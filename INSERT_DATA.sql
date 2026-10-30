USE [Steam_2];
GO

SET NOCOUNT ON;

/* =========================================================
   1. ROLE
   ========================================================= */
INSERT INTO Role (Nom)
SELECT v.Nom
FROM (VALUES ('Content_Creator'), ('Moderateur'), ('Consommateur')) AS v(Nom)
WHERE NOT EXISTS (SELECT 1 FROM Role r WHERE r.Nom = v.Nom);

/* =========================================================
   2. SEXE
   ========================================================= */
INSERT INTO Sexe (Genre)
SELECT v.Genre
FROM (VALUES ('Homme'), ('Femme'), ('Autre'), ('Non précisé')) AS v(Genre)
WHERE NOT EXISTS (SELECT 1 FROM Sexe s WHERE s.Genre = v.Genre);

/* =========================================================
   3. STATUS
   ========================================================= */
INSERT INTO Status (Nom)
SELECT v.Nom
FROM (VALUES ('Actif'), ('Suspendu'), ('Banni')) AS v(Nom)
WHERE NOT EXISTS (SELECT 1 FROM Status s WHERE s.Nom = v.Nom);

/* =========================================================
   4. TYPE
   ========================================================= */
INSERT INTO [Type] (Nom)
SELECT v.Nom
FROM (VALUES ('Jeu'), ('DLC'), ('Demo'), ('Logiciel'), ('Bande originale'), ('Mod')) AS v(Nom)
WHERE NOT EXISTS (SELECT 1 FROM [Type] t WHERE t.Nom = v.Nom);

/* =========================================================
   5. USERS
   Unicité vérifiée sur Pseudonyme ET Email (colonnes UNIQUE)
   Les id_role / id_sexe sont retrouvés par leur libellé.
   ========================================================= */
INSERT INTO Users (id_role, id_sexe, Pseudonyme, Nom, Prenom, Age, DateCreation, Porte_feuille, Email, Password_hash)
SELECT r.id, s.id, v.Pseudonyme, v.Nom, v.Prenom, v.Age, v.DateCreation, v.Porte_feuille, v.Email, v.Password_hash
FROM (VALUES
    ('Content_Creator',     'Homme',       'cc_gaben',     'Newell',   'Gabe',    62, CAST('2020-01-10' AS DATE), 500.00, 'gaben.n@steam2.fr',   'hash_cc_001'),
    ('Content_Creator',     'Femme',       'cc_sophie',    'Martin',   'Sophie',  34, CAST('2021-03-15' AS DATE), 120.50, 'sophie.m@steam2.fr',  'hash_cc_002'),
    ('Content_Creator',     'Homme',       'cc_pixel',     'Durand',   'Lucas',   29, CAST('2021-06-20' AS DATE),  75.00, 'lucas.d@steam2.fr',   'hash_cc_003'),
    ('Moderateur',          'Femme',       'mod_nova',     'Lefebvre', 'Camille', 31, CAST('2022-02-05' AS DATE),  60.00, 'camille.l@steam2.fr', 'hash_mod_004'),
    ('Moderateur',          'Homme',       'mod_Killer',   'Bernard',  'Thomas',  22, CAST('2022-09-12' AS DATE),  45.75, 'thomas.b@mail.fr',    'hash_mod_005'),
    ('Consommateur',        'Femme',       'LadyGamer',    'Petit',    'Emma',    25, CAST('2023-01-18' AS DATE),  80.00, 'emma.p@mail.fr',      'hash_usr_006'),
    ('Consommateur',        'Autre',       'ShadowFox',    'Moreau',   'Alex',    27, CAST('2023-04-02' AS DATE),  15.30, 'alex.m@mail.fr',      'hash_usr_007'),
    ('Consommateur',        'Non précisé', 'NoobMaster',   'Simon',    'Hugo',    19, CAST('2024-05-27' AS DATE),   5.00, 'hugo.s@mail.fr',      'hash_usr_008')
) AS v(RoleNom, SexeGenre, Pseudonyme, Nom, Prenom, Age, DateCreation, Porte_feuille, Email, Password_hash)
JOIN Role r ON r.Nom = v.RoleNom
JOIN Sexe s ON s.Genre = v.SexeGenre
WHERE NOT EXISTS (
    SELECT 1 FROM Users u
    WHERE u.Pseudonyme = v.Pseudonyme
       OR u.Email = v.Email
);

/* =========================================================
   6. CONTENU
   Unicité vérifiée sur (Nom, id_Createur).
   Le créateur est retrouvé par son pseudonyme.
   ========================================================= */
INSERT INTO Contenu (id_type, Nom, Description, Prix, Taille, id_Createur)
SELECT t.id, v.Nom, v.Description, v.Prix, v.Taille, u.id
FROM (VALUES
    ('Jeu',             'Pixel Quest',          'Un RPG rétro en pixel art',               19.99, 2500.00, 'dev_pixel'),
    ('Jeu',             'Nova Racing',          'Course futuriste en réseau',              29.99, 8200.50, 'dev_nova'),
    ('DLC',             'Pixel Quest - Donjons','Extension de 10 nouveaux donjons',         7.99,  600.00, 'dev_pixel'),
    ('Bande originale', 'Nova Racing OST',      'Bande originale officielle',               4.99,  350.75, 'dev_nova'),
    ('Mod',             'Pixel Quest HD',       'Pack de textures haute définition',        0.00,  1200.00, 'dev_pixel'),
    ('Logiciel',        'Level Editor Pro',     'Outil de création de niveaux',            14.50,  450.00, 'dev_nova')
) AS v(TypeNom, Nom, Description, Prix, Taille, Createur)
JOIN [Type] t ON t.Nom = v.TypeNom
JOIN Users  u ON u.Pseudonyme = v.Createur
WHERE NOT EXISTS (
    SELECT 1 FROM Contenu c
    WHERE c.Nom = v.Nom
      AND c.id_Createur = u.id
);

/* =========================================================
   7. USER_STATUS (corrigé : dates au format ISO avec 'T')
   ========================================================= */
INSERT INTO User_status (id_user, id_status, Description, DateFin)
SELECT u.id, s.id, v.Description, v.DateFin
FROM (VALUES
    ('cc_gaben',   'Actif',    'Compte content creator vérifié',    CAST(NULL AS DATETIME)),
    ('cc_sophie',  'Actif',    'Compte content creator vérifié',    CAST(NULL AS DATETIME)),
    ('cc_pixel',   'Actif',    'Compte content creator vérifié',    CAST(NULL AS DATETIME)),
    ('mod_nova',   'Actif',    'Modérateur validé',                 CAST(NULL AS DATETIME)),
    ('mod_Killer', 'Actif',    'Modérateur validé',                 CAST('2026-12-31T23:59:00' AS DATETIME)),
    ('LadyGamer',  'Suspendu', 'Comportement toxique en ligne',     CAST(NULL AS DATETIME)),
    ('ShadowFox',  'Actif',    'Compte en règle',                   CAST(NULL AS DATETIME)),
    ('NoobMaster', 'Banni',    'Triche (Neuille)',                  CAST('2026-11-30T00:00:00' AS DATETIME))
) AS v(Pseudonyme, StatusNom, Description, DateFin)
JOIN Users  u ON u.Pseudonyme = v.Pseudonyme
JOIN Status s ON s.Nom = v.StatusNom
WHERE NOT EXISTS (
    SELECT 1 FROM User_status us
    WHERE us.id_user = u.id
      AND us.id_status = s.id
);

/* =========================================================
   8. CONSOMMATEUR (achats)
   Clé primaire (id_user, id_contenu).
   ========================================================= */
INSERT INTO Consommateur (id_user, id_contenu)
SELECT u.id, c.id
FROM (VALUES
    ('mod_Killer',   'Pixel Quest'),
    ('mod_Killer',   'Nova Racing'),
    ('LadyGamer',    'Pixel Quest'),
    ('LadyGamer',    'Pixel Quest - Donjons'),
    ('LadyGamer',    'Nova Racing OST'),
    ('ShadowFox',    'Nova Racing'),
    ('ShadowFox',    'Level Editor Pro'),
    ('NoobMaster',   'Pixel Quest HD'),
    ('cc_sophie',    'Pixel Quest')
) AS v(Pseudonyme, ContenuNom)
JOIN Users   u ON u.Pseudonyme = v.Pseudonyme
JOIN Contenu c ON c.Nom = v.ContenuNom
WHERE NOT EXISTS (
    SELECT 1 FROM Consommateur co
    WHERE co.id_user = u.id
      AND co.id_contenu = c.id
);

/* =========================================================
   9. LIKEUR (likes)
   Clé primaire (id_user, id_contenu).
   ========================================================= */
INSERT INTO Likeur (id_user, id_contenu)
SELECT u.id, c.id
FROM (VALUES
    ('mod_Killer',   'Nova Racing'),
    ('LadyGamer',    'Pixel Quest'),
    ('LadyGamer',    'Nova Racing OST'),
    ('ShadowFox',    'Nova Racing'),
    ('ShadowFox',    'Level Editor Pro'),
    ('NoobMaster',   'Pixel Quest HD'),
    ('cc_sophie',    'Pixel Quest'),
    ('cc_pixel',     'Nova Racing')
) AS v(Pseudonyme, ContenuNom)
JOIN Users   u ON u.Pseudonyme = v.Pseudonyme
JOIN Contenu c ON c.Nom = v.ContenuNom
WHERE NOT EXISTS (
    SELECT 1 FROM Likeur l
    WHERE l.id_user = u.id
      AND l.id_contenu = c.id
);
GO

/* =========================================================
   Vérification rapide
   ========================================================= */
SELECT 'Role' AS [Table], COUNT(*) AS Nb FROM Role
UNION ALL SELECT 'Sexe',        COUNT(*) FROM Sexe
UNION ALL SELECT 'Status',      COUNT(*) FROM Status
UNION ALL SELECT 'Type',        COUNT(*) FROM [Type]
UNION ALL SELECT 'Users',       COUNT(*) FROM Users
UNION ALL SELECT 'Contenu',     COUNT(*) FROM Contenu
UNION ALL SELECT 'User_status', COUNT(*) FROM User_status
UNION ALL SELECT 'Consommateur',COUNT(*) FROM Consommateur
UNION ALL SELECT 'Likeur',      COUNT(*) FROM Likeur;
GO