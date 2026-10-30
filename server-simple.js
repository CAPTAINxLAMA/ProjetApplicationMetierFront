require('dotenv').config();
const express = require('express');
const sql = require('mssql');

const app = express();

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

app.listen(3000, () => console.log('API sur http://localhost:3000'));
