require('dotenv').config();
const jwt = require('jsonwebtoken');
const id = parseInt(process.argv[2] || '4', 10);
const role = parseInt(process.argv[3] || '3', 10);
const duree = process.argv[4] || '10m';
console.log(jwt.sign({ id, id_role: role }, process.env.JWT_SECRET, { expiresIn: duree }));
