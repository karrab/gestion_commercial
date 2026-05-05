-- Migration: Ajout des permissions referentiel (services, employes, fournisseurs, equipes)
-- A executer sur les bases de donnees existantes

INSERT IGNORE INTO `permissions` (`nom`, `module`, `action`, `description`) VALUES
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

-- Donner toutes les nouvelles permissions au role admin (id=1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`
WHERE `module` IN ('services', 'employes', 'fournisseurs', 'equipes');
