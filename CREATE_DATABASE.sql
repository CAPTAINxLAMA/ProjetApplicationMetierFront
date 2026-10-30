
-- Créer la base si elle n'existe pas
IF DB_ID('Steam_2') IS NULL
BEGIN
    CREATE DATABASE Steam_2;
END;
GO

USE [Steam_2];
GO

IF OBJECT_ID('dbo.Role', 'U') IS NULL
BEGIN
    CREATE TABLE Role (
       id INT IDENTITY(1,1) PRIMARY KEY,
       Nom VARCHAR(50) NOT NULL
    );
END;

IF OBJECT_ID('dbo.Sexe', 'U') IS NULL
BEGIN
    CREATE TABLE Sexe (
       id INT IDENTITY(1,1) PRIMARY KEY,
       Genre VARCHAR(50) NOT NULL
    );
END;

IF OBJECT_ID('dbo.Status', 'U') IS NULL
BEGIN
    CREATE TABLE Status (
       id INT IDENTITY(1,1) PRIMARY KEY,
       Nom VARCHAR(50) NOT NULL
    );
END;

IF OBJECT_ID('dbo.Type', 'U') IS NULL
BEGIN
    CREATE TABLE [Type] (
       id INT IDENTITY(1,1) PRIMARY KEY,
       Nom VARCHAR(50) NOT NULL
    );
END;

IF OBJECT_ID('dbo.Users', 'U') IS NULL
BEGIN
    CREATE TABLE Users (
       id INT IDENTITY(1,1) PRIMARY KEY,
       id_role INT NOT NULL,
       id_sexe INT,
       Pseudonyme VARCHAR(50) NOT NULL UNIQUE,
       Nom VARCHAR(50) NOT NULL,
       Prenom VARCHAR(50) NOT NULL,
       Age INT NOT NULL,
       DateCreation DATE NOT NULL,
       Porte_feuille DECIMAL(10,2) NOT NULL DEFAULT 0,
       Email VARCHAR(100) NOT NULL UNIQUE,
       Password_hash VARCHAR(255) NOT NULL,
       FOREIGN KEY (id_role) REFERENCES Role(id),
       FOREIGN KEY (id_sexe) REFERENCES Sexe(id)
    );
END;

IF OBJECT_ID('dbo.Contenu', 'U') IS NULL
BEGIN
    CREATE TABLE Contenu (
       id INT IDENTITY(1,1) PRIMARY KEY,
       id_type INT NOT NULL,
       Nom VARCHAR(50) NOT NULL,
       Description VARCHAR(255),
       Prix DECIMAL(10,2) NOT NULL,
       Taille DECIMAL(15,2) NOT NULL,
       id_Createur INT NOT NULL,
       FOREIGN KEY (id_Createur) REFERENCES Users(id),
       FOREIGN KEY (id_type) REFERENCES [Type](id)
    );
END;

-- Statuts des utilisateurs
IF OBJECT_ID('dbo.User_status', 'U') IS NULL
BEGIN
    CREATE TABLE User_status (
       id_user INT,
       id_status INT,
       Description VARCHAR(255),
       DateFin DATETIME,
       PRIMARY KEY (id_user, id_status),
       FOREIGN KEY (id_user) REFERENCES Users(id),
       FOREIGN KEY (id_status) REFERENCES Status(id)
    );
END;

-- Achats / consommation
IF OBJECT_ID('dbo.Consommateur', 'U') IS NULL
BEGIN
    CREATE TABLE Consommateur (
       id_user INT,
       id_contenu INT,
       PRIMARY KEY (id_user, id_contenu),
       FOREIGN KEY (id_user) REFERENCES Users(id),
       FOREIGN KEY (id_contenu) REFERENCES Contenu(id)
    );
END;

-- Likes
IF OBJECT_ID('dbo.Likeur', 'U') IS NULL
BEGIN
    CREATE TABLE Likeur (
       id_user INT,
       id_contenu INT,
       PRIMARY KEY (id_user, id_contenu),
       FOREIGN KEY (id_user) REFERENCES Users(id),
       FOREIGN KEY (id_contenu) REFERENCES Contenu(id)
    );
END;
