-- ============================================
-- MIGRATION COMPLÈTE
-- Ajoute toutes les colonnes manquantes dans les tables
-- ============================================

-- ============================================
-- 1. TABLE INVENTAIRES
-- ============================================

-- Ajouter la colonne reference (unique)
ALTER TABLE `inventaires`
ADD COLUMN `reference` VARCHAR(100) NOT NULL AFTER `id`;

-- Ajouter index unique sur reference
ALTER TABLE `inventaires`
ADD UNIQUE KEY `idx_reference` (`reference`);

-- Ajouter les colonnes date_debut et date_fin
ALTER TABLE `inventaires`
ADD COLUMN `date_debut` DATE NOT NULL AFTER `reference`,
ADD COLUMN `date_fin` DATE DEFAULT NULL AFTER `date_debut`;

-- Modifier la colonne equipe_id pour permettre NULL (optionnel)
ALTER TABLE `inventaires`
MODIFY COLUMN `equipe_id` INT(11) DEFAULT NULL;

-- Supprimer la colonne date originale (remplacée par date_debut/date_fin)
ALTER TABLE `inventaires`
DROP COLUMN `date`;

-- Ajouter la colonne notes pour commentaires
ALTER TABLE `inventaires`
ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `fichier`;

-- Ajouter des index pour améliorer les performances
ALTER TABLE `inventaires`
ADD KEY `idx_date_debut` (`date_debut`),
ADD KEY `idx_date_fin` (`date_fin`);

-- Mettre à jour les contraintes de clés étrangères
ALTER TABLE `inventaires`
DROP FOREIGN KEY `fk_inventaire_equipe`;

ALTER TABLE `inventaires`
ADD CONSTRAINT `fk_inventaire_equipe`
FOREIGN KEY (`equipe_id`) REFERENCES `equipes_inventaire` (`id`)
ON DELETE SET NULL;

-- ============================================
-- 2. TABLE BUREAUX
-- ============================================

-- Ajouter colonnes manquantes
ALTER TABLE `bureaux`
ADD COLUMN `batiment` VARCHAR(100) DEFAULT NULL AFTER `code_local`,
ADD COLUMN `etage` VARCHAR(50) DEFAULT NULL AFTER `batiment`,
ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `employe_id`;

-- Modifier service_id pour permettre NULL (optionnel)
ALTER TABLE `bureaux`
MODIFY COLUMN `service_id` INT(11) DEFAULT NULL;

-- Mettre à jour la contrainte de clé étrangère
ALTER TABLE `bureaux`
DROP FOREIGN KEY `fk_bureau_service`;

ALTER TABLE `bureaux`
ADD CONSTRAINT `fk_bureau_service`
FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
ON DELETE SET NULL;

-- ============================================
-- 3. TABLE ARMOIRES
-- ============================================

-- Vérifier si la table existe et ajouter colonnes si nécessaire
ALTER TABLE `armoires`
ADD COLUMN `notes` TEXT DEFAULT NULL;

-- ============================================
-- 4. TABLE EQUIPES_INVENTAIRE
-- ============================================

-- Ajouter colonnes si manquantes
ALTER TABLE `equipes_inventaire`
ADD COLUMN `notes` TEXT DEFAULT NULL;

-- ============================================
-- 5. TABLE RETOURS (vérification)
-- ============================================

-- S'assurer que la table retours existe
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

-- S'assurer que la table ligne_retours existe
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

-- ============================================
-- 6. TABLE ARTICLES (colonne qte_retour)
-- ============================================

-- Procédure pour ajouter qte_retour si elle n'existe pas
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

-- ============================================
-- 7. TABLE PARAMETRES (pour le module paramètres)
-- ============================================

CREATE TABLE IF NOT EXISTS `parametres` (
  `id` int(11) NOT NULL DEFAULT 1,
  `nom_entreprise` varchar(255) NOT NULL DEFAULT 'Gestion Stock',
  `adresse` text,
  `tel` varchar(50),
  `email` varchar(255),
  `site_web` varchar(255),
  `logo` varchar(255),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insérer une ligne par défaut si elle n'existe pas
INSERT IGNORE INTO `parametres` (`id`, `nom_entreprise`) VALUES (1, 'Gestion Stock Matériel Informatique');

-- ============================================
-- FIN DE LA MIGRATION
-- ============================================

SELECT 'Migration complète terminée avec succès !' as resultat;
SELECT 'Toutes les tables ont été mises à jour.' as info;

-- Vérifier les tables modifiées
SELECT 'Vérification des structures de tables :' as verification;
DESCRIBE inventaires;
DESCRIBE bureaux;
