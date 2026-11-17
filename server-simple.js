require('dotenv').config();
const express = require('express');
const sql = require('mssql');
const swaggerUi = require('swagger-ui-express');
const bcrypt = require('bcryptjs');

const app = express();
app.use(express.json()); // lit le JSON envoyé dans le corps des requêtes POST

const config = {
  server: process.env.DB_SERVER || 'localhost',
  port: parseInt(process.env.DB_PORT || '1433', 10),
  database: process.env.DB_NAME || 'Steam_2',
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  options: { encrypt: true, trustServerCertificate: true },
};

const poolPromise = new sql.ConnectionPool(config)
  .connect()
  .then((pool) => {
    console.log('Connecté à SQL Server, base :', config.database);
    return pool;
  })
  .catch((err) => {
    console.error('ECHEC connexion SQL :', err.message);
    process.exit(1);
  });

// Crée une route GET : chemin dans l'URL  ->  requête SQL à exécuter
function route(chemin, requeteSQL) {
  app.get(chemin, async (req, res) => {
    try {
      const pool = await poolPromise;
      const result = await pool.request().query(requeteSQL);
      res.json(result.recordset);
    } catch (err) {
      res.status(500).json({ error: err.message });
    }
  });
}

app.get('/users/:id/achats', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const type = req.query.type;

  if (!Number.isInteger(id)) {
    return res.status(400).json({ error: 'id doit être un entier' });
  }

  try {
    const pool = await poolPromise;

    // Vérifier que l'utilisateur existe
    const userRes = await pool.request()
      .input('id', sql.Int, id)
      .query('SELECT id FROM Users WHERE id = @id');
    if (userRes.recordset.length === 0) {
      return res.status(404).json({ error: 'Utilisateur introuvable' });
    }

    // Récupérer les achats (filtre optionnel sur le type)
    const request = pool.request().input('id', sql.Int, id);
    let requeteSQL = `
      SELECT
        c.id,
        c.Nom,
        c.Description,
        c.Prix,
        c.Taille,
        t.Nom AS Type,
        c.id_Createur
      FROM Consommateur co
      JOIN Contenu c ON c.id = co.id_contenu
      JOIN [Type] t ON t.id = c.id_type
      WHERE co.id_user = @id
    `;
    if (type) {
      request.input('type', sql.VarChar(50), type);
      requeteSQL += ' AND t.Nom = @type';
    }

    const result = await request.query(requeteSQL);
    res.json(result.recordset);
  } catch (err) {
    console.error('Erreur récupération achats :', err.message);
    res.status(500).json({ error: err.message });
  }
});

// ---------- Une route par table ----------
route('/role',         'SELECT * FROM Role');
route('/sexe',         'SELECT * FROM Sexe');
route('/status',       'SELECT * FROM Status');
route('/type',         'SELECT * FROM [Type]');
route('/users',        'SELECT id, id_role, id_sexe, Pseudonyme, Nom, Prenom, Age, DateCreation, Porte_feuille, Email FROM Users'); // sans Password_hash
route('/contenu',      'SELECT * FROM Contenu');
route('/user_status',  'SELECT * FROM User_status');
route('/consommateur', 'SELECT * FROM Consommateur');
route('/likeur',       'SELECT * FROM Likeur');

// Pour ajouter une route : route('/mon-url', 'MA REQUETE SQL');

// ---------- POST /login ----------
// Corps attendu : { "email": "...", "password": "..." }
app.post('/login', async (req, res) => {
  const { email, password } = req.body || {};
  if (typeof email !== 'string' || typeof password !== 'string' || !email || !password) {
    return res.status(400).json({ error: 'email et password sont requis' });
  }
  try {
    const pool = await poolPromise;
    const result = await pool
      .request()
      .input('email', sql.VarChar(100), email)
      .query('SELECT id, id_role, Pseudonyme, Nom, Prenom, Email, Porte_feuille, Password_hash FROM Users WHERE Email = @email');

    const user = result.recordset[0];
    // Même message si l'email n'existe pas ou si le mot de passe est faux
    const ok = user && (await bcrypt.compare(password, user.Password_hash));
    if (!ok) return res.status(401).json({ error: 'Email ou mot de passe incorrect' });

    delete user.Password_hash; // ne jamais renvoyer le hash
    res.json({ message: 'Connexion réussie', user });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ---------- POST /acheter ----------
// Corps attendu : { "id_user": 1, "id_contenu": 3 }
app.post('/acheter', async (req, res) => {
  const { id_user, id_contenu } = req.body || {};

  if (!Number.isInteger(id_user) || !Number.isInteger(id_contenu)) {
    return res.status(400).json({ error: 'id_user et id_contenu sont requis (entiers)' });
  }

  const pool = await poolPromise;
  const transaction = new sql.Transaction(pool);

  try {
    await transaction.begin();

    // 1. L'utilisateur (ligne verrouillée pour éviter deux achats simultanés)
    const userRes = await new sql.Request(transaction)
      .input('id_user', sql.Int, id_user)
      .query('SELECT id, Porte_feuille FROM Users WITH (UPDLOCK, ROWLOCK) WHERE id = @id_user');
    const user = userRes.recordset[0];
    if (!user) {
      await transaction.rollback();
      return res.status(404).json({ error: 'Utilisateur introuvable' });
    }

    // 2. Le contenu
    const contenuRes = await new sql.Request(transaction)
      .input('id_contenu', sql.Int, id_contenu)
      .query('SELECT id, Nom, Prix, id_Createur FROM Contenu WHERE id = @id_contenu');
    const contenu = contenuRes.recordset[0];
    if (!contenu) {
      await transaction.rollback();
      return res.status(404).json({ error: 'Contenu introuvable' });
    }

    // 3. Pas d'achat de son propre contenu
    if (contenu.id_Createur === id_user) {
      await transaction.rollback();
      return res.status(403).json({ error: 'Vous ne pouvez pas acheter votre propre contenu' });
    }

    // 4. Déjà acheté ?
    const dejaRes = await new sql.Request(transaction)
      .input('id_user', sql.Int, id_user)
      .input('id_contenu', sql.Int, id_contenu)
      .query('SELECT 1 AS existe FROM Consommateur WHERE id_user = @id_user AND id_contenu = @id_contenu');
    if (dejaRes.recordset.length > 0) {
      await transaction.rollback();
      return res.status(409).json({ error: 'Contenu déjà acheté' });
    }

    // 5. Solde suffisant ? (en centimes pour éviter les erreurs de flottants)
    const soldeCents = Math.round(user.Porte_feuille * 100);
    const prixCents = Math.round(contenu.Prix * 100);
    if (soldeCents < prixCents) {
      await transaction.rollback();
      return res.status(402).json({
        error: 'Solde insuffisant',
        solde: user.Porte_feuille,
        prix: contenu.Prix,
        manquant: (prixCents - soldeCents) / 100,
      });
    }

    // 6. Débit du portefeuille
    await new sql.Request(transaction)
      .input('id_user', sql.Int, id_user)
      .input('prix', sql.Decimal(10, 2), contenu.Prix)
      .query('UPDATE Users SET Porte_feuille = Porte_feuille - @prix WHERE id = @id_user');

    // 7. Ajout de la liaison dans Consommateur
    await new sql.Request(transaction)
      .input('id_user', sql.Int, id_user)
      .input('id_contenu', sql.Int, id_contenu)
      .query('INSERT INTO Consommateur (id_user, id_contenu) VALUES (@id_user, @id_contenu)');

    await transaction.commit();

    res.status(201).json({
      message: 'Achat réussi',
      contenu: { id: contenu.id, Nom: contenu.Nom, Prix: contenu.Prix },
      nouveau_solde: (soldeCents - prixCents) / 100,
    });
  } catch (err) {
    try { await transaction.rollback(); } catch (_) { /* déjà fermée */ }
    console.error('Erreur achat :', err.message);
    res.status(500).json({ error: err.message });
  }
});

// ---------- POST /create_user ----------
// Corps attendu :
// {
//   "id_role": 1,
//   "id_sexe": 1,
//   "Pseudonyme": "Joueur123",
//   "Nom": "Dupont",
//   "Prenom": "Jean",
//   "Age": 20,
//   "Email": "test@mail.com",
//   "password": "monMotDePasse"
// }

app.post('/create_user', async (req, res) => {
  const {
    id_role,
    id_sexe,
    Pseudonyme,
    Nom,
    Prenom,
    Age,
    Email,
    password
  } = req.body || {};

  // Vérification des champs obligatoires
  if (
    id_role == null ||
    id_sexe == null ||
    !Pseudonyme ||
    !Nom ||
    !Prenom ||
    Age == null ||
    !Email ||
    !password
  ) {
    return res.status(400).json({
      error: 'Tous les champs sont obligatoires'
    });
  }

  try {
    const pool = await poolPromise;

    // Vérifier si l'email existe déjà
    const existingUser = await pool.request()
      .input('email', sql.VarChar(100), Email)
      .query('SELECT id FROM Users WHERE Email = @email');

    if (existingUser.recordset.length > 0) {
      return res.status(409).json({
        error: 'Cette adresse email est déjà utilisée'
      });
    }

    // Hasher le mot de passe
    const passwordHash = await bcrypt.hash(password, 10);

    // Insérer le nouvel utilisateur
    const result = await pool.request()
      .input('id_role', sql.Int, id_role)
      .input('id_sexe', sql.Int, id_sexe)
      .input('Pseudonyme', sql.VarChar(100), Pseudonyme)
      .input('Nom', sql.VarChar(100), Nom)
      .input('Prenom', sql.VarChar(100), Prenom)
      .input('Age', sql.Int, Age)
      .input('Email', sql.VarChar(100), Email)
      .input('Password_hash', sql.VarChar(255), passwordHash)
      .query(`
        INSERT INTO Users (
          id_role,
          id_sexe,
          Pseudonyme,
          Nom,
          Prenom,
          Age,
          DateCreation,
          Porte_feuille,
          Email,
          Password_hash
        )
        OUTPUT
          INSERTED.id,
          INSERTED.id_role,
          INSERTED.id_sexe,
          INSERTED.Pseudonyme,
          INSERTED.Nom,
          INSERTED.Prenom,
          INSERTED.Age,
          INSERTED.DateCreation,
          INSERTED.Email
        VALUES (
          @id_role,
          @id_sexe,
          @Pseudonyme,
          @Nom,
          @Prenom,
          @Age,
          GETDATE(),
          0,
          @Email,
          @Password_hash
        )
      `);

    res.status(201).json({
      message: 'Utilisateur créé avec succès',
      user: result.recordset[0]
    });

  } catch (err) {
    console.error('Erreur création utilisateur :', err.message);
    res.status(500).json({
      error: err.message
    });
  }
});


// ---------- Swagger : /swagger ----------
// La doc est générée automatiquement à partir de toutes les routes GET déclarées ci-dessus
const paths = {};
app._router.stack
  .filter((layer) => layer.route && layer.route.methods.get)
  .forEach((layer) => {
    paths[layer.route.path] = {
      get: {
        summary: 'SELECT * FROM ' + layer.route.path.slice(1),
        responses: {
          200: {
            description: 'Liste des lignes (JSON)',
            content: { 'application/json': { schema: { type: 'array', items: { type: 'object' } } } },
          },
          500: { description: 'Erreur serveur ou SQL' },
        },
      },
    };
  });

delete paths['/users/:id/achats']; // supprime l'entrée auto-générée

paths['/users/{id}/achats'] = {
  get: {
    summary: "Liste du contenu acheté par un utilisateur (filtre optionnel ?type=Jeu)",
    parameters: [
      { name: 'id', in: 'path', required: true, schema: { type: 'integer', example: 4 } },
      { name: 'type', in: 'query', required: false, schema: { type: 'string', example: 'Jeu' } },
    ],
    responses: {
      200: { description: 'Liste du contenu acheté (JSON)' },
      400: { description: 'id invalide' },
      404: { description: 'Utilisateur introuvable' },
      500: { description: 'Erreur serveur ou SQL' },
    },
  },
};

// POST /login (le code ci-dessus ne repère que les GET, on l'ajoute à la main)
paths['/login'] = {
  post: {
    summary: 'Connexion : vérifie email + mot de passe dans Users',
    requestBody: {
      required: true,
      content: {
        'application/json': {
          schema: {
            type: 'object',
            required: ['email', 'password'],
            properties: { email: { type: 'string', example: 'test@mail.com' }, password: { type: 'string', example: 'monMotDePasse' } },
          },
        },
      },
    },
    responses: {
      200: { description: 'Connexion réussie (renvoie l\'utilisateur sans le hash)' },
      400: { description: 'email ou password manquant' },
      401: { description: 'Email ou mot de passe incorrect' },
      500: { description: 'Erreur serveur ou SQL' },
    },
  },
};


paths['/create_user'] = {
  post: {
    summary: 'Créer un nouvel utilisateur',
    requestBody: {
      required: true,
      content: {
        'application/json': {
          schema: {
            type: 'object',
            required: [
              'id_role',
              'id_sexe',
              'Pseudonyme',
              'Nom',
              'Prenom',
              'Age',
              'Email',
              'password'
            ],
            properties: {
              id_role: { type: 'integer', example: 1 },
              id_sexe: { type: 'integer', example: 1 },
              Pseudonyme: { type: 'string', example: 'Joueur123' },
              Nom: { type: 'string', example: 'Dupont' },
              Prenom: { type: 'string', example: 'Jean' },
              Age: { type: 'integer', example: 20 },
              Email: { type: 'string', example: 'test@mail.com' },
              password: { type: 'string', example: 'monMotDePasse' }
            }
          }
        }
      }
    },
    responses: {
      201: { description: 'Utilisateur créé avec succès' },
      400: { description: 'Champs manquants' },
      409: { description: 'Email déjà utilisé' },
      500: { description: 'Erreur serveur ou SQL' }
    }
  }
};

paths['/acheter'] = {
  post: {
    summary: 'Acheter un contenu (vérifie le solde, débite, enregistre dans Consommateur)',
    requestBody: {
      required: true,
      content: {
        'application/json': {
          schema: {
            type: 'object',
            required: ['id_user', 'id_contenu'],
            properties: {
              id_user: { type: 'integer', example: 1 },
              id_contenu: { type: 'integer', example: 3 },
            },
          },
        },
      },
    },
    responses: {
      201: { description: 'Achat réussi (renvoie le nouveau solde)' },
      400: { description: 'id_user ou id_contenu manquant ou invalide' },
      402: { description: 'Solde insuffisant' },
      403: { description: 'Achat de son propre contenu interdit' },
      404: { description: 'Utilisateur ou contenu introuvable' },
      409: { description: 'Contenu déjà acheté' },
      500: { description: 'Erreur serveur ou SQL' },
    },
  },
};

const swaggerSpec = {
  openapi: '3.0.3',
  info: { title: 'API Steam_2', version: '1.0.0' },
  servers: [{ url: 'http://localhost:3000' }],
  paths,
};





app.use('/swagger', swaggerUi.serve, swaggerUi.setup(swaggerSpec));

app.listen(3000, () => console.log('API sur http://localhost:3000'));