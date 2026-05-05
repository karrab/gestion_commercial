-- Script de vérification et création des tables manquantes
-- Exécutez ce script dans phpMyAdmin pour corriger l'erreur

-- 1. Vérifier que toutes les tables existent
SELECT 'Vérification des tables' as info;

-- Si la table retours n'existe pas, la créer
CREATE TABLE IF NOT EXISTS `retours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `fichier` varchar(255) DEFAULT NULL,
  `notes` text,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_employe` (`employe_id`),
  KEY `idx_date` (`date`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Créer la table ligne_retours si elle n'existe pas
CREATE TABLE IF NOT EXISTS `ligne_retours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `retour_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `code_article` varchar(100) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `qte_retour` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retour` (`retour_id`),
  KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Ajouter la colonne qte_retour si elle n'existe pas
-- MySQL ne supporte pas IF NOT EXISTS pour ALTER COLUMN, donc on utilise une procédure
DELIMITER $$

DROP PROCEDURE IF EXISTS AddQteRetourColumn$$

CREATE PROCEDURE AddQteRetourColumn()
BEGIN
    DECLARE CONTINUE HANDLER FOR SQLSTATE '42S21' BEGIN END;
    ALTER TABLE articles ADD COLUMN qte_retour DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER qte_sortie;
END$$

DELIMITER ;

CALL AddQteRetourColumn();
DROP PROCEDURE IF EXISTS AddQteRetourColumn;

-- 3. Vérifier que tout est OK
SELECT 'Tables créées avec succès' as resultat;

-- 4. Afficher la structure de la table articles pour vérification
DESCRIBE articles;
