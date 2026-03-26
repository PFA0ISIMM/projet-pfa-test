-- ============================================================
-- OrientTN — Base de données
-- Site d'orientation universitaire en Tunisie
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
START TRANSACTION;

CREATE DATABASE IF NOT EXISTS `orienttn`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_general_ci;

USE `orienttn`;

-- ─────────────────────────────────────────────────────
-- TABLE : utilisateurs
-- ─────────────────────────────────────────────────────
CREATE TABLE `utilisateurs` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `nom`         VARCHAR(80)  NOT NULL,
    `prenom`      VARCHAR(80)  NOT NULL,
    `email`       VARCHAR(150) NOT NULL,
    `password`    VARCHAR(255) NOT NULL,
    `bac_serie`   VARCHAR(60)  DEFAULT NULL   COMMENT 'Série du baccalauréat',
    `bac_score`   DECIMAL(5,3) DEFAULT NULL   COMMENT 'Moyenne du bac /20',
    `region`      VARCHAR(80)  DEFAULT NULL,
    `telephone`   VARCHAR(20)  DEFAULT NULL,
    `role`        ENUM('etudiant','admin') DEFAULT 'etudiant',
    `avatar`      VARCHAR(255) DEFAULT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- TABLE : gouvernorats
-- ─────────────────────────────────────────────────────
CREATE TABLE `gouvernorats` (
    `id`    INT(11)     NOT NULL AUTO_INCREMENT,
    `nom`   VARCHAR(80) NOT NULL,
    `code`  VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `gouvernorats` (`nom`, `code`) VALUES
('Tunis', 'TUN'), ('Ariana', 'ARI'), ('Ben Arous', 'BAR'),
('Manouba', 'MAN'), ('Nabeul', 'NAB'), ('Zaghouan', 'ZAG'),
('Bizerte', 'BIZ'), ('Béja', 'BEJ'), ('Jendouba', 'JEN'),
('Kef', 'KEF'), ('Siliana', 'SIL'), ('Kairouan', 'KAI'),
('Kasserine', 'KAS'), ('Sidi Bouzid', 'SBZ'), ('Sousse', 'SOU'),
('Monastir', 'MON'), ('Mahdia', 'MAH'), ('Sfax', 'SFX'),
('Gafsa', 'GAF'), ('Tozeur', 'TOZ'), ('Kébili', 'KEB'),
('Gabès', 'GAB'), ('Médenine', 'MED'), ('Tataouine', 'TAT');

-- ─────────────────────────────────────────────────────
-- TABLE : universites
-- ─────────────────────────────────────────────────────
CREATE TABLE `universites` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `nom`           VARCHAR(200) NOT NULL,
    `sigle`         VARCHAR(30)  DEFAULT NULL,
    `gouvernorat_id`INT(11)      DEFAULT NULL,
    `adresse`       TEXT         DEFAULT NULL,
    `site_web`      VARCHAR(255) DEFAULT NULL,
    `type`          ENUM('publique','privee') DEFAULT 'publique',
    `description`   TEXT         DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `gouvernorat_id` (`gouvernorat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `universites` (`nom`, `sigle`, `gouvernorat_id`, `type`, `description`) VALUES
('Université de Tunis El Manar', 'UTM', 1, 'publique', 'Première université tunisienne, reconnue pour ses facultés de sciences et médecine.'),
('Université de Tunis', 'UT', 1, 'publique', 'Grande institution avec des facultés en lettres, sciences humaines et droit.'),
('Université de Carthage', 'UC', 2, 'publique', 'Université moderne spécialisée en sciences appliquées et ingénierie.'),
('Université de Sousse', 'US', 15, 'publique', 'Université régionale dynamique avec plusieurs instituts techniques.'),
('Université de Sfax', 'USF', 17, 'publique', 'Université reconnue notamment pour ses formations en médecine et ingénierie.'),
('Université de Monastir', 'UM', 16, 'publique', 'Spécialisée en médecine, pharmacie et sciences paramédicales.'),
('Université Centrale', 'UCT', 1, 'privee', 'Université privée moderne offrant des formations en business et technologie.'),
('Université de la Manouba', 'UMA', 4, 'publique', 'Spécialisée en lettres, communication et sciences sociales.'),
('Université de Jendouba', 'UJ', 9, 'publique', 'Université régionale en développement avec des formations en droit et économie.'),
('Université de Gafsa', 'UG', 19, 'publique', 'Université du sud tunisien offrant des formations en sciences et gestion.');

-- ─────────────────────────────────────────────────────
-- TABLE : filieres
-- ─────────────────────────────────────────────────────
CREATE TABLE `filieres` (
    `id`              INT(11)      NOT NULL AUTO_INCREMENT,
    `titre`           VARCHAR(200) NOT NULL,
    `sigle`           VARCHAR(30)  DEFAULT NULL,
    `description`     TEXT         DEFAULT NULL,
    `description_longue` LONGTEXT  DEFAULT NULL,
    `domaine`         VARCHAR(80)  NOT NULL     COMMENT 'Informatique, Médecine, Ingénierie...',
    `type_formation`  VARCHAR(60)  DEFAULT NULL COMMENT 'Licence, Master, Ingénieur, Doctorat...',
    `duree`           INT(2)       DEFAULT NULL COMMENT 'Durée en années',
    `score_min`       DECIMAL(5,3) DEFAULT NULL COMMENT 'Score minimum bac',
    `score_moyen`     DECIMAL(5,3) DEFAULT NULL,
    `capacite`        INT(5)       DEFAULT NULL COMMENT 'Places disponibles',
    `universite_id`   INT(11)      DEFAULT NULL,
    `langue`          VARCHAR(20)  DEFAULT 'Arabe/Français',
    `debouches`       TEXT         DEFAULT NULL COMMENT 'Débouchés professionnels JSON',
    `matieres`        TEXT         DEFAULT NULL COMMENT 'Matières principales JSON',
    `icon`            VARCHAR(10)  DEFAULT '🎓',
    `couleur_classe`  VARCHAR(30)  DEFAULT 'ct-def',
    `statut`          ENUM('active','inactive') DEFAULT 'active',
    `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `universite_id` (`universite_id`),
    KEY `domaine`       (`domaine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `filieres` (`titre`,`sigle`,`description`,`description_longue`,`domaine`,`type_formation`,`duree`,`score_min`,`score_moyen`,`capacite`,`universite_id`,`langue`,`debouches`,`matieres`,`icon`,`couleur_classe`) VALUES
(
    'Licence en Informatique',
    'LI',
    'Formation solide en algorithmique, programmation et systèmes informatiques pour les futurs ingénieurs du numérique.',
    '<p>La Licence en Informatique offre une formation de 3 ans couvrant les fondamentaux de l\'informatique : algorithmique, structures de données, programmation orientée objet, bases de données, réseaux et systèmes. Les étudiants acquièrent des compétences pratiques via des projets et stages.</p><p>Le programme prépare aux métiers du développement logiciel, de l\'administration système, de la cybersécurité et de l\'intelligence artificielle.</p>',
    'Informatique', 'Licence', 3, 12.000, 14.500, 120, 1, 'Français/Anglais',
    '["Développeur Web","Ingénieur Logiciel","Administrateur Système","Data Analyst","DevOps"]',
    '["Algorithmique","Programmation C/Java/Python","Bases de Données","Réseaux","Système d\'Exploitation","Mathématiques Discrètes"]',
    '💻', 'ct-info'
),
(
    'Médecine Générale',
    'MED',
    'Formation médicale d\'excellence de 7 ans, préparant des médecins compétents pour le système de santé tunisien.',
    '<p>La formation en Médecine Générale s\'étend sur 7 ans, incluant 3 ans de cycle préparatoire, 3 ans de cycle clinique et 1 an d\'internat. Elle combine enseignement théorique et pratique clinique intensive dans les hôpitaux universitaires.</p><p>La sélection est très stricte avec un numerus clausus. Le score minimum requis est parmi les plus élevés du baccalauréat.</p>',
    'Médecine', 'Doctorat en Médecine', 7, 17.500, 18.200, 200, 6, 'Français',
    '["Médecin Généraliste","Spécialiste après résidence","Médecin du Travail","Recherche Médicale"]',
    '["Anatomie","Physiologie","Biochimie","Sémiologie","Pharmacologie","Chirurgie","Pédiatrie","Gynécologie"]',
    '🩺', 'ct-med'
),
(
    'Génie Civil',
    'GC',
    'Formation d\'ingénieurs spécialisés dans la conception et construction d\'infrastructures et bâtiments.',
    '<p>Le cycle Ingénieur en Génie Civil forme des professionnels capables de concevoir, construire et superviser des infrastructures : ponts, routes, bâtiments, barrages, systèmes hydrauliques. La formation dure 5 ans (prépa + cycle ingénieur).</p>',
    'Ingénierie', 'Ingénieur', 5, 15.000, 16.500, 80, 3, 'Français',
    '["Ingénieur BTP","Chef de Projet Construction","Ingénieur Bureau d\'Études","Consultant Infrastructure"]',
    '["Mécanique des Structures","Béton Armé","Hydraulique","Topographie","Gestion de Projet","Dessin Technique"]',
    '🏗️', 'ct-ing'
),
(
    'Licence en Sciences Économiques et Gestion',
    'SEG',
    'Formation en économie, management et finance pour comprendre et piloter les organisations modernes.',
    '<p>La Licence en Sciences Économiques et Gestion est une formation polyvalente qui couvre la microéconomie, la macroéconomie, la comptabilité, le management et les mathématiques financières. Durée : 3 ans.</p>',
    'Commerce', 'Licence', 3, 11.000, 13.000, 200, 1, 'Arabe/Français',
    '["Comptable","Contrôleur de Gestion","Conseiller Financier","Manager","Entrepreneur"]',
    '["Microéconomie","Macroéconomie","Comptabilité","Statistiques","Management","Droit des Affaires"]',
    '📊', 'ct-com'
),
(
    'Licence en Droit',
    'LD',
    'Formation juridique complète couvrant le droit privé, public, commercial et international.',
    '<p>La Licence en Droit offre une formation de 3 ans en droit privé, droit public, droit commercial et procédure. Elle prépare aux concours de la magistrature, à l\'exercice du barreau et aux carrières juridiques dans l\'administration.</p>',
    'Droit', 'Licence', 3, 10.000, 12.500, 300, 1, 'Arabe/Français',
    '["Avocat","Magistrat","Notaire","Juriste d\'Entreprise","Fonctionnaire de Justice"]',
    '["Droit Civil","Droit Pénal","Droit Commercial","Droit Constitutionnel","Procédure Civile","Droit International"]',
    '⚖️', 'ct-droit'
),
(
    'Licence en Lettres françaises',
    'LLF',
    'Études littéraires approfondies : littérature, linguistique, traduction et culture francophone.',
    '<p>La Licence en Lettres Françaises forme des étudiants en littérature française et francophone, linguistique, histoire de la littérature et traduction. Durée 3 ans.</p>',
    'Lettres', 'Licence', 3, 10.000, 11.500, 150, 8, 'Français',
    '["Enseignant","Traducteur","Journaliste","Rédacteur","Attaché Culturel"]',
    '["Littérature Française","Linguistique","Traduction","Civilisation Française","Poésie","Roman"]',
    '📚', 'ct-lett'
),
(
    'Licence en Sciences Physiques',
    'LSP',
    'Étude approfondie de la physique fondamentale : mécanique, thermodynamique, électromagnétisme et optique.',
    '<p>La Licence en Sciences Physiques est une formation fondamentale de 3 ans en physique théorique et expérimentale. Elle prépare à la recherche scientifique et à l\'enseignement.</p>',
    'Sciences', 'Licence', 3, 13.000, 15.000, 100, 1, 'Français',
    '["Chercheur","Enseignant-Chercheur","Ingénieur R&D","Physicien Industriel"]',
    '["Mécanique Classique","Thermodynamique","Électromagnétisme","Mécanique Quantique","Optique","Physique Moderne"]',
    '⚛️', 'ct-sci'
),
(
    'Génie Électrique et Automatique',
    'GEA',
    'Formation en électronique, automatique et systèmes embarqués pour l\'industrie du futur.',
    '<p>Le cycle Ingénieur en Génie Électrique et Automatique forme des ingénieurs experts en électronique, automatisation industrielle, systèmes embarqués et réseaux électriques. Durée : 5 ans.</p>',
    'Ingénierie', 'Ingénieur', 5, 15.500, 17.000, 70, 3, 'Français',
    '["Ingénieur en Automatisme","Ingénieur Électronique","Ingénieur Systèmes","Chef de Projet Industriel"]',
    '["Électronique Analogique","Automatique","Traitement du Signal","Microcontrôleurs","Énergie Électrique","Robotique"]',
    '⚡', 'ct-ing'
),
(
    'Pharmacie',
    'PHARM',
    'Formation en sciences pharmaceutiques pour la maîtrise du médicament, de sa conception à sa dispensation.',
    '<p>Le cursus en Pharmacie dure 6 ans et couvre la chimie pharmaceutique, la pharmacologie, la biologie moléculaire et la pratique officinale. Très sélectif avec numerus clausus.</p>',
    'Médecine', 'Doctorat en Pharmacie', 6, 16.500, 17.800, 100, 6, 'Français',
    '["Pharmacien d\'Officine","Pharmacien Hospitalier","Chercheur Pharmaceutique","Délégué Médical"]',
    '["Chimie Organique","Pharmacologie","Biochimie","Microbiologie","Pharmacie Clinique","Droit Pharmaceutique"]',
    '💊', 'ct-med'
),
(
    'Master en Intelligence Artificielle',
    'MIA',
    'Formation d\'excellence en IA, machine learning, deep learning et data science pour les experts de demain.',
    '<p>Le Master en Intelligence Artificielle est une formation de 2 ans (bac+5) spécialisée en apprentissage automatique, traitement du langage naturel, vision par ordinateur et big data. Il accueille des diplômés de licence en informatique ou mathématiques.</p>',
    'Informatique', 'Master', 2, 14.000, 15.500, 40, 1, 'Français/Anglais',
    '["Data Scientist","Machine Learning Engineer","AI Researcher","NLP Engineer","Computer Vision Engineer"]',
    '["Machine Learning","Deep Learning","NLP","Computer Vision","Big Data","Statistiques Avancées","Python/TensorFlow"]',
    '🤖', 'ct-info'
);

-- ─────────────────────────────────────────────────────
-- TABLE : candidatures (favoris/demandes)
-- ─────────────────────────────────────────────────────
CREATE TABLE `candidatures` (
    `id`           INT(11)  NOT NULL AUTO_INCREMENT,
    `utilisateur_id` INT(11) NOT NULL,
    `filiere_id`   INT(11)  NOT NULL,
    `statut`       ENUM('favori','postule','accepte','refuse') DEFAULT 'favori',
    `note`         TEXT     DEFAULT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_filiere` (`utilisateur_id`, `filiere_id`),
    KEY `utilisateur_id` (`utilisateur_id`),
    KEY `filiere_id`     (`filiere_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- TABLE : messages_chatbot
-- ─────────────────────────────────────────────────────
CREATE TABLE `messages_chatbot` (
    `id`           INT(11)  NOT NULL AUTO_INCREMENT,
    `utilisateur_id` INT(11) DEFAULT NULL,
    `session_id`   VARCHAR(100) NOT NULL,
    `role`         ENUM('user','bot') NOT NULL,
    `contenu`      TEXT NOT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `utilisateur_id` (`utilisateur_id`),
    KEY `session_id`     (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- TABLE : avis
-- ─────────────────────────────────────────────────────
CREATE TABLE `avis` (
    `id`           INT(11)  NOT NULL AUTO_INCREMENT,
    `filiere_id`   INT(11)  NOT NULL,
    `utilisateur_id` INT(11) NOT NULL,
    `note`         TINYINT(1) DEFAULT NULL COMMENT '/5',
    `commentaire`  TEXT DEFAULT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `filiere_id`     (`filiere_id`),
    KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────
-- CLÉS ÉTRANGÈRES
-- ─────────────────────────────────────────────────────
ALTER TABLE `filieres`
    ADD CONSTRAINT `fk_fil_univ` FOREIGN KEY (`universite_id`) REFERENCES `universites` (`id`) ON DELETE SET NULL;

ALTER TABLE `universites`
    ADD CONSTRAINT `fk_univ_gouv` FOREIGN KEY (`gouvernorat_id`) REFERENCES `gouvernorats` (`id`) ON DELETE SET NULL;

ALTER TABLE `candidatures`
    ADD CONSTRAINT `fk_cand_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_cand_fil`  FOREIGN KEY (`filiere_id`)     REFERENCES `filieres`     (`id`) ON DELETE CASCADE;

ALTER TABLE `messages_chatbot`
    ADD CONSTRAINT `fk_msg_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

ALTER TABLE `avis`
    ADD CONSTRAINT `fk_avis_fil`  FOREIGN KEY (`filiere_id`)     REFERENCES `filieres`     (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_avis_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

-- Admin par défaut
INSERT INTO `utilisateurs` (`nom`,`prenom`,`email`,`password`,`role`) VALUES
('Admin', 'OrientTN', 'admin@orienttn.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

COMMIT;