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
      .query('SELECT id, id_role, Pseudonyme, Nom, Prenom, Email, Password_hash FROM Users WHERE Email = @email');

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



const swaggerSpec = {
  openapi: '3.0.3',
  info: { title: 'API Steam_2', version: '1.0.0' },
  servers: [{ url: 'http://localhost:3000' }],
  paths,
};



app.use('/swagger', swaggerUi.serve, swaggerUi.setup(swaggerSpec));

app.listen(3000, () => console.log('API sur http://localhost:3000'));