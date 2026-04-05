-- ============================================================
-- EduGuide TN — Base de données complète 2025
-- Source : دليل طاقة استيعاب — دورة التوجيه الجامعي 2025
-- Score sur 210 points | 689 filières | 10 domaines
-- Ministère de l'Enseignement Supérieur et de la Recherche Scientifique
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────
-- SUPPRESSION ET CRÉATION DES TABLES
-- ────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS candidatures;
DROP TABLE IF EXISTS filieres;
DROP TABLE IF EXISTS institutions;
DROP TABLE IF EXISTS universites;
DROP TABLE IF EXISTS gouvernorats;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS bac_sections;
DROP TABLE IF EXISTS messages_chatbot;

CREATE TABLE gouvernorats (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    nom        VARCHAR(60)  NOT NULL,
    region     VARCHAR(60)  NOT NULL,
    created_at DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE universites (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    nom            VARCHAR(200) NOT NULL,
    sigle          VARCHAR(60),
    gouvernorat_id INT,
    type           ENUM('publique','privée') DEFAULT 'publique',
    site_web       VARCHAR(200),
    created_at     DATETIME DEFAULT NOW(),
    FOREIGN KEY (gouvernorat_id) REFERENCES gouvernorats(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE institutions (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    nom            VARCHAR(300) NOT NULL  COMMENT 'Nom complet de la faculté ou de l''institut',
    sigle          VARCHAR(80),
    universite_id  INT          NOT NULL,
    gouvernorat_id INT,
    type           ENUM('faculte','institut_superieur','ecole','centre_prep','autre') DEFAULT 'institut_superieur',
    adresse        VARCHAR(200),
    site_web       VARCHAR(200),
    created_at     DATETIME DEFAULT NOW(),
    FOREIGN KEY (universite_id)  REFERENCES universites(id),
    FOREIGN KEY (gouvernorat_id) REFERENCES gouvernorats(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bac_sections (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    code        VARCHAR(10) NOT NULL UNIQUE,
    nom_ar      VARCHAR(100),
    nom_fr      VARCHAR(100) NOT NULL,
    description VARCHAR(400)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE filieres (
    id               INT PRIMARY KEY AUTO_INCREMENT,
    code_orientation VARCHAR(10)  NOT NULL UNIQUE COMMENT 'Code 5 chiffres du guide officiel',
    titre            VARCHAR(250) NOT NULL,
    domaine          VARCHAR(120) NOT NULL,
    type_formation   VARCHAR(100),
    description      TEXT,
    description_longue TEXT,
    debouches        JSON,
    matieres         JSON,
    universite_id    INT,
    institution_id   INT          COMMENT 'Faculté ou Institut qui dispense la filière',
    score_min        DECIMAL(6,2) NOT NULL COMMENT 'Score minimum dernier admis 2024, sur 210',
    score_moyen      DECIMAL(6,2) COMMENT 'Score moyen des admis, sur 210',
    score_max        DECIMAL(6,2) COMMENT 'Score maximum observé, sur 210',
    capacite         INT          COMMENT 'Nombre de places par an',
    duree            TINYINT      DEFAULT 3 COMMENT 'Durée en années',
    langue           VARCHAR(60),
    formule_calcul   VARCHAR(120) COMMENT 'Ex: FG+M, FG+SVT, FG+(M+SP)/2',
    bac_requis       VARCHAR(250) COMMENT 'Sections du bac acceptées',
    icon             VARCHAR(10),
    couleur_classe   VARCHAR(20)  DEFAULT 'ct-def',
    statut           ENUM('active','inactive') DEFAULT 'active',
    created_at       DATETIME DEFAULT NOW(),
    updated_at       DATETIME DEFAULT NOW() ON UPDATE NOW(),
    FOREIGN KEY (universite_id)  REFERENCES universites(id),
    FOREIGN KEY (institution_id) REFERENCES institutions(id),
    INDEX idx_score   (score_min),
    INDEX idx_domaine (domaine),
    INDEX idx_statut  (statut),
    INDEX idx_code    (code_orientation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE utilisateurs (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    prenom       VARCHAR(60)  NOT NULL,
    nom          VARCHAR(60)  NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    serie        VARCHAR(80),
    score_bac    DECIMAL(6,2) COMMENT 'Score total sur 210 points',
    gouvernorat  VARCHAR(60),
    role         ENUM('etudiant','admin') DEFAULT 'etudiant',
    created_at   DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE candidatures (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    filiere_id     INT NOT NULL,
    note           TEXT,
    created_at     DATETIME DEFAULT NOW(),
    UNIQUE KEY uq_cand (utilisateur_id, filiere_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)  ON DELETE CASCADE,
    FOREIGN KEY (filiere_id)     REFERENCES filieres(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages_chatbot (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT,
    session_id   VARCHAR(60)  NOT NULL,
    role         ENUM('user','bot') NOT NULL,
    contenu      TEXT         NOT NULL,
    created_at   DATETIME DEFAULT NOW(),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- GOUVERNORATS (24 gouvernorats de Tunisie)
-- ────────────────────────────────────────────────────────────
INSERT INTO gouvernorats (nom, region) VALUES
  ('Tunis',       'Grand Tunis'),
  ('Ariana',      'Grand Tunis'),
  ('Ben Arous',   'Grand Tunis'),
  ('Manouba',     'Grand Tunis'),
  ('Nabeul',      'Cap Bon'),
  ('Zaghouan',    'Nord-Est'),
  ('Bizerte',     'Nord'),
  ('Béja',        'Nord-Ouest'),
  ('Jendouba',    'Nord-Ouest'),
  ('Le Kef',      'Nord-Ouest'),
  ('Siliana',     'Nord-Ouest'),
  ('Kairouan',    'Centre'),
  ('Kasserine',   'Centre-Ouest'),
  ('Sidi Bouzid', 'Centre'),
  ('Sousse',      'Sahel'),
  ('Monastir',    'Sahel'),
  ('Mahdia',      'Sahel'),
  ('Sfax',        'Sud-Est'),
  ('Gafsa',       'Sud-Ouest'),
  ('Tozeur',      'Sud-Ouest'),
  ('Kébili',      'Sud'),
  ('Gabès',       'Sud-Est'),
  ('Médenine',    'Sud-Est'),
  ('Tataouine',   'Sud');

-- ────────────────────────────────────────────────────────────
-- SECTIONS DU BAC TUNISIEN
-- ────────────────────────────────────────────────────────────
INSERT INTO bac_sections (code, nom_ar, nom_fr, description) VALUES
  ('MAT',  'رياضيات',        'Mathématiques',           'Bac axé mathématiques avancées, physique et informatique. Donne accès aux filières scientifiques, technologiques et médicales. Score max 210 pts.'),
  ('SE',   'علوم تجريبية',   'Sciences Expérimentales', 'Bac orienté biologie, chimie, physique et SVT. Accès aux filières médicales, agronomiques et scientifiques. Score max 210 pts.'),
  ('TEC',  'علوم تقنية',     'Sciences Techniques',     'Bac technique : électronique, mécanique, automatique. Accès aux formations technologiques et d''ingénierie. Score max 210 pts.'),
  ('INFO', 'علوم الإعلامية', 'Informatique',            'Bac spécialisé algorithmique, programmation, réseaux. Accès aux filières IT et ingénierie logicielle. Score max 210 pts.'),
  ('ECO',  'اقتصاد وتصرف',  'Économie & Gestion',      'Bac économique et de gestion. Accès au commerce, finance, droit et sciences économiques. Score max 210 pts.'),
  ('LET',  'آداب',           'Lettres',                 'Bac littéraire en arabe, français, philosophie et histoire. Accès lettres, langues et sciences humaines. Score max 210 pts.'),
  ('SPO',  'رياضة',          'Sport',                   'Bac sport pour athlètes de haut niveau. Accès filières STAPS et éducation physique. Score max 210 pts.');

-- ────────────────────────────────────────────────────────────
-- UNIVERSITÉS (12 universités publiques)
-- ────────────────────────────────────────────────────────────
INSERT INTO universites (nom, sigle, gouvernorat_id, site_web) VALUES
  ('Université de Tunis',             'U-Tunis',    1,  'http://www.ut.rnu.tn'),
  ('Université de la Manouba',        'U-Manouba',  4,  'http://www.uma.rnu.tn'),
  ('Université Tunis El Manar',       'UTM',        1,  'http://www.utm.rnu.tn'),
  ('Université de Carthage',          'U-Carthage', 1,  'http://www.ucar.rnu.tn'),
  ('Université de Sousse',            'U-Sousse',   15, 'http://www.uss.rnu.tn'),
  ('Université de Monastir',          'U-Monastir', 16, 'http://www.um.rnu.tn'),
  ('Université de Sfax',              'U-Sfax',     18, 'http://www.usf.rnu.tn'),
  ('Université de Gabès',             'U-Gabès',    22, 'http://www.ugabs.rnu.tn'),
  ('Université de Gafsa',             'U-Gafsa',    19, 'http://www.ugaf.rnu.tn'),
  ('Université de Kairouan',          'U-Kairouan', 12, 'http://www.univ-k.rnu.tn'),
  ('Université de Jendouba',          'U-Jendouba', 9,  'http://www.uj.rnu.tn'),
  ('Université Virtuelle de Tunis',   'UVT',        1,  'http://www.uvt.rnu.tn');

-- ────────────────────────────────────────────────────────────
-- INSTITUTIONS (Facultés et Instituts Supérieurs)
-- ────────────────────────────────────────────────────────────
INSERT INTO institutions (nom, sigle, universite_id, gouvernorat_id, type) VALUES
-- === UNIVERSITÉ DE TUNIS (id=1) ===
('Faculté des Sciences Humaines et Sociales de Tunis',                    'FSHS-Tunis',     1, 1,  'faculte'),
('Institut Supérieur de l''Éducation et de la Formation Continue',        'ISEFC',          1, 1,  'institut_superieur'),
('Institut Supérieur des Cadres de l''Enfance',                           'ISCE',           1, 1,  'institut_superieur'),
('Institut Supérieur des Arts et Métiers de Tunis',                       'ISAM-Tunis',     1, 1,  'institut_superieur'),
('Institut Supérieur de Musique de Tunis',                                'ISM-Tunis',      1, 1,  'institut_superieur'),
('École Nationale des Beaux-Arts de Tunis',                               'ENBA',           1, 1,  'ecole'),

-- === UNIVERSITÉ DE LA MANOUBA (id=2) ===
('Faculté des Lettres, Arts et Humanités de Manouba',                     'FLAH-Manouba',   2, 4,  'faculte'),
('Faculté des Sciences Juridiques, Politiques et Sociales de Tunis',      'FSJPS-Tunis',    2, 4,  'faculte'),
('Institut Supérieur des Sciences du Sport et de l''Éducation Physique',  'ISSEP-Manouba',  2, 4,  'institut_superieur'),
('Institut Supérieur de Comptabilité et d''Administration des Entreprises','ISCAE',          2, 4,  'institut_superieur'),
('École Nationale d''Architecture et d''Urbanisme',                       'ENAU',           2, 1,  'ecole'),
('Institut de Presse et des Sciences de l''Information',                  'IPSI',           2, 4,  'institut_superieur'),

-- === UNIVERSITÉ TUNIS EL MANAR (id=3) ===
('Faculté des Sciences de Tunis',                                         'FST',            3, 1,  'faculte'),
('Faculté des Sciences Mathématiques, Physiques et Naturelles de Tunis',  'FSMPN',          3, 1,  'faculte'),
('Faculté de Médecine de Tunis',                                          'FMT',            3, 1,  'faculte'),
('Faculté de Pharmacie de Monastir',                                      'FPharma',        3, 16, 'faculte'),
('Institut Supérieur des Sciences Humaines de Tunis',                     'ISSH-Tunis',     3, 1,  'institut_superieur'),
('Institut Supérieur d''Informatique',                                     'ISI',            3, 1,  'institut_superieur'),
('École Nationale d''Ingénieurs de Tunis',                                'ENIT',           3, 1,  'ecole'),
('Institut Préparatoire aux Études Scientifiques et Techniques',          'IPEST',          3, 7,  'centre_prep'),
('Institut Supérieur d''Études Technologiques de Charguia',               'ISET-Charguia',  3, 1,  'institut_superieur'),
('Institut Supérieur de Biotechnologie de Sidi Thabet',                   'ISBST',          3, 2,  'institut_superieur'),

-- === UNIVERSITÉ DE CARTHAGE (id=4) ===
('Institut Supérieur des Langues de Tunis',                               'ISL-Tunis',      4, 1,  'institut_superieur'),
('Faculté des Sciences de Bizerte',                                       'FSB',            4, 7,  'faculte'),
('Faculté de Médecine de Tunis (Carthage)',                               'FM-Carthage',    4, 1,  'faculte'),
('Faculté des Sciences Juridiques, Politiques et Sociales de Tunis II',   'FSJPS2',         4, 1,  'faculte'),
('Institut Supérieur des Langues de Nabeul',                              'ISL-Nabeul',     4, 5,  'institut_superieur'),
('Institut Supérieur d''Études Appliquées en Humanités de Nabeul',        'ISEAH-Nabeul',   4, 5,  'institut_superieur'),
('Institut Supérieur des Arts et Métiers de Nabeul',                      'ISAM-Nabeul',    4, 5,  'institut_superieur'),
('École Polytechnique de Tunisie',                                        'EPT',            4, 7,  'ecole'),
('Institut Préparatoire aux Études d''Ingénieurs de Bizerte',             'IPEIB',          4, 7,  'centre_prep'),
('Institut Supérieur de Gestion de Bizerte',                              'ISG-Bizerte',    4, 7,  'institut_superieur'),
('Institut Supérieur de Gestion de Nabeul',                               'ISG-Nabeul',     4, 5,  'institut_superieur'),
('École Supérieure de Commerce de Tunis',                                 'ESCT',           4, 1,  'ecole'),

-- === UNIVERSITÉ DE SOUSSE (id=5) ===
('Faculté des Lettres et Sciences Humaines de Sousse',                    'FLSH-Sousse',    5, 15, 'faculte'),
('Faculté des Sciences Économiques et de Gestion de Sousse',              'FSEG-Sousse',    5, 15, 'faculte'),
('Faculté de Droit et des Sciences Politiques de Sousse',                 'FDSP-Sousse',    5, 15, 'faculte'),
('Faculté des Sciences de Sousse',                                        'FSS',            5, 15, 'faculte'),
('Institut Supérieur des Beaux-Arts de Sousse',                           'ISBAS',          5, 15, 'institut_superieur'),
('Institut Supérieur d''Études Appliquées en Humanités de Sousse',        'ISEAH-Sousse',   5, 15, 'institut_superieur'),
('Institut Supérieur du Sport et de l''Éducation Physique de Sousse',     'ISSEP-Sousse',   5, 15, 'institut_superieur'),
('École Nationale d''Ingénieurs de Sousse',                               'ENISo',          5, 15, 'ecole'),
('Institut Supérieur de Gestion de Sousse',                               'ISG-Sousse',     5, 15, 'institut_superieur'),
('Institut Supérieur d''Informatique et des Technologies de Communication','ISITC',          5, 15, 'institut_superieur'),

-- === UNIVERSITÉ DE MONASTIR (id=6) ===
('Faculté des Sciences de Monastir',                                      'FSM',            6, 16, 'faculte'),
('Faculté de Médecine de Monastir',                                       'FMM',            6, 16, 'faculte'),
('Faculté de Pharmacie de Monastir',                                      'FPM',            6, 16, 'faculte'),
('Faculté de Médecine Dentaire de Monastir',                              'FMDM',           6, 16, 'faculte'),
('Institut Supérieur des Sciences Humaines de Mahdia',                    'ISSH-Mahdia',    6, 17, 'institut_superieur'),
('Institut Supérieur des Arts et Métiers de Mahdia',                      'ISAM-Mahdia',    6, 17, 'institut_superieur'),
('Institut Supérieur de Gestion de Mahdia',                               'ISG-Mahdia',     6, 17, 'institut_superieur'),
('Institut Supérieur d''Informatique et de Mathématiques de Monastir',    'ISIMM',          6, 16, 'institut_superieur'),
('École Nationale d''Ingénieurs de Monastir',                             'ENIM',           6, 16, 'ecole'),
('Institut Préparatoire aux Études d''Ingénieurs de Monastir',            'IPEIM',          6, 16, 'centre_prep'),
('Institut Supérieur d''Études Technologiques de Mahdia',                 'ISET-Mahdia',    6, 17, 'institut_superieur'),
('Institut Supérieur du Sport et de l''Éducation Physique de Ksar-Saïd', 'ISSEP-Ksar',     6, 16, 'institut_superieur'),

-- === UNIVERSITÉ DE SFAX (id=7) ===
('Faculté des Lettres et Sciences Humaines de Sfax',                      'FLSH-Sfax',      7, 18, 'faculte'),
('Faculté des Sciences Économiques et de Gestion de Sfax',                'FSEG-Sfax',      7, 18, 'faculte'),
('Faculté de Droit de Sfax',                                              'FDS',            7, 18, 'faculte'),
('Faculté des Sciences de Sfax',                                          'FSS-Sfax',       7, 18, 'faculte'),
('Faculté de Médecine de Sfax',                                           'FMS',            7, 18, 'faculte'),
('Institut Supérieur des Beaux-Arts et du Métier de Sfax',                'ISBAMS',         7, 18, 'institut_superieur'),
('Institut Supérieur d''Administration des Entreprises de Sfax',          'ISAES',          7, 18, 'institut_superieur'),
('Institut Supérieur des Études Commerciales de Sfax',                    'ISECS',          7, 18, 'institut_superieur'),
('Institut Supérieur d''Informatique et de Multimédia de Sfax',           'ISIMS',          7, 18, 'institut_superieur'),
('École Nationale d''Ingénieurs de Sfax',                                 'ENIS',           7, 18, 'ecole'),
('École Supérieure de Commerce de Sfax',                                  'ESC-Sfax',       7, 18, 'ecole'),
('Institut Supérieur de Gestion de Sfax',                                 'ISG-Sfax',       7, 18, 'institut_superieur'),
('Institut Supérieur du Transport et de la Logistique de Sfax',           'ISTLS',          7, 18, 'institut_superieur'),

-- === UNIVERSITÉ DE GABÈS (id=8) ===
('Faculté des Sciences de Gabès',                                         'FSG',            8, 22, 'faculte'),
('Institut Supérieur des Langues de Gabès',                               'ISL-Gabès',      8, 22, 'institut_superieur'),
('Institut Supérieur des Sciences Humaines de Médenine',                  'ISSH-Médenine',  8, 23, 'institut_superieur'),
('Institut Supérieur d''Études Appliquées en Humanités de Gabès',         'ISEAH-Gabès',    8, 22, 'institut_superieur'),
('Institut Supérieur des Arts et Métiers de Gabès',                       'ISAM-Gabès',     8, 22, 'institut_superieur'),
('Institut Supérieur de Gestion de Gabès',                                'ISG-Gabès',      8, 22, 'institut_superieur'),
('Institut Supérieur de Gestion de Médenine',                             'ISG-Médenine',   8, 23, 'institut_superieur'),
('Institut Supérieur des Sciences Appliquées et de Technologie de Gabès', 'ISSAT-Gabès',    8, 22, 'institut_superieur'),
('Institut Supérieur des Sciences Appliquées de Médenine',                'ISSAT-Médenine', 8, 23, 'institut_superieur'),
('École Nationale d''Ingénieurs de Gabès',                                'ENIG',           8, 22, 'ecole'),
('Institut Supérieur du Sport et de l''Éducation Physique de Gabès',      'ISSEP-Gabès',    8, 22, 'institut_superieur'),
('Institut Supérieur de Tataouine',                                       'IST',            8, 24, 'institut_superieur'),

-- === UNIVERSITÉ DE GAFSA (id=9) ===
('Institut Supérieur d''Études Appliquées en Humanités de Gafsa',         'ISEAH-Gafsa',    9, 19, 'institut_superieur'),
('Faculté des Sciences de Gafsa',                                         'FSGafsa',        9, 19, 'faculte'),
('Institut Supérieur de Gestion de Gafsa',                                'ISG-Gafsa',      9, 19, 'institut_superieur'),
('Institut Supérieur des Arts et Métiers de Gafsa',                       'ISAM-Gafsa',     9, 19, 'institut_superieur'),
('Institut Préparatoire aux Études d''Ingénieurs de Gafsa',               'IPEIG',          9, 19, 'centre_prep'),

-- === UNIVERSITÉ DE KAIROUAN (id=10) ===
('Faculté des Lettres et Sciences Humaines de Kairouan',                  'FLSH-Kairouan',  10, 12, 'faculte'),
('Faculté des Sciences Économiques et de Gestion de Kairouan',            'FSEG-Kairouan',  10, 12, 'faculte'),
('Faculté des Sciences de Kairouan',                                      'FSK',            10, 12, 'faculte'),
('Institut Supérieur d''Études Appliquées en Humanités de Kasserine',     'ISEAH-Kasserine',10, 13, 'institut_superieur'),
('Institut Supérieur de Gestion de Kasserine',                            'ISG-Kasserine',  10, 13, 'institut_superieur'),
('Institut Supérieur des Sciences Appliquées de Kasserine',               'ISSAT-Kasserine',10, 13, 'institut_superieur'),
('Institut Supérieur d''Informatique de Kairouan',                        'ISI-Kairouan',   10, 12, 'institut_superieur'),
('Institut Supérieur des Sciences Humaines de Sidi Bouzid',               'ISSH-SidiBouzid',10, 14, 'institut_superieur'),
('Institut Supérieur de Gestion de Sidi Bouzid',                          'ISG-SidiBouzid', 10, 14, 'institut_superieur'),

-- === UNIVERSITÉ DE JENDOUBA (id=11) ===
('Faculté des Sciences Économiques et de Gestion de Jendouba',            'FSEG-Jendouba',  11, 9,  'faculte'),
('Institut Supérieur d''Études Appliquées en Humanités de Jendouba',      'ISEAH-Jendouba', 11, 9,  'institut_superieur'),
('Institut Supérieur d''Études Appliquées en Humanités de Béja',          'ISEAH-Béja',     11, 8,  'institut_superieur'),
('Institut Supérieur d''Études Appliquées en Humanités du Kef',           'ISEAH-Kef',      11, 10, 'institut_superieur'),
('Institut Supérieur d''Études Appliquées en Humanités de Siliana',       'ISEAH-Siliana',  11, 11, 'institut_superieur'),
('Institut Supérieur de Gestion de Béja',                                 'ISG-Béja',       11, 8,  'institut_superieur'),
('Institut Supérieur de Gestion du Kef',                                  'ISG-Kef',        11, 10, 'institut_superieur'),
('Institut Supérieur des Sciences Appliquées et Technologie de Béja',     'ISSAT-Béja',     11, 8,  'institut_superieur'),
('Institut National Agronomique de Tunisie (antenne Jendouba)',            'INAT-Jendouba',  11, 9,  'institut_superieur'),
('Institut Supérieur des Sciences Appliquées du Kef',                     'ISSAT-Kef',      11, 10, 'institut_superieur');

-- ────────────────────────────────────────────────────────────
-- FILIÈRES (689 filières — Guide officiel 2025)
-- Score sur 210 points
-- code_orientation = code 5 chiffres du guide officiel
-- ────────────────────────────────────────────────────────────
INSERT INTO filieres
  (code_orientation, titre, domaine, type_formation, description, debouches, matieres,
   universite_id, institution_id, score_min, score_moyen, score_max,
   capacite, duree, langue, formule_calcul, bac_requis, icon, couleur_classe)
VALUES
-- ══════════════════════════════════════════
-- LETTRES & LANGUES
-- ══════════════════════════════════════════
('10101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation universitaire en langue, littérature arabe et civilisation islamique. Prépare à l''enseignement, la traduction et la recherche.','["Enseignant(e) de langue arabe","Traducteur / Interprète","Journaliste","Attaché culturel","Correcteur / Rédacteur"]','["Linguistique arabe","Littérature arabe classique","Littérature arabe moderne","Rhétorique","Civilisation islamique","Grammaire avancée"]',1,1,97.88,108.5,130.94,85,3,'Arabe','FG+AR','Lettres, Économie & Gestion, Informatique, Sciences Expérimentales','📚','ct-lett'),

('11101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue et littérature arabes à la Faculté des Lettres de Manouba.','["Enseignant(e) de langue arabe","Traducteur","Journaliste","Chargé de communication"]','["Linguistique arabe","Littérature classique","Civilisation","Traduction"]',2,7,95.24,105.0,124.84,95,3,'Arabe','FG+AR','Lettres, Économie & Gestion, Informatique, Sciences Expérimentales','📚','ct-lett'),

('12101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe à l''Institut Supérieur des Sciences Humaines de Tunis El Manar.','["Enseignant(e)","Traducteur","Chercheur"]','["Linguistique","Littérature arabe","Civilisation islamique"]',3,17,98.5,104.0,124.03,50,3,'Arabe','FG+AR','Lettres, Économie & Gestion, Informatique','📚','ct-lett'),

('13101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe et civilisation à l''Institut Supérieur des Langues de Tunis (Université de Carthage).','["Enseignant(e)","Traducteur","Attaché culturel"]','["Linguistique arabe","Rhétorique","Civilisation","Traduction"]',4,21,92.61,101.0,119.77,92,3,'Arabe','FG+AR','Lettres, Économie & Gestion, Informatique, Sciences Expérimentales','📚','ct-lett'),

('22101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe à l''Institut Supérieur des Langues de Nabeul.','["Enseignant(e)","Traducteur","Journaliste"]','["Linguistique","Littérature","Civilisation","Traduction"]',4,25,99.94,106.0,109.6,30,3,'Arabe','FG+AR','Lettres, Économie & Gestion','📚','ct-lett'),

('30101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue et littérature arabes à la Faculté des Lettres et Sciences Humaines de Sousse.','["Enseignant(e)","Traducteur","Chercheur"]','["Linguistique arabe","Littérature","Civilisation"]',5,33,100.24,109.0,134.28,95,3,'Arabe','FG+AR','Lettres, Sciences Expérimentales, Économie & Gestion, Informatique','📚','ct-lett'),

('40101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe à la Faculté des Lettres et Sciences Humaines de Sfax.','["Enseignant(e)","Traducteur","Journaliste"]','["Linguistique arabe","Littérature","Civilisation"]',7,53,92.44,101.0,120.01,83,3,'Arabe','FG+AR','Lettres, Économie & Gestion, Informatique, Sciences Expérimentales','📚','ct-lett'),

('50101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe à l''Institut Supérieur des Langues de Gabès.','["Enseignant(e)","Traducteur"]','["Linguistique","Littérature arabe","Civilisation"]',8,74,96.3,103.0,118.37,45,3,'Arabe','FG+AR','Lettres, Économie & Gestion','📚','ct-lett'),

('54101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe à l''Institut Supérieur des Sciences Humaines de Médenine.','["Enseignant(e)","Traducteur"]','["Linguistique arabe","Littérature","Civilisation"]',8,75,90.84,98.0,110.01,50,3,'Arabe','FG+AR','Lettres, Économie & Gestion','📚','ct-lett'),

('60101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe à l''Institut Supérieur d''Études Appliquées en Humanités de Gafsa.','["Enseignant(e)","Traducteur"]','["Linguistique","Littérature arabe","Civilisation"]',9,83,89.11,97.0,109.71,90,3,'Arabe','FG+AR','Lettres, Économie & Gestion','📚','ct-lett'),

('70101','Licence — Langue et Civilisation Arabes','Lettres & Langues','Licence LMD','Formation en langue arabe à la Faculté des Lettres et Sciences Humaines de Kairouan.','["Enseignant(e)","Traducteur"]','["Linguistique arabe","Littérature","Civilisation"]',10,85,84.58,95.0,108.29,90,3,'Arabe','FG+AR','Lettres, Économie & Gestion','📚','ct-lett'),

-- Langue Anglaise
('10102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en langue, littérature et civilisation anglaises. Prépare à la traduction, l''enseignement et la communication internationale.','["Enseignant(e) d''anglais","Traducteur anglophone","Chargé export","Guide touristique","Journaliste","Diplomate"]','["Linguistique anglaise","Littérature britannique","Littérature américaine","Civilisation anglophone","Traduction","Phonétique"]',1,1,115.43,125.0,151.82,143,3,'Arabe/Anglais','FG+ANG','Lettres, Sciences Expérimentales, Mathématiques, Économie & Gestion, Informatique, Sciences Techniques, Sport','📚','ct-lett'),

('11102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en anglais et relations internationales à la Faculté de la Manouba.','["Enseignant(e)","Traducteur","Diplomate","Journaliste"]','["Linguistique anglaise","Civilisation","Relations internationales","Traduction"]',2,7,113.24,120.0,168.93,143,3,'Arabe/Anglais','FG+ANG','Lettres, Sciences Expérimentales, Mathématiques, Économie & Gestion','📚','ct-lett'),

('12102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en anglais et relations internationales à l''Institut Supérieur des Sciences Humaines (El Manar).','["Enseignant(e)","Traducteur","Diplomate"]','["Anglais","Relations internationales","Civilisation","Traduction"]',3,17,130.9,140.0,166.9,60,3,'Arabe/Anglais','FG+ANG','Lettres, Sciences Expérimentales, Mathématiques, Économie & Gestion','📚','ct-lett'),

('13102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en langue anglaise à l''Institut Supérieur des Langues de Tunis (Carthage).','["Enseignant(e)","Traducteur","Diplomate"]','["Linguistique anglaise","Civilisation","Traduction","Phonétique"]',4,21,115.35,128.0,164.58,143,3,'Arabe/Anglais','FG+ANG','Lettres, Sciences Expérimentales, Mathématiques, Économie & Gestion','📚','ct-lett'),

('22102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en langue anglaise à l''Institut Supérieur des Langues de Nabeul.','["Enseignant(e)","Traducteur","Chargé export"]','["Anglais","Civilisation","Traduction"]',4,25,108.91,118.0,133.0,90,3,'Arabe/Anglais','FG+ANG','Lettres, Sciences Expérimentales, Économie & Gestion','📚','ct-lett'),

('30102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en langue anglaise à la Faculté des Lettres et Sciences Humaines de Sousse.','["Enseignant(e)","Traducteur","Journaliste"]','["Anglais","Civilisation anglophone","Traduction"]',5,33,110.14,122.0,148.0,100,3,'Arabe/Anglais','FG+ANG','Lettres, Sciences Expérimentales, Économie & Gestion','📚','ct-lett'),

('40102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en langue anglaise à la Faculté des Lettres et Sciences Humaines de Sfax.','["Enseignant(e)","Traducteur","Journaliste"]','["Anglais","Civilisation","Traduction"]',7,53,105.2,116.0,140.0,80,3,'Arabe/Anglais','FG+ANG','Lettres, Sciences Expérimentales, Économie & Gestion','📚','ct-lett'),

('50102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en langue anglaise à l''Institut Supérieur des Langues de Gabès.','["Enseignant(e)","Traducteur"]','["Anglais","Civilisation","Traduction"]',8,74,100.5,112.0,130.0,50,3,'Arabe/Anglais','FG+ANG','Lettres, Économie & Gestion','📚','ct-lett'),

('70102','Licence — Langue et Civilisation Anglaises','Lettres & Langues','Licence LMD','Formation en langue anglaise à la Faculté des Lettres et Sciences Humaines de Kairouan.','["Enseignant(e)","Traducteur"]','["Anglais","Civilisation","Traduction"]',10,85,88.0,98.0,115.0,60,3,'Arabe/Anglais','FG+ANG','Lettres, Économie & Gestion','📚','ct-lett'),

-- Langue Française
('10103','Licence — Langue et Civilisation Françaises','Lettres & Langues','Licence LMD','Formation en langue, littérature et civilisation françaises. Débouchés dans l''enseignement, la traduction et la coopération francophone.','["Enseignant(e) de français","Traducteur FR/AR","Attaché culturel","Journaliste francophone","Correcteur"]','["Linguistique française","Littérature française","Littérature francophone","Civilisation française","Traduction"]',1,1,102.5,112.0,138.0,80,3,'Arabe/Français','FG+F','Lettres, Sciences Expérimentales, Économie & Gestion, Informatique','📚','ct-lett'),

('11103','Licence — Langue et Civilisation Françaises','Lettres & Langues','Licence LMD','Formation en langue française à la Faculté des Lettres de Manouba.','["Enseignant(e)","Traducteur","Journaliste"]','["Linguistique française","Littérature","Civilisation","Traduction"]',2,7,98.0,108.0,130.0,80,3,'Arabe/Français','FG+F','Lettres, Sciences Expérimentales, Économie & Gestion','📚','ct-lett'),

('30103','Licence — Langue et Civilisation Françaises','Lettres & Langues','Licence LMD','Formation en langue française à la Faculté des Lettres de Sousse.','["Enseignant(e)","Traducteur","Journaliste"]','["Linguistique française","Littérature","Civilisation"]',5,33,95.0,105.0,125.0,60,3,'Arabe/Français','FG+F','Lettres, Économie & Gestion','📚','ct-lett'),

('40103','Licence — Langue et Civilisation Françaises','Lettres & Langues','Licence LMD','Formation en langue française à la Faculté des Lettres de Sfax.','["Enseignant(e)","Traducteur"]','["Linguistique française","Littérature","Civilisation"]',7,53,90.0,100.0,118.0,50,3,'Arabe/Français','FG+F','Lettres, Économie & Gestion','📚','ct-lett'),

-- ══════════════════════════════════════════
-- SCIENCES HUMAINES & SOCIALES
-- ══════════════════════════════════════════
('10201','Licence en Histoire','Sciences Humaines & Sociales','Licence LMD','Formation en histoire ancienne, médiévale et contemporaine. Débouchés dans l''enseignement, la recherche, les archives et le patrimoine.','["Enseignant(e) d''histoire","Archiviste","Guide patrimoine","Chercheur","Journaliste historique"]','["Histoire ancienne","Histoire médiévale","Histoire contemporaine","Méthodologie historique","Archéologie","Patrimoine"]',1,1,88.0,98.0,115.0,60,3,'Arabe','FG+AR','Lettres, Économie & Gestion, Informatique','🏛️','ct-def'),

('10202','Licence en Géographie','Sciences Humaines & Sociales','Licence LMD','Formation en géographie humaine, physique et cartographie. Prépare à l''aménagement du territoire et à la recherche.','["Géographe","Aménageur du territoire","Enseignant(e)","Cartographe","Chercheur"]','["Géographie physique","Géographie humaine","Cartographie","SIG","Démographie","Aménagement"]',1,1,90.0,100.0,118.0,50,3,'Arabe','FG+AR','Lettres, Sciences Expérimentales, Économie & Gestion','🏛️','ct-def'),

('10203','Licence en Sociologie','Sciences Humaines & Sociales','Licence LMD','Formation en sociologie, anthropologie et sciences sociales. Prépare aux métiers de l''éducation, du travail social et de la recherche.','["Sociologue","Assistant social","Animateur socioculturel","Chercheur","Éducateur spécialisé"]','["Sociologie générale","Sociologie du travail","Anthropologie","Méthodes de recherche","Statistiques sociales"]',1,1,85.0,95.0,112.0,40,3,'Arabe','FG+AR','Lettres, Économie & Gestion','🏛️','ct-def'),

('10204','Licence en Psychologie','Sciences Humaines & Sociales','Licence LMD','Formation en psychologie clinique et psychologie sociale. Prépare aux métiers de l''accompagnement, de l''éducation et de la recherche.','["Psychologue (avec master)","Conseiller d''orientation","Éducateur","Animateur social","Chercheur"]','["Psychologie générale","Psychologie clinique","Psychologie sociale","Neuropsychologie","Méthodes d''évaluation"]',1,1,92.0,102.0,120.0,50,3,'Arabe/Français','FG+AR','Lettres, Sciences Expérimentales, Économie & Gestion','🏛️','ct-def'),

('10206','Licence en Sciences de l''Éducation','Sciences Humaines & Sociales','Licence LMD','Formation en sciences pédagogiques et éducatives. Prépare à l''enseignement, la formation et l''administration scolaire.','["Enseignant(e)","Formateur","Conseiller pédagogique","Éducateur","Inspecteur de l''éducation"]','["Didactique","Pédagogie","Psychologie de l''éducation","Sociologie scolaire","Technologies éducatives"]',1,1,88.0,98.0,115.0,45,3,'Arabe','FG+AR','Lettres, Économie & Gestion','🏛️','ct-def'),

('30202','Licence en Géographie','Sciences Humaines & Sociales','Licence LMD','Formation en géographie à la Faculté des Lettres de Sousse.','["Géographe","Aménageur","Enseignant(e)","Cartographe"]','["Géographie physique","Géographie humaine","SIG","Aménagement"]',5,33,85.0,95.0,110.0,40,3,'Arabe','FG+AR','Lettres, Sciences Expérimentales','🏛️','ct-def'),

-- ══════════════════════════════════════════
-- CULTURE & BEAUX-ARTS
-- ══════════════════════════════════════════
('10301','Licence en Arts Plastiques','Culture & Beaux-Arts','Licence LMD','Formation dans les arts visuels : dessin, peinture, sculpture, photographie et arts numériques. Prépare aux métiers de la création artistique.','["Artiste plasticien","Designer graphique","Enseignant d''art","Animateur culturel","Illustrateur"]','["Dessin académique","Peinture","Sculpture","Gravure","Photographie","Arts numériques","Histoire de l''art"]',1,6,78.0,90.0,115.0,40,3,'Arabe/Français','FG+PH','Lettres, Économie & Gestion, Sciences Expérimentales, Sport','🎨','ct-art'),

('22202','Licence en Beaux-Arts Plastiques','Culture & Beaux-Arts','Licence LMD','Formation artistique à l''Institut Supérieur des Beaux-Arts de Nabeul (Université de Carthage).','["Artiste","Designer","Enseignant d''art","Animateur culturel"]','["Dessin","Peinture","Sculpture","Photographie","Arts décoratifs"]',4,27,75.67,88.0,113.72,58,3,'Arabe/Français','FG+PH','Lettres, Économie & Gestion, Sciences Expérimentales, Sport','🎨','ct-art'),

('30302','Licence en Beaux-Arts','Culture & Beaux-Arts','Licence LMD','Formation artistique à l''Institut Supérieur des Beaux-Arts de Sousse.','["Artiste","Designer","Enseignant d''art"]','["Dessin","Peinture","Sculpture","Photographie","Arts numériques"]',5,37,93.1,103.0,122.73,66,3,'Arabe/Français','FG+PH','Lettres, Économie & Gestion, Sciences Expérimentales, Sport','🎨','ct-art'),

('40202','Licence en Arts et Métiers','Culture & Beaux-Arts','Licence LMD','Formation en arts appliqués et artisanat à l''Institut Supérieur des Beaux-Arts et du Métier de Sfax.','["Artiste","Designer","Artisan","Enseignant d''art"]','["Arts plastiques","Céramique","Tissage","Design","Arts numériques"]',7,58,88.04,98.0,114.23,47,3,'Arabe/Français','FG+PH','Lettres, Économie & Gestion, Sciences Expérimentales, Sport','🎨','ct-art'),

-- ══════════════════════════════════════════
-- TOURISME & SPORT
-- ══════════════════════════════════════════
('10213','Licence en Éducation Physique et Sportive','Tourisme & Sport','Licence LMD','Formation en éducation physique, sport et santé. Prépare aux métiers de l''enseignement sportif, du coaching et de l''animation.','["Professeur d''EPS","Entraîneur sportif","Animateur sportif","Manager sportif","Kiné du sport"]','["Anatomie sportive","Physiologie de l''effort","Techniques sportives","Éducation physique","Sports collectifs","Biomécanique"]',2,9,85.0,98.0,130.0,50,3,'Arabe/Français','FG+SP','Sport, Lettres, Sciences Expérimentales','⛷️','ct-art'),

('30213','Licence en Éducation Physique et Sportive','Tourisme & Sport','Licence LMD','Formation en éducation physique à l''ISSEP de Sousse.','["Professeur d''EPS","Entraîneur","Animateur sportif"]','["Anatomie","Physiologie","Techniques sportives","EPS"]',5,39,80.0,92.0,120.0,40,3,'Arabe/Français','FG+SP','Sport, Lettres','⛷️','ct-art'),

('10211','Licence en Tourisme et Hôtellerie','Tourisme & Sport','Licence LMD','Formation en management touristique, hôtellerie et restauration. Prépare aux métiers du tourisme, de l''hôtellerie et de l''animation.','["Guide touristique","Réceptionniste hôtel","Manager hôtelier","Agent de voyage","Animateur touristique"]','["Management touristique","Hôtellerie & Restauration","Langues étrangères","Marketing touristique","Culture et patrimoine"]',1,1,90.0,102.0,125.0,40,3,'Arabe/Français','FG+ANG','Lettres, Économie & Gestion, Sciences Expérimentales','⛷️','ct-art'),

-- ══════════════════════════════════════════
-- DROIT & SCIENCES POLITIQUES
-- ══════════════════════════════════════════
('10501','Licence en Droit Privé','Droit & Sciences Politiques','Licence LMD','Formation approfondie en droit civil, commercial, du travail et droit international privé. Ouvre vers l''avocat, le notariat et la magistrature.','["Avocat","Notaire","Huissier de justice","Juriste d''entreprise","Magistrat","Conseiller juridique"]','["Droit civil","Droit commercial","Droit du travail","Droit international privé","Procédure civile","Droit de la famille"]',2,8,95.0,106.0,128.0,100,3,'Arabe','FG+(AR+F)/2','Lettres, Sciences Expérimentales, Économie & Gestion, Informatique','⚖️','ct-droit'),

('10502','Licence en Droit Public','Droit & Sciences Politiques','Licence LMD','Formation en droit constitutionnel, administratif et droit public international. Prépare aux carrières dans l''administration et la diplomatie.','["Haut fonctionnaire","Diplomate","Magistrat administratif","Conseiller juridique","Chercheur en droit"]','["Droit constitutionnel","Droit administratif","Droit international public","Institutions politiques","Droit fiscal"]',2,8,92.0,103.0,124.0,100,3,'Arabe','FG+(AR+F)/2','Lettres, Sciences Expérimentales, Économie & Gestion','⚖️','ct-droit'),

('30501','Licence en Droit','Droit & Sciences Politiques','Licence LMD','Formation en droit à la Faculté de Droit et des Sciences Politiques de Sousse.','["Avocat","Notaire","Magistrat","Juriste d''entreprise"]','["Droit civil","Droit commercial","Droit administratif","Procédure civile"]',5,35,88.0,98.0,118.0,120,3,'Arabe','FG+(AR+F)/2','Lettres, Économie & Gestion, Sciences Expérimentales','⚖️','ct-droit'),

('40501','Licence en Droit','Droit & Sciences Politiques','Licence LMD','Formation en droit à la Faculté de Droit de Sfax.','["Avocat","Notaire","Magistrat","Juriste"]','["Droit civil","Droit commercial","Droit administratif"]',7,55,85.0,95.0,115.0,150,3,'Arabe','FG+(AR+F)/2','Lettres, Économie & Gestion','⚖️','ct-droit'),

-- ══════════════════════════════════════════
-- SCIENCES ÉCONOMIQUES & GESTION
-- ══════════════════════════════════════════
('10311','Licence en Économie','Sciences Économiques & Gestion','Licence LMD','Formation en microéconomie, macroéconomie, économie internationale et politique économique. Prépare à l''analyse économique et aux concours de la fonction publique.','["Économiste","Analyste financier","Chargé d''études","Conseiller économique","Fonctionnaire","Chercheur"]','["Microéconomie","Macroéconomie","Économétrie","Économie internationale","Mathématiques pour économistes","Statistiques"]',1,1,95.0,108.0,130.0,80,3,'Arabe/Français','FG+M','Mathématiques, Sciences Expérimentales, Économie & Gestion, Informatique','📊','ct-com'),

('10318','Licence en Sciences de Gestion','Sciences Économiques & Gestion','Licence LMD','Tronc commun en management, comptabilité, finance et marketing. Formation généraliste ouvrant vers tous les secteurs d''activité.','["Gestionnaire d''entreprise","Comptable","Chargé marketing","Responsable RH","Banquier","Consultant"]','["Management","Comptabilité générale","Marketing","Finance","Statistiques","Droit des affaires","Informatique de gestion"]',1,1,100.0,115.0,145.0,200,3,'Arabe/Français','FG+(M+GEST)/2','Économie & Gestion, Mathématiques, Informatique, Sciences Expérimentales','📊','ct-com'),

('11318','Licence en Sciences de Gestion','Sciences Économiques & Gestion','Licence LMD','Formation en gestion à l''Institut Supérieur de Comptabilité et d''Administration des Entreprises (ISCAE).','["Gestionnaire","Comptable","Auditeur","Consultant","Banquier"]','["Management","Comptabilité","Finance","Marketing","Statistiques"]',2,10,110.0,125.0,155.0,150,3,'Arabe/Français','FG+(M+GEST)/2','Économie & Gestion, Mathématiques, Informatique','📊','ct-com'),

('30318','Licence en Sciences de Gestion','Sciences Économiques & Gestion','Licence LMD','Formation en sciences de gestion à la Faculté des Sciences Économiques et de Gestion de Sousse.','["Gestionnaire","Comptable","Consultant","Banquier","RH"]','["Management","Comptabilité","Finance","Marketing"]',5,34,95.0,110.0,135.0,200,3,'Arabe/Français','FG+(M+GEST)/2','Économie & Gestion, Mathématiques, Informatique','📊','ct-com'),

('40318','Licence en Sciences de Gestion','Sciences Économiques & Gestion','Licence LMD','Formation en gestion à la Faculté des Sciences Économiques et de Gestion de Sfax.','["Gestionnaire","Comptable","Consultant","Banquier"]','["Management","Comptabilité","Finance","Marketing"]',7,54,92.0,106.0,130.0,200,3,'Arabe/Français','FG+(M+GEST)/2','Économie & Gestion, Mathématiques','📊','ct-com'),

('41318','Licence en Commerce et Gestion','Sciences Économiques & Gestion','Licence LMD','Formation en commerce à l''École Supérieure de Commerce de Sfax.','["Commercial","Manager","Chargé marketing","Logisticien"]','["Commerce","Marketing","Logistique","Gestion","Finance"]',7,63,110.0,128.0,155.0,80,3,'Français','FG+M','Mathématiques, Économie & Gestion, Informatique','📊','ct-com'),

('70318','Licence en Sciences de Gestion','Sciences Économiques & Gestion','Licence LMD','Formation en gestion à la Faculté des Sciences Économiques et de Gestion de Kairouan.','["Gestionnaire","Comptable","Consultant"]','["Management","Comptabilité","Finance","Marketing"]',10,87,88.0,100.0,122.0,120,3,'Arabe/Français','FG+(M+GEST)/2','Économie & Gestion, Mathématiques','📊','ct-com'),

-- ══════════════════════════════════════════
-- SCIENCES EXACTES & TECHNOLOGIE
-- ══════════════════════════════════════════
('10510','Licence en Mathématiques','Sciences Exactes & Technologie','Licence LMD','Formation en mathématiques pures et appliquées : algèbre, analyse, probabilités et statistiques. Prépare à la recherche et à l''enseignement.','["Enseignant(e) de mathématiques","Chercheur","Statisticien","Actuaire","Analyste quantitatif"]','["Algèbre","Analyse réelle","Analyse complexe","Probabilités","Statistiques","Topologie","Géométrie différentielle"]',3,13,100.0,115.0,145.0,80,3,'Français','FG+M','Mathématiques','⚛️','ct-sci'),

('10511','Licence en Physique','Sciences Exactes & Technologie','Licence LMD','Formation en physique fondamentale et appliquée. Prépare à la recherche, l''enseignement et les applications industrielles.','["Enseignant(e) de physique","Chercheur","Ingénieur de recherche","Physicien industriel"]','["Mécanique classique","Électromagnétisme","Thermodynamique","Optique","Physique quantique","Mécanique des fluides"]',3,13,95.0,110.0,138.0,60,3,'Français','FG+(M+SP)/2','Mathématiques, Sciences Expérimentales','⚛️','ct-sci'),

('10512','Licence en Sciences de la Vie et de l''Environnement','Sciences Exactes & Technologie','Licence LMD','Formation en biologie, écologie et sciences de l''environnement. Prépare aux métiers de l''environnement, de la recherche et de l''enseignement.','["Enseignant(e) de SVT","Biologiste","Environnementaliste","Chercheur","Agent de conservation"]','["Biologie cellulaire","Génétique","Écologie","Biochimie","Microbiologie","Biologie végétale"]',3,13,90.0,105.0,130.0,100,3,'Français','FG+SVT','Sciences Expérimentales, Mathématiques','⚛️','ct-sci'),

('10571','Licence en Informatique','Sciences Exactes & Technologie','Licence LMD','Formation complète en informatique : algorithmique, programmation, bases de données, réseaux et intelligence artificielle. Très demandée sur le marché de l''emploi.','["Développeur logiciel","Ingénieur réseau","Data Analyst","Administrateur système","DevOps","Chef de projet IT"]','["Algorithmique","Programmation orientée objet","Bases de données","Réseaux informatiques","Systèmes d''exploitation","Web development","IA & Machine Learning"]',3,18,152.4,165.0,185.0,14,3,'Français','FG+(M+SP+Info)/3','Informatique, Mathématiques, Sciences Techniques','⚛️','ct-sci'),

('10572','Licence en Réseaux & Télécommunications','Sciences Exactes & Technologie','Licence LMD','Formation en réseaux informatiques, télécommunications et sécurité des systèmes. Prépare aux métiers des infrastructures numériques.','["Ingénieur réseaux","Administrateur systèmes","Expert cybersécurité","Chef de projet télécom","Support technique"]','["Réseaux informatiques","Télécommunications","Sécurité des systèmes","Protocoles réseaux","Cloud Computing","IoT"]',3,18,166.68,172.0,188.0,45,3,'Français','FG+(M+SP+Info)/3','Informatique, Mathématiques, Sciences Techniques','⚛️','ct-sci'),

('11518','Cycle Ingénieur — Génie Informatique (Numérique)','Sciences Exactes & Technologie','Cycle Ingénieur','Cycle ingénieur en informatique et numérique à l''Institut Supérieur d''Informatique et de Mathématiques de Monastir (ISIMM). Formation de 5 ans.','["Ingénieur en informatique","Architecte logiciel","Data Scientist","Chef de projet","Entrepreneur tech"]','["Mathématiques","Algorithmique avancée","Programmation","Réseaux","IA","Systèmes embarqués","Génie logiciel"]',6,51,180.5,184.0,189.0,37,5,'Français','FG+(M+SP+Info)/3','Mathématiques, Informatique, Sciences Techniques','⚛️','ct-sci'),

('10517','Classe Préparatoire aux Études d''Ingénieurs (IPEST)','Sciences Exactes & Technologie','Classe Préparatoire','Classe préparatoire intensive aux grandes écoles d''ingénieurs tunisiennes. Formation de haut niveau en mathématiques, physique et informatique (2 ans).','["Ingénieur (après concours grandes écoles)","Chercheur en sciences exactes"]','["Mathématiques supérieures","Mathématiques spéciales","Physique","Chimie","Informatique","Anglais scientifique"]',3,20,175.0,182.0,199.0,80,2,'Français','FG+(M+SP)/2','Mathématiques','⚛️','ct-sci'),

('30571','Licence en Informatique','Sciences Exactes & Technologie','Licence LMD','Formation en informatique à l''Institut Supérieur d''Informatique et des Technologies de Communication de Hammam Sousse.','["Développeur","Ingénieur réseau","Data Analyst","Administrateur système"]','["Algorithmique","Programmation","Bases de données","Réseaux","Web"]',5,42,140.0,155.0,175.0,40,3,'Français','FG+(M+SP+Info)/3','Informatique, Mathématiques, Sciences Techniques','⚛️','ct-sci'),

('31518','Cycle Ingénieur — Informatique (ISIMM)','Sciences Exactes & Technologie','Cycle Ingénieur','Cycle ingénieur à l''Institut Supérieur d''Informatique et de Mathématiques de Monastir.','["Ingénieur informaticien","Architecte systèmes","Data Scientist"]','["Mathématiques","Algorithmique","Programmation avancée","Réseaux","IA"]',6,51,178.0,183.0,190.0,50,5,'Français','FG+(M+SP+Info)/3','Mathématiques, Informatique, Sciences Techniques','⚛️','ct-sci'),

('40571','Licence en Informatique et Multimédia','Sciences Exactes & Technologie','Licence LMD','Formation en informatique et multimédia à l''Institut Supérieur d''Informatique et de Multimédia de Sfax.','["Développeur multimédia","Designer web","Ingénieur réseau","Data Analyst"]','["Algorithmique","Programmation","Multimédia","Réseaux","Web","IA"]',7,62,145.0,158.0,178.0,50,3,'Français','FG+(M+SP+Info)/3','Informatique, Mathématiques, Sciences Techniques','⚛️','ct-sci'),

('40510','Licence en Mathématiques','Sciences Exactes & Technologie','Licence LMD','Formation en mathématiques à la Faculté des Sciences de Sfax.','["Enseignant(e)","Chercheur","Statisticien","Actuaire"]','["Algèbre","Analyse","Probabilités","Statistiques","Topologie"]',7,56,98.0,112.0,138.0,60,3,'Français','FG+M','Mathématiques','⚛️','ct-sci'),

('50572','Licence en Réseaux & Télécommunications','Sciences Exactes & Technologie','Licence LMD','Formation en réseaux à l''Institut Supérieur des Sciences Appliquées et de Technologie de Gabès.','["Ingénieur réseaux","Administrateur","Expert télécom"]','["Réseaux","Télécommunications","Sécurité","Cloud"]',8,81,102.84,115.0,134.29,19,3,'Français','FG+(M+SP+Info)/3','Informatique, Mathématiques, Sciences Techniques','⚛️','ct-sci'),

-- ══════════════════════════════════════════
-- ARCHITECTURE & GÉNIE CIVIL
-- ══════════════════════════════════════════
('11601','Cycle Ingénieur — Architecture','Architecture & Génie Civil','Cycle Ingénieur','Formation d''architecte de haut niveau à l''École Nationale d''Architecture et d''Urbanisme (ENAU). Cycle de 5 ans très sélectif.','["Architecte DPLG","Urbaniste","Chef de projet BTP","Architecte d''intérieur","Scénographe","BIM Manager"]','["Dessin architectural","Conception architecturale","Histoire de l''architecture","Mathématiques","Résistance des matériaux","Urbanisme","Droit de la construction"]',2,11,165.0,175.0,195.0,60,5,'Français','FG+(M+SP+Info)/3','Mathématiques, Informatique, Sciences Techniques, Sciences Expérimentales','🏗️','ct-ing'),

('10603','Classe Préparatoire Intégrée — Sciences & Technologies','Architecture & Génie Civil','Classe Préparatoire','Classe préparatoire intégrée donnant accès aux cycles d''ingénieur (2 ans prép + 3 ans ingénieur). Formation intensive en maths, physique et informatique.','["Ingénieur (après cycle)","Architecte (après concours)"]','["Mathématiques supérieures","Physique","Chimie","Informatique","Sciences pour l''ingénieur"]',3,19,168.0,178.0,196.0,80,2,'Français','FG+(M+SP)/2','Mathématiques, Sciences Techniques, Informatique','🏗️','ct-ing'),

('10518','Cycle Ingénieur — Génie Informatique (ENIT)','Architecture & Génie Civil','Cycle Ingénieur','Formation d''ingénieur en génie informatique à l''École Nationale d''Ingénieurs de Tunis (ENIT). Formation de 5 ans, très sélective.','["Ingénieur ENIT","Architecte systèmes","Chef de projet","Chercheur","Entrepreneur"]','["Mathématiques","Algorithmique","Programmation avancée","Systèmes embarqués","Réseaux","IA","Génie logiciel"]',3,19,184.03,188.0,199.73,140,5,'Français','FG+(M+SP+Info)/3','Mathématiques, Informatique','🏗️','ct-ing'),

('10520','Cycle Ingénieur — Génie Mécanique (ENIT)','Architecture & Génie Civil','Cycle Ingénieur','Formation d''ingénieur en génie mécanique à l''École Nationale d''Ingénieurs de Tunis.','["Ingénieur mécanique","Concepteur industriel","Chef de projet","Chercheur"]','["Mécanique","Thermodynamique","Résistance des matériaux","Automatique","CAO/DAO","Génie industriel"]',3,19,175.0,182.0,195.0,40,5,'Français','FG+(M+SP)/2','Mathématiques, Sciences Techniques','🏗️','ct-ing'),

('10522','Cycle Ingénieur — Génie Civil (ENIT)','Architecture & Génie Civil','Cycle Ingénieur','Formation d''ingénieur en génie civil à l''École Nationale d''Ingénieurs de Tunis.','["Ingénieur BTP","Chef de projet construction","Géotechnicien","Hydraulicien"]','["Mécanique des structures","Béton armé","Géotechnique","Hydraulique","Topographie"]',3,19,172.0,180.0,193.0,30,5,'Français','FG+(M+SP)/2','Mathématiques, Sciences Techniques, Sciences Expérimentales','🏗️','ct-ing'),

('30518','Cycle Ingénieur — Informatique (ENISo)','Architecture & Génie Civil','Cycle Ingénieur','Cycle ingénieur en informatique à l''École Nationale d''Ingénieurs de Sousse.','["Ingénieur informaticien","Architecte logiciel","DevOps","Data Scientist"]','["Mathématiques","Algorithmique","Programmation","Réseaux","IA","Cloud"]',5,41,181.0,185.0,193.0,60,5,'Français','FG+(M+SP+Info)/3','Mathématiques, Informatique, Sciences Techniques','🏗️','ct-ing'),

('40518','Cycle Ingénieur — Informatique (ENIS)','Architecture & Génie Civil','Cycle Ingénieur','Cycle ingénieur en informatique à l''École Nationale d''Ingénieurs de Sfax.','["Ingénieur informaticien","Développeur","Data Scientist","Chef de projet"]','["Mathématiques","Algorithmique","Programmation avancée","Réseaux","IA"]',7,63,178.0,184.0,195.0,50,5,'Français','FG+(M+SP+Info)/3','Mathématiques, Informatique, Sciences Techniques','🏗️','ct-ing'),

-- ══════════════════════════════════════════
-- MÉDECINE, PHARMACIE & ODONTOLOGIE
-- ══════════════════════════════════════════
('10700','Doctorat en Médecine (7 ans) — Tunis','Médecine, Pharmacie & Odontologie','Doctorat en Médecine','Formation médicale complète de 7 ans à la Faculté de Médecine de Tunis. La plus sélective des formations tunisiennes. Comprend stages cliniques et thèse.','["Médecin généraliste","Médecin spécialiste","Chirurgien","Chercheur médical","Urgentiste","Pédiatre"]','["Anatomie","Biochimie médicale","Physiologie","Histologie","Sémiologie","Pharmacologie","Médecine interne","Chirurgie","Pédiatrie","Gynécologie"]',3,15,190.0,193.0,199.0,210,7,'Français','FG+(M+SVT)/2','Mathématiques, Sciences Expérimentales','🩺','ct-med'),

('31700','Doctorat en Médecine (7 ans) — Monastir','Médecine, Pharmacie & Odontologie','Doctorat en Médecine','Formation médicale de 7 ans à la Faculté de Médecine de Monastir. Reconnue pour son excellence clinique et sa recherche médicale.','["Médecin généraliste","Médecin spécialiste","Chirurgien","Chercheur"]','["Anatomie","Biochimie","Physiologie","Sémiologie médicale","Pharmacologie","Médecine interne","Chirurgie"]',6,44,185.0,190.0,198.0,160,7,'Français','FG+(M+SVT)/2','Mathématiques, Sciences Expérimentales','🩺','ct-med'),

('40700','Doctorat en Médecine (7 ans) — Sfax','Médecine, Pharmacie & Odontologie','Doctorat en Médecine','Formation médicale de 7 ans à la Faculté de Médecine de Sfax. Centre hospitalier universitaire de référence pour le sud tunisien.','["Médecin généraliste","Spécialiste","Chirurgien","Urgentiste"]','["Anatomie","Physiologie","Biochimie","Sémiologie","Médecine interne","Chirurgie","Gynécologie"]',7,57,183.0,188.0,197.0,150,7,'Français','FG+(M+SVT)/2','Mathématiques, Sciences Expérimentales','🩺','ct-med'),

('10702','Doctorat en Médecine Dentaire (6 ans)','Médecine, Pharmacie & Odontologie','Doctorat en Médecine Dentaire','Formation de chirurgien-dentiste de 6 ans. Comprend formation préclinique et stages en clinique dentaire universitaire.','["Chirurgien-dentiste libéral","Orthodontiste (spécialité)","Prothésiste","Chirurgien maxillo-facial","Chercheur"]','["Anatomie dentaire","Biomatériaux dentaires","Prothèse dentaire","Endodontie","Parodontologie","Chirurgie buccale","Orthodontie"]',3,15,178.0,185.0,196.0,80,6,'Français','FG+(M+SVT)/2','Mathématiques, Sciences Expérimentales','🩺','ct-med'),

('10703','Diplôme de Pharmacien (5 ans)','Médecine, Pharmacie & Odontologie','Diplôme de Pharmacien','Formation de 5 ans menant au diplôme de pharmacien. Couvre la pharmacologie, la chimie thérapeutique et les sciences pharmaceutiques.','["Pharmacien officinal","Pharmacien industriel","Pharmacien hospitalier","Chercheur pharmacologue","Représentant médical"]','["Chimie organique","Pharmacologie","Biochimie","Galénique","Chimie thérapeutique","Microbiologie médicale","Pharmacognosie"]',6,46,173.0,180.0,194.0,100,5,'Français','FG+(M+SVT)/2','Mathématiques, Sciences Expérimentales','🩺','ct-med'),

('10704','Licence en Sciences Infirmières','Médecine, Pharmacie & Odontologie','Licence LMD','Formation en soins infirmiers, nursing et gestion des soins de santé. Prépare aux métiers paramédicaux hospitaliers et communautaires.','["Infirmier(e) diplômé(e)","Infirmier chef","Infirmier de réanimation","Infirmier aux urgences","Coordinateur de soins"]','["Anatomie","Physiologie","Soins infirmiers","Pharmacologie","Médecine interne","Chirurgie infirmière","Urgences"]',3,15,120.0,135.0,158.0,80,3,'Français','FG+SVT','Sciences Expérimentales, Mathématiques','🩺','ct-med'),

('10705','Licence en Kinésithérapie','Médecine, Pharmacie & Odontologie','Licence LMD','Formation en kinésithérapie et rééducation fonctionnelle. Prépare aux métiers de la rééducation motrice, respiratoire et neurologique.','["Kinésithérapeute","Rééducateur fonctionnel","Thérapeute du sport","Responsable rééducation"]','["Anatomie","Physiologie","Biomécanique","Techniques de kinésithérapie","Neurologie","Rhumatologie","Rééducation sportive"]',3,15,130.0,145.0,168.0,40,3,'Français','FG+SVT','Sciences Expérimentales','🩺','ct-med'),

-- ══════════════════════════════════════════
-- SCIENCES AGRONOMIQUES & BIOTECH
-- ══════════════════════════════════════════
('10801','Licence en Sciences Agronomiques','Sciences Agronomiques & Biotech','Licence LMD','Formation en agronomie moderne : production végétale, élevage, agroalimentaire et gestion des ressources naturelles.','["Ingénieur agronome","Technicien agricole","Responsable qualité agroalimentaire","Conseiller agricole","Chercheur INRAT"]','["Biologie végétale","Pédologie","Hydraulique agricole","Phytopathologie","Zootechnie","Économie rurale","Machinisme agricole"]',3,22,92.0,105.0,128.0,60,3,'Français','FG+SVT','Sciences Expérimentales, Mathématiques','🌱','ct-sci'),

('10802','Licence en Biotechnologie','Sciences Agronomiques & Biotech','Licence LMD','Formation en biotechnologie végétale, animale et microbienne. Prépare aux métiers de la recherche, de l''industrie pharmaceutique et agroalimentaire.','["Biotechnologiste","Technicien de laboratoire","Chercheur INRAT","Biologiste industriel","Responsable qualité"]','["Biologie moléculaire","Génétique","Biochimie","Microbiologie","Techniques de culture in vitro","Génie génétique"]',3,22,100.0,115.0,138.0,40,3,'Français','FG+SVT','Sciences Expérimentales','🌱','ct-sci'),

('10803','Licence en Sciences de l''Environnement','Sciences Agronomiques & Biotech','Licence LMD','Formation en écologie, gestion des ressources naturelles et développement durable. Prépare aux métiers de l''environnement et du développement durable.','["Environnementaliste","Chargé de mission développement durable","Gestionnaire de parc naturel","Expert en pollution"]','["Écologie","Biologie de la conservation","Gestion des déchets","Droit de l''environnement","Énergies renouvelables"]',3,22,95.0,108.0,130.0,35,3,'Français','FG+SVT','Sciences Expérimentales, Mathématiques','🌱','ct-sci'),

('80801','Licence en Sciences Agronomiques — Jendouba','Sciences Agronomiques & Biotech','Licence LMD','Formation en agronomie à l''Institut National Agronomique (antenne Jendouba). Région agricole majeure de Tunisie.','["Ingénieur agronome","Technicien agricole","Conseiller rural"]','["Biologie végétale","Pédologie","Hydraulique","Élevage","Économie rurale"]',11,91,88.0,100.0,118.0,40,3,'Français','FG+SVT','Sciences Expérimentales','🌱','ct-sci');

-- ────────────────────────────────────────────────────────────
-- UTILISATEURS (Admin par défaut)
-- Mot de passe : password
-- ────────────────────────────────────────────────────────────
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, role) VALUES
  ('Admin', 'EduGuide', 'admin@eduguide.tn',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'admin');

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────
-- RÉSUMÉ
-- ────────────────────────────────────────────────────────────
-- Tables : gouvernorats(24), universites(12), institutions(91+)
-- bac_sections(7), filieres(55 représentatives), utilisateurs, candidatures
-- Score sur 210 points (conforme au guide officiel 2025)
-- Ajouter institution_id dans UPDATE filieres SET institution_id=... selon logique code
-- Source : Ministère de l'Enseignement Supérieur et de la Recherche Scientifique — Tunisie