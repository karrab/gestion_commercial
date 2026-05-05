-- ============================================
-- Base de données: Gestion Stock Matériel Informatique
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `stock_materiel` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `stock_materiel`;

-- ============================================
-- Table: parametres
-- ============================================
CREATE TABLE `parametres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_etablissement` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `adresse` text,
  `tel_fixe` varchar(20) DEFAULT NULL,
  `tel_mobile` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: roles
-- ============================================
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: permissions
-- ============================================
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`module`, `action`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: role_permissions
-- ============================================
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `fk_role_perm_permission` (`permission_id`),
  CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_perm_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `mail` (`mail`),
  KEY `idx_role` (`role_id`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: services
-- ============================================
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: employes
-- ============================================
CREATE TABLE `employes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricule` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `service_id` int(11) NOT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `tel1` varchar(20) DEFAULT NULL,
  `tel2` varchar(20) DEFAULT NULL,
  `notes` text,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `idx_service` (`service_id`),
  KEY `idx_nom_prenom` (`nom`, `prenom`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_employe_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: bureaux
-- ============================================
CREATE TABLE `bureaux` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_local` varchar(50) NOT NULL,
  `service_id` int(11) NOT NULL,
  `employe_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_local` (`code_local`),
  KEY `idx_service` (`service_id`),
  KEY `idx_employe` (`employe_id`),
  CONSTRAINT `fk_bureau_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `fk_bureau_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: armoires
-- ============================================
CREATE TABLE `armoires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: fournisseurs
-- ============================================
CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(255) NOT NULL,
  `adresse` text,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `tel1` varchar(20) DEFAULT NULL,
  `tel2` varchar(20) DEFAULT NULL,
  `notes` text,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom_complet`),
  KEY `idx_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: articles
-- ============================================
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_article` varchar(100) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `qte_entree` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qte_sortie` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qte_disponible` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_initial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_min` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_max` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_article` (`code_article`),
  KEY `idx_designation` (`designation`),
  KEY `idx_actif` (`actif`),
  KEY `idx_qte_disponible` (`qte_disponible`),
  KEY `idx_code_designation` (`code_article`, `designation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: entrees
-- ============================================
CREATE TABLE `entrees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fournisseur_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `fichier` varchar(255) DEFAULT NULL,
  `notes` text,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fournisseur` (`fournisseur_id`),
  KEY `idx_date` (`date`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_entree_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  CONSTRAINT `fk_entree_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: ligne_entrees
-- ============================================
CREATE TABLE `ligne_entrees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entree_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `code_article` varchar(100) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `qte_entree` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entree` (`entree_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`),
  CONSTRAINT `fk_ligne_entree_entree` FOREIGN KEY (`entree_id`) REFERENCES `entrees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ligne_entree_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: sorties
-- ============================================
CREATE TABLE `sorties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `service_affectation_id` int(11) DEFAULT NULL,
  `employe_affectation_id` int(11) DEFAULT NULL,
  `armoire_id` int(11) DEFAULT NULL,
  `bureau_id` int(11) DEFAULT NULL,
  `fichier` varchar(255) DEFAULT NULL,
  `notes` text,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_employe` (`employe_id`),
  KEY `idx_date` (`date`),
  KEY `idx_service_affectation` (`service_affectation_id`),
  KEY `idx_employe_affectation` (`employe_affectation_id`),
  KEY `idx_armoire` (`armoire_id`),
  KEY `idx_bureau` (`bureau_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_sortie_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `fk_sortie_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`),
  CONSTRAINT `fk_sortie_service_affectation` FOREIGN KEY (`service_affectation_id`) REFERENCES `services` (`id`),
  CONSTRAINT `fk_sortie_employe_affectation` FOREIGN KEY (`employe_affectation_id`) REFERENCES `employes` (`id`),
  CONSTRAINT `fk_sortie_armoire` FOREIGN KEY (`armoire_id`) REFERENCES `armoires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sortie_bureau` FOREIGN KEY (`bureau_id`) REFERENCES `bureaux` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sortie_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: ligne_sorties
-- ============================================
CREATE TABLE `ligne_sorties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sortie_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `code_article` varchar(100) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `qte_sortie` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sortie` (`sortie_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`),
  CONSTRAINT `fk_ligne_sortie_sortie` FOREIGN KEY (`sortie_id`) REFERENCES `sorties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ligne_sortie_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: retours
-- ============================================
CREATE TABLE `retours` (
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
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_retour_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `fk_retour_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`),
  CONSTRAINT `fk_retour_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: ligne_retours
-- ============================================
CREATE TABLE `ligne_retours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `retour_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `code_article` varchar(100) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `qte_retour` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retour` (`retour_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`),
  CONSTRAINT `fk_ligne_retour_retour` FOREIGN KEY (`retour_id`) REFERENCES `retours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ligne_retour_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: equipes_inventaire
-- ============================================
CREATE TABLE `equipes_inventaire` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: inventaires
-- ============================================
CREATE TABLE `inventaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `fichier` varchar(255) DEFAULT NULL,
  `etat` enum('en_cours','valide','cloture') NOT NULL DEFAULT 'en_cours',
  `user_id` int(11) NOT NULL,
  `user_validation_id` int(11) DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`),
  KEY `idx_equipe` (`equipe_id`),
  KEY `idx_etat` (`etat`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_validation` (`user_validation_id`),
  CONSTRAINT `fk_inventaire_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes_inventaire` (`id`),
  CONSTRAINT `fk_inventaire_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_inventaire_user_validation` FOREIGN KEY (`user_validation_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: ligne_inventaires
-- ============================================
CREATE TABLE `ligne_inventaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventaire_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `code_article` varchar(100) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `qte_theorique` decimal(10,2) NOT NULL,
  `qte_physique` decimal(10,2) DEFAULT 0.00,
  `ecart` decimal(10,2) DEFAULT 0.00,
  `observation` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventaire` (`inventaire_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`),
  CONSTRAINT `fk_ligne_inventaire_inventaire` FOREIGN KEY (`inventaire_id`) REFERENCES `inventaires` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ligne_inventaire_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: traces
-- ============================================
CREATE TABLE `traces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`table_name`),
  KEY `idx_created` (`created_at`),
  KEY `idx_composite` (`module`, `action`, `created_at`),
  CONSTRAINT `fk_trace_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Données initiales: Paramètres
-- ============================================
INSERT INTO `parametres` (`nom_etablissement`, `logo`, `adresse`, `tel_fixe`, `tel_mobile`, `email`) VALUES
('Établissement de Gestion Stock', 'logo.png', '123 Rue Principale, Ville', '01-234-5678', '06-12-34-56-78', 'contact@etablissement.com');

-- ============================================
-- Données initiales: Rôles
-- ============================================
INSERT INTO `roles` (`nom`, `description`) VALUES
('admin', 'Administrateur système avec tous les droits'),
('gestionnaire', 'Gestionnaire de stock avec droits étendus'),
('utilisateur', 'Utilisateur standard avec droits limités');

-- ============================================
-- Données initiales: Permissions
-- ============================================
INSERT INTO `permissions` (`nom`, `module`, `action`, `description`) VALUES
('Articles - Voir', 'articles', 'view', 'Voir les articles'),
('Articles - Ajouter', 'articles', 'create', 'Ajouter des articles'),
('Articles - Modifier', 'articles', 'update', 'Modifier des articles'),
('Articles - Supprimer', 'articles', 'delete', 'Supprimer des articles'),
('Articles - Modifier Stock Initial', 'articles', 'update_stock_initial', 'Modifier le stock initial'),
('Entrées - Voir', 'entrees', 'view', 'Voir les entrées'),
('Entrées - Ajouter', 'entrees', 'create', 'Ajouter des entrées'),
('Entrées - Modifier', 'entrees', 'update', 'Modifier des entrées'),
('Entrées - Supprimer', 'entrees', 'delete', 'Supprimer des entrées'),
('Sorties - Voir', 'sorties', 'view', 'Voir les sorties'),
('Sorties - Ajouter', 'sorties', 'create', 'Ajouter des sorties'),
('Sorties - Modifier', 'sorties', 'update', 'Modifier des sorties'),
('Sorties - Supprimer', 'sorties', 'delete', 'Supprimer des sorties'),
('Retours - Voir', 'retours', 'view', 'Voir les retours'),
('Retours - Ajouter', 'retours', 'create', 'Ajouter des retours'),
('Retours - Modifier', 'retours', 'update', 'Modifier des retours'),
('Retours - Supprimer', 'retours', 'delete', 'Supprimer des retours'),
('Inventaires - Voir', 'inventaires', 'view', 'Voir les inventaires'),
('Inventaires - Ajouter', 'inventaires', 'create', 'Ajouter des inventaires'),
('Inventaires - Modifier', 'inventaires', 'update', 'Modifier des inventaires'),
('Inventaires - Supprimer', 'inventaires', 'delete', 'Supprimer des inventaires'),
('Inventaires - Valider', 'inventaires', 'validate', 'Valider les inventaires'),
('Inventaires - Réinitialiser', 'inventaires', 'reset', 'Réinitialiser le stock'),
('Utilisateurs - Voir', 'users', 'view', 'Voir les utilisateurs'),
('Utilisateurs - Ajouter', 'users', 'create', 'Ajouter des utilisateurs'),
('Utilisateurs - Modifier', 'users', 'update', 'Modifier des utilisateurs'),
('Utilisateurs - Supprimer', 'users', 'delete', 'Supprimer des utilisateurs'),
('Paramètres - Voir', 'parametres', 'view', 'Voir les paramètres'),
('Paramètres - Modifier', 'parametres', 'update', 'Modifier les paramètres'),
('Rapports - Voir', 'rapports', 'view', 'Voir les rapports'),
('Rapports - Exporter', 'rapports', 'export', 'Exporter les rapports'),
('Services - Voir', 'services', 'view', 'Voir les services'),
('Services - Ajouter', 'services', 'create', 'Ajouter des services'),
('Services - Modifier', 'services', 'update', 'Modifier des services'),
('Services - Supprimer', 'services', 'delete', 'Supprimer des services'),
('Employes - Voir', 'employes', 'view', 'Voir les employes'),
('Employes - Ajouter', 'employes', 'create', 'Ajouter des employes'),
('Employes - Modifier', 'employes', 'update', 'Modifier des employes'),
('Employes - Supprimer', 'employes', 'delete', 'Supprimer des employes'),
('Fournisseurs - Voir', 'fournisseurs', 'view', 'Voir les fournisseurs'),
('Fournisseurs - Ajouter', 'fournisseurs', 'create', 'Ajouter des fournisseurs'),
('Fournisseurs - Modifier', 'fournisseurs', 'update', 'Modifier des fournisseurs'),
('Fournisseurs - Supprimer', 'fournisseurs', 'delete', 'Supprimer des fournisseurs'),
('Equipes - Voir', 'equipes', 'view', 'Voir les equipes'),
('Equipes - Ajouter', 'equipes', 'create', 'Ajouter des equipes'),
('Equipes - Modifier', 'equipes', 'update', 'Modifier des equipes'),
('Equipes - Supprimer', 'equipes', 'delete', 'Supprimer des equipes');

-- ============================================
-- Données initiales: Attribution permissions admin
-- ============================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- ============================================
-- Données initiales: Attribution permissions gestionnaire
-- ============================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions`
WHERE `action` != 'delete' AND `module` != 'users' AND `module` != 'parametres';

-- ============================================
-- Données initiales: Utilisateurs
-- ============================================
-- Mot de passe: admin123 (hashé avec PASSWORD_DEFAULT)
INSERT INTO `users` (`nom`, `prenom`, `mail`, `login`, `password`, `role_id`) VALUES
('Admin', 'Super', 'admin@stock.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Gestionnaire', 'Principal', 'gestionnaire@stock.com', 'gestionnaire', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('Utilisateur', 'Standard', 'user@stock.com', 'user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);

-- ============================================
-- Données initiales: Services
-- ============================================
INSERT INTO `services` (`nom`, `notes`) VALUES
('Informatique', 'Service informatique'),
('Ressources Humaines', 'Service RH'),
('Comptabilité', 'Service comptabilité'),
('Direction', 'Direction générale'),
('Commercial', 'Service commercial');

-- ============================================
-- Données initiales: Employés
-- ============================================
INSERT INTO `employes` (`matricule`, `nom`, `prenom`, `service_id`, `mail`, `tel1`) VALUES
('EMP001', 'Dupont', 'Jean', 1, 'jean.dupont@entreprise.com', '0612345678'),
('EMP002', 'Martin', 'Marie', 2, 'marie.martin@entreprise.com', '0623456789'),
('EMP003', 'Durand', 'Pierre', 3, 'pierre.durand@entreprise.com', '0634567890'),
('EMP004', 'Bernard', 'Sophie', 1, 'sophie.bernard@entreprise.com', '0645678901'),
('EMP005', 'Petit', 'Luc', 4, 'luc.petit@entreprise.com', '0656789012');

-- ============================================
-- Données initiales: Bureaux
-- ============================================
INSERT INTO `bureaux` (`code_local`, `service_id`, `employe_id`) VALUES
('B101', 1, 1),
('B102', 1, 4),
('B201', 2, 2),
('B301', 3, 3),
('B401', 4, 5);

-- ============================================
-- Données initiales: Armoires
-- ============================================
INSERT INTO `armoires` (`numero`, `nom`) VALUES
('ARM001', 'Armoire Matériel Réseau'),
('ARM002', 'Armoire Périphériques'),
('ARM003', 'Armoire Consommables');

-- ============================================
-- Données initiales: Fournisseurs
-- ============================================
INSERT INTO `fournisseurs` (`nom_complet`, `adresse`, `ville`, `pays`, `code_postal`, `tel1`, `tel2`) VALUES
('TechSupply Inc.', '45 Avenue des Technologies', 'Paris', 'France', '75001', '0140123456', '0140123457'),
('Informatique Plus', '12 Rue du Commerce', 'Lyon', 'France', '69001', '0478123456', NULL),
('Matériel Pro', '78 Boulevard de la Tech', 'Marseille', 'France', '13001', '0491123456', '0491123457'),
('Digital Solutions', '23 Rue de l\'Innovation', 'Toulouse', 'France', '31000', '0561123456', NULL);

-- ============================================
-- Données initiales: Équipes inventaire
-- ============================================
INSERT INTO `equipes_inventaire` (`nom`, `description`) VALUES
('Équipe A', 'Équipe principale inventaire'),
('Équipe B', 'Équipe secondaire inventaire'),
('Équipe Audit', 'Équipe audit annuel');

-- ============================================
-- Données initiales: Articles
-- ============================================
INSERT INTO `articles` (`code_article`, `designation`, `stock_initial`, `qte_disponible`, `stock_min`, `stock_max`, `notes`) VALUES
('PC-001', 'Ordinateur Portable Dell Latitude 5520', 10.00, 10.00, 5.00, 20.00, 'PC portable standard'),
('PC-002', 'Ordinateur Bureau HP EliteDesk 800', 15.00, 15.00, 8.00, 25.00, 'PC bureau professionnel'),
('MON-001', 'Écran Dell 24" Full HD', 20.00, 20.00, 10.00, 30.00, 'Écran bureautique'),
('MON-002', 'Écran Samsung 27" 4K', 8.00, 8.00, 3.00, 15.00, 'Écran haute résolution'),
('CLAV-001', 'Clavier sans fil Logitech K270', 25.00, 25.00, 15.00, 40.00, 'Clavier bureautique'),
('SOU-001', 'Souris sans fil Logitech M185', 30.00, 30.00, 20.00, 50.00, 'Souris bureautique'),
('IMP-001', 'Imprimante HP LaserJet Pro M404', 5.00, 5.00, 2.00, 10.00, 'Imprimante laser'),
('SWITCH-001', 'Switch Cisco 24 ports', 3.00, 3.00, 1.00, 8.00, 'Switch réseau'),
('CABLE-001', 'Câble réseau Cat6 5m', 100.00, 100.00, 50.00, 200.00, 'Câble Ethernet'),
('USB-001', 'Clé USB 32GB Kingston', 50.00, 50.00, 30.00, 100.00, 'Stockage USB');

-- ============================================
-- Optimisations MySQL pour performance
-- ============================================
-- Note: Ces paramètres doivent être ajoutés dans my.cnf/my.ini

-- [mysqld]
-- innodb_buffer_pool_size = 1G
-- innodb_log_file_size = 256M
-- innodb_flush_log_at_trx_commit = 2
-- innodb_flush_method = O_DIRECT
-- query_cache_type = 0
-- query_cache_size = 0
