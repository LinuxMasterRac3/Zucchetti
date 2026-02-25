-- Database creation script for Z-Volta Asset Management
-- Created for MySQL/MariaDB environment (XAMPP default)

CREATE DATABASE IF NOT EXISTS z_volta_db;
USE z_volta_db;

-- =================================================================================
-- 1. Table: ruoli (Roles)
-- =================================================================================
CREATE TABLE IF NOT EXISTS ruoli (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descrizione VARCHAR(255),
    max_prenotazioni INT DEFAULT 1
);

INSERT INTO ruoli (nome, descrizione, max_prenotazioni) VALUES 
('gestore', 'Amministratore del sistema, gestione completa', 100),
('coordinatore', 'Gestisce un team e ha privilegi di prenotazione estesi', 3),
('dipendente', 'Utente base, può prenotare asset limitati', 1);

-- =================================================================================
-- 2. Table: utenti (Users)
-- =================================================================================
CREATE TABLE IF NOT EXISTS utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    id_ruolo INT NOT NULL,
    id_coordinatore INT NULL,
    
    FOREIGN KEY (id_ruolo) REFERENCES ruoli(id),
    FOREIGN KEY (id_coordinatore) REFERENCES utenti(id) ON DELETE SET NULL
);

-- Gestore + Coordinatori
INSERT INTO utenti (nome, cognome, username, password, id_ruolo, id_coordinatore) VALUES 
('Mario', 'Rossi', 'admin', 'Admin123!', 1, NULL),
('Luigi', 'Verdi', 'coord1', 'Coord123!', 2, NULL),
('Anna', 'Bianchi', 'coord2', 'Coord123!', 2, NULL);

-- Dipendenti
INSERT INTO utenti (nome, cognome, username, password, id_ruolo, id_coordinatore) VALUES 
('Paolo', 'Neri', 'dip1', 'User1234!', 3, 2),
('Giulia', 'Gialli', 'dip2', 'User1234!', 3, 2),
('Marco', 'Blu', 'dip3', 'User1234!', 3, 3);

-- =================================================================================
-- 3. Table: tipi_asset (Asset Types)
-- =================================================================================
CREATE TABLE IF NOT EXISTS tipi_asset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codice_tipo VARCHAR(10) NOT NULL UNIQUE,
    descrizione VARCHAR(255) NOT NULL
);

INSERT INTO tipi_asset (codice_tipo, descrizione) VALUES 
('A', 'Scrivania - Cassettiera - Armadietto'),
('A2', 'Scrivania con Monitor - Cassettiera - Armadietto'),
('B', 'Sala Riunioni'),
('C', 'Posto Auto');

-- =================================================================================
-- 4. Table: assets
-- =================================================================================
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codice_univoco VARCHAR(50) NOT NULL UNIQUE,
    id_tipo INT NOT NULL,
    stato_attuale ENUM('disponibile', 'occupato', 'non_prenotabile') DEFAULT 'disponibile',
    
    FOREIGN KEY (id_tipo) REFERENCES tipi_asset(id)
);

-- Tipo A: Scrivania base (20 richiesti)
INSERT INTO assets (codice_univoco, id_tipo) VALUES 
('A-01', 1), ('A-02', 1), ('A-03', 1), ('A-04', 1), ('A-05', 1),
('A-06', 1), ('A-07', 1), ('A-08', 1), ('A-09', 1), ('A-10', 1),
('A-11', 1), ('A-12', 1), ('A-13', 1), ('A-14', 1), ('A-15', 1),
('A-16', 1), ('A-17', 1), ('A-18', 1), ('A-19', 1), ('A-20', 1);

-- Tipo A2: Scrivania con Monitor (30 richiesti)
INSERT INTO assets (codice_univoco, id_tipo) VALUES 
('A2-01', 2), ('A2-02', 2), ('A2-03', 2), ('A2-04', 2), ('A2-05', 2),
('A2-06', 2), ('A2-07', 2), ('A2-08', 2), ('A2-09', 2), ('A2-10', 2),
('A2-11', 2), ('A2-12', 2), ('A2-13', 2), ('A2-14', 2), ('A2-15', 2),
('A2-16', 2), ('A2-17', 2), ('A2-18', 2), ('A2-19', 2), ('A2-20', 2),
('A2-21', 2), ('A2-22', 2), ('A2-23', 2), ('A2-24', 2), ('A2-25', 2),
('A2-26', 2), ('A2-27', 2), ('A2-28', 2), ('A2-29', 2), ('A2-30', 2);

-- Tipo B: Sala Riunioni (5 richiesti)
INSERT INTO assets (codice_univoco, id_tipo) VALUES 
('B-01', 3), ('B-02', 3), ('B-03', 3), ('B-04', 3), ('B-05', 3);

-- Tipo C: Posto Auto (10 richiesti)
INSERT INTO assets (codice_univoco, id_tipo) VALUES 
('C-01', 4), ('C-02', 4), ('C-03', 4), ('C-04', 4), ('C-05', 4),
('C-06', 4), ('C-07', 4), ('C-08', 4), ('C-09', 4), ('C-10', 4);

-- =================================================================================
-- 5. Table: prenotazioni (Bookings)
-- =================================================================================
CREATE TABLE IF NOT EXISTS prenotazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    id_asset INT NOT NULL,
    data_prenotazione DATE NOT NULL,
    ora_inizio TIME NOT NULL,
    ora_fine TIME NOT NULL,
    timestamp_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modifiche_counter INT DEFAULT 0 CHECK (modifiche_counter <= 2),
    stato_prenotazione ENUM('attiva', 'cancellata', 'revocata') DEFAULT 'attiva',
    
    FOREIGN KEY (id_utente) REFERENCES utenti(id),
    FOREIGN KEY (id_asset) REFERENCES assets(id)
);

-- Esempio di prenotazioni
INSERT INTO prenotazioni (id_utente, id_asset, data_prenotazione, ora_inizio, ora_fine) VALUES
(4, 1, CURDATE() + INTERVAL 1 DAY, '09:00:00', '18:00:00'),
(5, 22, CURDATE() + INTERVAL 1 DAY, '09:00:00', '18:00:00'),
(6, 3, CURDATE() + INTERVAL 2 DAY, '09:00:00', '13:00:00');
