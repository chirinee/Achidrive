-- =====================================================
-- ACHIDRIVE — schéma et données de démonstration (MySQL / MariaDB)
-- Toutes les personnes ci-dessous sont fictives.
-- =====================================================

DROP TABLE IF EXISTS Transports;
DROP TABLE IF EXISTS Propositions;
DROP TABLE IF EXISTS Internautes;

-- ─── Internautes ───
CREATE TABLE IF NOT EXISTS Internautes (
 email VARCHAR(255) NOT NULL PRIMARY KEY,
 motDePasse VARCHAR(255) NOT NULL,
 nom VARCHAR(50) NOT NULL,
 prenom VARCHAR(50) NOT NULL,
 telephone VARCHAR(20) DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


INSERT INTO Internautes (email, motDePasse, nom, prenom, telephone) VALUES
('alice.dupont@email.com', SHA2('MotDePasseAlice123', 224), 'Dupont', 'Alice', '+33 6 12 34 56 78'),
('jean.renaud@email.com', SHA2('JeanSecret!@#', 224), 'Renaud', 'Jean', '+33 7 98 76 54 32'),
('marie.lefevre@email.com', SHA2('MariePwd2025', 224), 'Lefèvre', 'Marie','+33 7 49 13 02 04' ),
('li.wei@email.com', SHA2('WeiParis2025', 224), 'Wei', 'Li', '+33 6 12 34 56 78'),
('fatoumata.sy@email.com', SHA2('SyMdpStrong!', 224), 'Sy', 'Fatoumata', '+33 7 98 76 54 32'),
('akira.tanaka@email.com', SHA2('Akira75001', 224), 'Tanaka', 'Akira', '+33 6 11 22 33 44'),
('yasmine.belkacem@email.com', SHA2('YasBordeaux', 224), 'Belkacem', 'Yasmine', '+33 7 55 66 77 88'),
('kofi.mensah@email.com', SHA2('KofiM!1990', 224), 'Mensah', 'Kofi', '+33 6 44 55 66 77'),
('mei.lin@email.com', SHA2('MeiLinPwd$', 224), 'Lin', 'Mei', '+33 7 12 12 12 12'),
('omar.diallo@email.com', SHA2('OmarD2024', 224), 'Diallo', 'Omar', '+33 6 99 88 77 66'),
('priya.kapoor@email.com', SHA2('PriyaKap#', 224), 'Kapoor', 'Priya', '+33 7 23 45 67 89'),
('thi.nguyen@email.com', SHA2('ThiNguyen!', 224), 'Nguyen', 'Thi', '+33 6 10 20 30 40'),
('mehdi.benali@email.com', SHA2('MehdiBen77', 224), 'Benali', 'Mehdi', '+33 7 65 43 21 09'),
('sakura.yamada@email.com', SHA2('SakuraYama', 224), 'Yamada', 'Sakura', '+33 6 50 60 70 80'),
('aissa.traore@email.com', SHA2('BamakoParis', 224), 'Traoré', 'Aïssa', '+33 7 11 22 33 44'),
('jun.park@email.com', SHA2('JunParkCoree', 224), 'Park', 'Jun', '+33 6 25 36 47 58'),
('sofia.elmansouri@email.com', SHA2('SofiaElm@', 224), 'El Mansouri', 'Sofia', '+33 7 88 99 00 11'),
('chen.wu@email.com', SHA2('ChenWu888', 224), 'Wu', 'Chen', '+33 6 14 25 36 99'),
('mamadou.konate@email.com', SHA2('KonateM!75', 224), 'Konaté', 'Mamadou', '+33 7 77 88 99 00'),
('minh.tran@email.com', SHA2('MinhTranVn', 224), 'Tran', 'Minh', '+33 6 33 44 55 66'),
('leila.haddad@email.com', SHA2('LeilaH!33', 224), 'Haddad', 'Leïla', '+33 7 66 55 44 33'),
('kenji.sato@email.com', SHA2('KenjiSato1', 224), 'Sato', 'Kenji', '+33 6 22 33 44 55'),
('amina.sow@email.com', SHA2('AminaSow221', 224), 'Sow', 'Amina', '+33 7 19 28 37 46'),
('raj.patel@email.com', SHA2('RajPatelInd', 224), 'Patel', 'Raj', '+33 6 98 76 54 32'),
('han.kim@email.com', SHA2('HanSoloKim', 224), 'Kim', 'Han', '+33 7 56 78 90 12'),
('youssef.mansour@email.com', SHA2('YoussMan99', 224), 'Mansour', 'Youssef', '+33 6 01 02 03 04'),
('lin.zhang@email.com', SHA2('LinZhangZC', 224), 'Zhang', 'Lin', '+33 7 11 11 22 22'),
('binta.diop@email.com', SHA2('BintaD@kar', 224), 'Diop', 'Binta', '+33 6 77 77 77 77'),
('anh.pham@email.com', SHA2('AnhPham33', 224), 'Pham', 'Anh', '+33 7 44 44 44 44'),
('lucas.moreau@email.com', SHA2('LucasM2025', 224), 'Moreau', 'Lucas', '+33 6 69 69 69 69'),
('idrissa.gueye@email.com', SHA2('GanaGueye', 224), 'Gueye', 'Idrissa', '+33 7 21 21 21 21'),
('hiroshi.nakamura@email.com', SHA2('HiroNaka!', 224), 'Nakamura', 'Hiroshi', '+33 6 12 12 12 12'),
('zara.ali@email.com', SHA2('ZaraAli2025', 224), 'Ali', 'Zara', '+33 7 34 34 34 34'),
-- Compte de démonstration (voir README)
('demo@achidrive.fr', SHA2('demo1234', 224), 'Demo', 'Camille', NULL);


-- ─── Propositions de trajets ───
CREATE TABLE IF NOT EXISTS Propositions (
 idProposition INT AUTO_INCREMENT PRIMARY KEY,
 emailConducteur VARCHAR(255) NOT NULL,
 villeD VARCHAR(255) NOT NULL,
 villeA VARCHAR(255) NOT NULL,
 dateSouhaite VARCHAR(6) NOT NULL,
 nbPlaces INT NOT NULL DEFAULT 1,
 prix FLOAT,
 CONSTRAINT fk_conducteur FOREIGN KEY (emailConducteur) REFERENCES Internautes(email) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


INSERT INTO Propositions (emailConducteur, villeD, villeA, dateSouhaite, nbPlaces, prix) VALUES
('alice.dupont@email.com','Marseille', 'Toulouse', '270212', 3, 30),
('jean.renaud@email.com','Lille', 'Paris', '271230', 2, 15.5),
('marie.lefevre@email.com','Nantes', 'Rennes', '270731', 4, 10),
('li.wei@email.com', 'Paris', 'Lyon', '270615', 3, 45.00),
('fatoumata.sy@email.com', 'Marseille', 'Lille', '270701', 2, 89.00),
('akira.tanaka@email.com', 'Paris', 'Lyon', '270620', 4, 25.50),
('yasmine.belkacem@email.com', 'Bordeaux', 'Paris', '270812', 3, 60.00),
('kofi.mensah@email.com', 'Nantes', 'Strasbourg', '270905', 2, 33.00),
('mei.lin@email.com', 'Paris', 'Lyon', '271010', 1, 15.00),
('omar.diallo@email.com', 'Lyon', 'Marseille', '271111', 4, 12.50),
('priya.kapoor@email.com', 'Paris', 'Nice', '271224', 2, 95.00),
('thi.nguyen@email.com', 'Bordeaux', 'Paris', '270501', 3, 55.00),
('mehdi.benali@email.com', 'Lille', 'Montpellier', '270714', 2, 78.00),
('sakura.yamada@email.com', 'Paris', 'Lyon', '270618', 3, 88.50),
('aissa.traore@email.com', 'Marseille', 'Lille', '270830', 4, 42.00),
('jun.park@email.com', 'Lyon', 'Marseille', '270915', 2, 9.99),
('sofia.elmansouri@email.com', 'Toulouse', 'Paris', '271020', 3, 35.00),
('lucas.moreau@email.com', 'Lyon', 'Madrid', '270601', 1, 100.00);

-- ─── Réservations ───
CREATE TABLE IF NOT EXISTS Transports (
 idTransport INT AUTO_INCREMENT PRIMARY KEY,
 idProposition INT NOT NULL,
 emailPassager VARCHAR(255) NOT NULL,
 CONSTRAINT fk_proposition FOREIGN KEY (idProposition) REFERENCES Propositions(idProposition) ON DELETE CASCADE,
 CONSTRAINT fk_passager FOREIGN KEY (emailPassager) REFERENCES Internautes(email) ON DELETE CASCADE 
,
 CONSTRAINT uq_reservation UNIQUE (idProposition, emailPassager)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


INSERT INTO Transports (idProposition, emailPassager) VALUES
(1, 'jean.renaud@email.com'),
(2, 'marie.lefevre@email.com'),
(3, 'alice.dupont@email.com'),    
(1, 'chen.wu@email.com'),
(2, 'mamadou.konate@email.com'),
(3, 'minh.tran@email.com'),
(4, 'leila.haddad@email.com'),
(5, 'kenji.sato@email.com'),
(6, 'amina.sow@email.com'),
(7, 'raj.patel@email.com'),
(8, 'han.kim@email.com'),
(9, 'youssef.mansour@email.com'),
(10, 'lin.zhang@email.com'),
(15, 'binta.diop@email.com'),
(14, 'anh.pham@email.com'),
(12, 'idrissa.gueye@email.com'),
(1, 'hiroshi.nakamura@email.com'),
(13, 'zara.ali@email.com');
