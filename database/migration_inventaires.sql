-- Migration pour corriger la table inventaires
-- Ajoute les colonnes manquantes nécessaires pour le module inventaires

-- Ajouter la colonne reference (unique)
ALTER TABLE `inventaires`
ADD COLUMN `reference` VARCHAR(100) NOT NULL AFTER `id`,
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

SELECT 'Migration inventaires terminée avec succès' as resultat;
