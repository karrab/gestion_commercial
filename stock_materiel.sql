-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 16 fév. 2026 à 10:55
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `stock_materiel`
--

-- --------------------------------------------------------



--
-- Structure de la table `articles`
--

DROP TABLE IF EXISTS `articles`;
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code_article` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `qte_entree` int DEFAULT '0',
  `qte_sortie` int DEFAULT '0',
  `qte_retour` int DEFAULT '0',
  `qte_retour_frs` int DEFAULT '0',
  `qte_disponible` int DEFAULT '0',
  `stock_initial` int DEFAULT '0',
  `stock_min` int DEFAULT '0',
  `stock_max` int DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_article` (`code_article`),
  KEY `idx_designation` (`designation`),
  KEY `idx_actif` (`actif`),
  KEY `idx_qte_disponible` (`qte_disponible`),
  KEY `idx_code_designation` (`code_article`,`designation`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



--
-- Structure de la table `debug_log`
--

DROP TABLE IF EXISTS `debug_log`;
CREATE TABLE IF NOT EXISTS `debug_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `employes`
--

DROP TABLE IF EXISTS `employes`;
CREATE TABLE IF NOT EXISTS `employes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_id` int NOT NULL,
  `mail` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel1` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel2` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `idx_service` (`service_id`),
  KEY `idx_nom_prenom` (`nom`,`prenom`),
  KEY `idx_actif` (`actif`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Structure de la table `entrees`
--

DROP TABLE IF EXISTS `entrees`;
CREATE TABLE IF NOT EXISTS `entrees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fournisseur_id` int NOT NULL,
  `date` date NOT NULL,
  `fichier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fournisseur` (`fournisseur_id`),
  KEY `idx_date` (`date`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Structure de la table `equipes_inventaire`
--

DROP TABLE IF EXISTS `equipes_inventaire`;
CREATE TABLE IF NOT EXISTS `equipes_inventaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `noteseinv` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



--
-- Structure de la table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
CREATE TABLE IF NOT EXISTS `fournisseurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ville` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel1` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel2` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom_complet`),
  KEY `idx_actif` (`actif`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Structure de la table `historique_article`
--

DROP TABLE IF EXISTS `historique_article`;
CREATE TABLE IF NOT EXISTS `historique_article` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code_article` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation` enum('entree','sortie','retour_employe','retour_fournisseur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qte_entree` int DEFAULT NULL,
  `qte_sortie` int DEFAULT NULL,
  `qte_retour_employe` int DEFAULT NULL,
  `qte_retour_fournisseur` int DEFAULT NULL,
  `qte` int NOT NULL,
  `stock_avant_operation` int NOT NULL,
  `stock_apres_operation` int NOT NULL,
  `stock_initial` int NOT NULL DEFAULT '0',
  `stock_min` int NOT NULL DEFAULT '0',
  `stock_max` int NOT NULL DEFAULT '0',
  `article_id` int DEFAULT NULL,
  `entree_id` int DEFAULT NULL,
  `sortie_id` int DEFAULT NULL,
  `retour_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `date_operation` datetime NOT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `retour_fournisseur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_code_article` (`code_article`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_operation` (`operation`),
  KEY `idx_date_operation` (`date_operation`),
  KEY `idx_entree_id` (`entree_id`),
  KEY `idx_sortie_id` (`sortie_id`),
  KEY `idx_retour_id` (`retour_id`),
  KEY `user_id` (`user_id`),
  KEY `fk_historique_retour_fournisseur` (`retour_fournisseur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Structure de la table `inventaires`
--

DROP TABLE IF EXISTS `inventaires`;
CREATE TABLE IF NOT EXISTS `inventaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `equipe_id` int DEFAULT NULL,
  `fichier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `etat` enum('en_cours','valide','cloture') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `reinitialise` tinyint(1) DEFAULT '0',
  `user_id` int NOT NULL,
  `user_validation_id` int DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_reference` (`reference`),
  KEY `idx_equipe` (`equipe_id`),
  KEY `idx_etat` (`etat`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_validation` (`user_validation_id`),
  KEY `idx_date_debut` (`date_debut`),
  KEY `idx_date_fin` (`date_fin`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `ligne_entrees`
--

DROP TABLE IF EXISTS `ligne_entrees`;
CREATE TABLE IF NOT EXISTS `ligne_entrees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entree_id` int NOT NULL,
  `article_id` int NOT NULL,
  `code_article` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qte_entree` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entree` (`entree_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `ligne_inventaires`
--

DROP TABLE IF EXISTS `ligne_inventaires`;
CREATE TABLE IF NOT EXISTS `ligne_inventaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventaire_id` int NOT NULL,
  `article_id` int NOT NULL,
  `code_article` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qte_theorique` int NOT NULL,
  `qte_physique` int DEFAULT '0',
  `ecart` int DEFAULT '0',
  `observation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventaire` (`inventaire_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `ligne_retours`
--

DROP TABLE IF EXISTS `ligne_retours`;
CREATE TABLE IF NOT EXISTS `ligne_retours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `retour_id` int NOT NULL,
  `article_id` int NOT NULL,
  `code_article` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qte_retour` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retour` (`retour_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `ligne_retour_fournisseur`
--

DROP TABLE IF EXISTS `ligne_retour_fournisseur`;
CREATE TABLE IF NOT EXISTS `ligne_retour_fournisseur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `retour_fournisseur_id` int NOT NULL,
  `article_id` int NOT NULL,
  `code_article` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qte` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_retour_fournisseur_id` (`retour_fournisseur_id`),
  KEY `idx_article_id` (`article_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `ligne_sorties`
--

DROP TABLE IF EXISTS `ligne_sorties`;
CREATE TABLE IF NOT EXISTS `ligne_sorties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sortie_id` int NOT NULL,
  `article_id` int NOT NULL,
  `code_article` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qte_sortie` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sortie` (`sortie_id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_code_article` (`code_article`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

DROP TABLE IF EXISTS `parametres`;
CREATE TABLE IF NOT EXISTS `parametres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_etablissement` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tel_fixe` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel_mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `nom_etablissement`, `logo`, `adresse`, `tel_fixe`, `tel_mobile`, `fax`, `email`, `site_web`, `created_at`, `updated_at`) VALUES
(1, 'IRA MEDENINE', 'logo.png', 'ROUTE DU DJORF KM 22.5 MEDENINE 4119', '75633005', '00', '75633006', 'ira@ira.agrinet.tn', 'https://www.ira.agrinet.tn', '2025-11-27 15:39:52', '2026-01-29 08:11:18');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`module`,`action`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `nom`, `module`, `action`, `description`, `created_at`) VALUES
(1, 'Articles - Voir', 'articles', 'view', 'Voir les articles', '2025-11-27 15:39:52'),
(2, 'Articles - Ajouter', 'articles', 'create', 'Ajouter des articles', '2025-11-27 15:39:52'),
(3, 'Articles - Modifier', 'articles', 'update', 'Modifier des articles', '2025-11-27 15:39:52'),
(4, 'Articles - Supprimer', 'articles', 'delete', 'Supprimer des articles', '2025-11-27 15:39:52'),
(5, 'Articles - Modifier Stock Initial', 'articles', 'update_stock_initial', 'Modifier le stock initial', '2025-11-27 15:39:52'),
(6, 'Entrées - Voir', 'entrees', 'view', 'Voir les entrées', '2025-11-27 15:39:52'),
(7, 'Entrées - Ajouter', 'entrees', 'create', 'Ajouter des entrées', '2025-11-27 15:39:52'),
(8, 'Entrées - Modifier', 'entrees', 'update', 'Modifier des entrées', '2025-11-27 15:39:52'),
(9, 'Entrées - Supprimer', 'entrees', 'delete', 'Supprimer des entrées', '2025-11-27 15:39:52'),
(10, 'Sorties - Voir', 'sorties', 'view', 'Voir les sorties', '2025-11-27 15:39:52'),
(11, 'Sorties - Ajouter', 'sorties', 'create', 'Ajouter des sorties', '2025-11-27 15:39:52'),
(12, 'Sorties - Modifier', 'sorties', 'update', 'Modifier des sorties', '2025-11-27 15:39:52'),
(13, 'Sorties - Supprimer', 'sorties', 'delete', 'Supprimer des sorties', '2025-11-27 15:39:52'),
(14, 'Retours - Voir', 'retours', 'view', 'Voir les retours', '2025-11-27 15:39:52'),
(15, 'Retours - Ajouter', 'retours', 'create', 'Ajouter des retours', '2025-11-27 15:39:52'),
(16, 'Retours - Modifier', 'retours', 'update', 'Modifier des retours', '2025-11-27 15:39:52'),
(17, 'Retours - Supprimer', 'retours', 'delete', 'Supprimer des retours', '2025-11-27 15:39:52'),
(18, 'Inventaires - Voir', 'inventaires', 'view', 'Voir les inventaires', '2025-11-27 15:39:52'),
(19, 'Inventaires - Ajouter', 'inventaires', 'create', 'Ajouter des inventaires', '2025-11-27 15:39:52'),
(20, 'Inventaires - Modifier', 'inventaires', 'update', 'Modifier des inventaires', '2025-11-27 15:39:52'),
(21, 'Inventaires - Supprimer', 'inventaires', 'delete', 'Supprimer des inventaires', '2025-11-27 15:39:52'),
(22, 'Inventaires - Valider', 'inventaires', 'validate', 'Valider les inventaires', '2025-11-27 15:39:52'),
(23, 'Inventaires - Réinitialiser', 'inventaires', 'reset', 'Réinitialiser le stock', '2025-11-27 15:39:52'),
(24, 'Utilisateurs - Voir', 'users', 'view', 'Voir les utilisateurs', '2025-11-27 15:39:52'),
(25, 'Utilisateurs - Ajouter', 'users', 'create', 'Ajouter des utilisateurs', '2025-11-27 15:39:52'),
(26, 'Utilisateurs - Modifier', 'users', 'update', 'Modifier des utilisateurs', '2025-11-27 15:39:52'),
(27, 'Utilisateurs - Supprimer', 'users', 'delete', 'Supprimer des utilisateurs', '2025-11-27 15:39:52'),
(28, 'Paramètres - Voir', 'parametres', 'view', 'Voir les paramètres', '2025-11-27 15:39:52'),
(29, 'Paramètres - Modifier', 'parametres', 'update', 'Modifier les paramètres', '2025-11-27 15:39:52'),
(30, 'Rapports - Voir', 'rapports', 'view', 'Voir les rapports', '2025-11-27 15:39:52'),
(31, 'Rapports - Exporter', 'rapports', 'export', 'Exporter les rapports', '2025-11-27 15:39:52'),
(32, 'Retours Fournisseur - Voir', 'retour_fournisseur', 'view', 'Voir les retours fournisseur', '2026-01-07 10:36:06'),
(33, 'Retours Fournisseur - Ajouter', 'retour_fournisseur', 'create', 'Ajouter des retours fournisseur', '2026-01-07 10:36:06'),
(34, 'Retours Fournisseur - Modifier', 'retour_fournisseur', 'update', 'Modifier des retours fournisseur', '2026-01-07 10:36:06'),
(35, 'Retours Fournisseur - Supprimer', 'retour_fournisseur', 'delete', 'Supprimer des retours fournisseur', '2026-01-07 10:36:06'),
(36, 'Retours Fournisseur - PDF', 'retour_fournisseur', 'pdf', 'Générer PDF des retours fournisseur', '2026-01-07 10:36:06'),
(37, 'Mouvements - Voir', 'mouvements', 'view', 'Voir l\'historique des mouvements', '2026-01-07 14:09:28'),
(38, 'Mouvements - Exporter', 'mouvements', 'export', 'Exporter l\'historique des mouvements', '2026-01-07 14:09:28');

-- --------------------------------------------------------

--
-- Structure de la table `retours`
--

DROP TABLE IF EXISTS `retours`;
CREATE TABLE IF NOT EXISTS `retours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `date` date NOT NULL,
  `fichier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_employe` (`employe_id`),
  KEY `idx_date` (`date`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `retour_fournisseur`
--

DROP TABLE IF EXISTS `retour_fournisseur`;
CREATE TABLE IF NOT EXISTS `retour_fournisseur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fournisseur_id` int NOT NULL,
  `date` date NOT NULL,
  `fichier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fournisseur_id` (`fournisseur_id`),
  KEY `idx_date` (`date`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `nom`, `description`, `created_at`) VALUES
(1, 'admin', 'Administrateur système avec tous les droits', '2025-11-27 15:39:52'),
(2, 'gestionnaire', 'Gestionnaire de stock avec droits étendus', '2025-11-27 15:39:52'),
(3, 'utilisateur', 'Utilisateur standard avec droits limités', '2025-11-27 15:39:52');

-- --------------------------------------------------------

--
-- Structure de la table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_role_perm_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2026-01-05 07:25:17'),
(1, 2, '2026-01-05 07:25:17'),
(1, 3, '2026-01-05 07:25:17'),
(1, 4, '2026-01-05 07:25:17'),
(1, 5, '2026-01-05 07:25:17'),
(1, 6, '2026-01-05 07:25:17'),
(1, 7, '2026-01-05 07:25:17'),
(1, 8, '2026-01-05 07:25:17'),
(1, 9, '2026-01-05 07:25:17'),
(1, 10, '2026-01-05 07:25:17'),
(1, 11, '2026-01-05 07:25:17'),
(1, 13, '2026-01-05 07:25:17'),
(1, 14, '2026-01-05 07:25:17'),
(1, 15, '2026-01-05 07:25:17'),
(1, 16, '2026-01-05 07:25:17'),
(1, 17, '2026-01-05 07:25:17'),
(1, 24, '2026-01-05 07:25:17'),
(1, 25, '2026-01-05 07:25:17'),
(1, 26, '2026-01-05 07:25:17'),
(1, 27, '2026-01-05 07:25:17'),
(1, 28, '2026-01-05 07:25:17'),
(1, 29, '2026-01-05 07:25:17'),
(1, 30, '2026-01-05 07:25:17'),
(1, 31, '2026-01-05 07:25:17'),
(1, 32, '2026-01-07 10:36:07'),
(1, 33, '2026-01-07 10:36:07'),
(1, 34, '2026-01-07 10:36:07'),
(1, 35, '2026-01-07 10:36:07'),
(1, 36, '2026-01-07 10:36:07'),
(1, 37, '2026-01-07 14:12:16'),
(1, 38, '2026-01-07 14:12:16'),
(2, 1, '2025-11-27 15:39:52'),
(2, 2, '2025-11-27 15:39:52'),
(2, 3, '2025-11-27 15:39:52'),
(2, 5, '2025-11-27 15:39:52'),
(2, 6, '2025-11-27 15:39:52'),
(2, 7, '2025-11-27 15:39:52'),
(2, 8, '2025-11-27 15:39:52'),
(2, 10, '2025-11-27 15:39:52'),
(2, 11, '2025-11-27 15:39:52'),
(2, 12, '2025-11-27 15:39:52'),
(2, 14, '2025-11-27 15:39:52'),
(2, 15, '2025-11-27 15:39:52'),
(2, 16, '2025-11-27 15:39:52'),
(2, 18, '2025-11-27 15:39:52'),
(2, 19, '2025-11-27 15:39:52'),
(2, 20, '2025-11-27 15:39:52'),
(2, 22, '2025-11-27 15:39:52'),
(2, 23, '2025-11-27 15:39:52'),
(2, 30, '2025-11-27 15:39:52'),
(2, 31, '2025-11-27 15:39:52'),
(2, 32, '2026-01-07 10:36:07'),
(2, 33, '2026-01-07 10:36:07'),
(2, 34, '2026-01-07 10:36:07'),
(2, 36, '2026-01-07 10:36:07'),
(2, 37, '2026-01-07 14:12:16'),
(2, 38, '2026-01-07 14:12:16'),
(3, 1, '2026-01-07 11:18:28'),
(3, 2, '2026-01-07 11:18:28'),
(3, 3, '2026-01-07 11:18:28'),
(3, 6, '2026-01-07 11:18:28'),
(3, 7, '2026-01-07 11:18:28'),
(3, 8, '2026-01-07 11:18:28'),
(3, 10, '2026-01-07 11:18:28'),
(3, 11, '2026-01-07 11:18:28'),
(3, 12, '2026-01-07 11:18:28'),
(3, 14, '2026-01-07 11:18:28'),
(3, 15, '2026-01-07 11:18:28'),
(3, 16, '2026-01-07 11:18:28'),
(3, 28, '2026-01-07 11:18:28'),
(3, 30, '2026-01-07 11:18:28'),
(3, 31, '2026-01-07 11:18:28'),
(3, 32, '2026-01-07 11:18:28'),
(3, 33, '2026-01-07 11:18:28'),
(3, 34, '2026-01-07 11:18:28'),
(3, 36, '2026-01-07 11:18:28');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `nom`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Informatique', 'Service informatique', '2025-11-27 15:39:52', '2025-11-27 15:39:52'),
(2, 'Ressources Humaines', 'Service RH', '2025-11-27 15:39:52', '2025-11-27 15:39:52'),
(3, 'Comptabilité', 'Service comptabilité', '2025-11-27 15:39:52', '2025-11-27 15:39:52'),
(4, 'Direction', 'Direction générale', '2025-11-27 15:39:52', '2025-11-27 15:39:52'),
(5, 'Commercial', 'Service commercial', '2025-11-27 15:39:52', '2025-11-27 15:39:52');

-- --------------------------------------------------------

--
-- Structure de la table `sorties`
--

DROP TABLE IF EXISTS `sorties`;
CREATE TABLE IF NOT EXISTS `sorties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_id` int NOT NULL,
  `employe_id` int NOT NULL,
  `date` date NOT NULL,
  `fichier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_employe` (`employe_id`),
  KEY `idx_date` (`date`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `traces`
--

DROP TABLE IF EXISTS `traces`;
CREATE TABLE IF NOT EXISTS `traces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`table_name`),
  KEY `idx_created` (`created_at`),
  KEY `idx_composite` (`module`,`action`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=537 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mail` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `mail` (`mail`),
  KEY `idx_role` (`role_id`),
  KEY `idx_actif` (`actif`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `mail`, `login`, `password`, `role_id`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'Super', 'admin@stock.com', 'admin', '$2y$10$TxwexqmvndwwAcV2mC1sGuJL/in4vsJFaCGriMDLWjDNAvhbqJshK', 1, 1, '2025-11-27 15:39:52', '2025-11-28 07:07:11'),
(2, 'Gestionnaire', 'Principal', 'gestionnaire@stock.com', 'gestionnaire', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, '2025-11-27 15:39:52', '2025-11-27 15:39:52'),
(3, 'Utilisateur', 'Standard', 'user@stock.com', 'user', '$2y$10$ca9g1zx..7FgrdFfTtZ6E.4OJWby4QydwBCkd2n9zPYrOmUgEcMgu', 3, 1, '2025-11-27 15:39:52', '2026-01-07 11:19:12');

--
-- Contraintes pour les tables déchargées
--


--
-- Contraintes pour la table `employes`
--
ALTER TABLE `employes`
  ADD CONSTRAINT `fk_employe_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Contraintes pour la table `entrees`
--
ALTER TABLE `entrees`
  ADD CONSTRAINT `fk_entree_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `fk_entree_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `historique_article`
--
ALTER TABLE `historique_article`
  ADD CONSTRAINT `fk_historique_retour_fournisseur` FOREIGN KEY (`retour_fournisseur_id`) REFERENCES `retour_fournisseur` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historique_article_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historique_article_ibfk_2` FOREIGN KEY (`entree_id`) REFERENCES `entrees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historique_article_ibfk_3` FOREIGN KEY (`sortie_id`) REFERENCES `sorties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historique_article_ibfk_4` FOREIGN KEY (`retour_id`) REFERENCES `retours` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historique_article_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `inventaires`
--
ALTER TABLE `inventaires`
  ADD CONSTRAINT `fk_inventaire_equipe` FOREIGN KEY (`equipe_id`) REFERENCES `equipes_inventaire` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventaire_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_inventaire_user_validation` FOREIGN KEY (`user_validation_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `ligne_entrees`
--
ALTER TABLE `ligne_entrees`
  ADD CONSTRAINT `fk_ligne_entree_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_ligne_entree_entree` FOREIGN KEY (`entree_id`) REFERENCES `entrees` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ligne_inventaires`
--
ALTER TABLE `ligne_inventaires`
  ADD CONSTRAINT `fk_ligne_inventaire_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_ligne_inventaire_inventaire` FOREIGN KEY (`inventaire_id`) REFERENCES `inventaires` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ligne_retours`
--
ALTER TABLE `ligne_retours`
  ADD CONSTRAINT `fk_ligne_retour_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_ligne_retour_retour` FOREIGN KEY (`retour_id`) REFERENCES `retours` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ligne_retour_fournisseur`
--
ALTER TABLE `ligne_retour_fournisseur`
  ADD CONSTRAINT `ligne_retour_fournisseur_ibfk_1` FOREIGN KEY (`retour_fournisseur_id`) REFERENCES `retour_fournisseur` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ligne_retour_fournisseur_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `ligne_sorties`
--
ALTER TABLE `ligne_sorties`
  ADD CONSTRAINT `fk_ligne_sortie_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `fk_ligne_sortie_sortie` FOREIGN KEY (`sortie_id`) REFERENCES `sorties` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `retours`
--
ALTER TABLE `retours`
  ADD CONSTRAINT `fk_retour_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`),
  ADD CONSTRAINT `fk_retour_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `fk_retour_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `retour_fournisseur`
--
ALTER TABLE `retour_fournisseur`
  ADD CONSTRAINT `retour_fournisseur_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `retour_fournisseur_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_perm_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sorties`
--
ALTER TABLE `sorties`
  ADD CONSTRAINT `fk_sortie_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`),
  ADD CONSTRAINT `fk_sortie_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `fk_sortie_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `traces`
--
ALTER TABLE `traces`
  ADD CONSTRAINT `fk_trace_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
