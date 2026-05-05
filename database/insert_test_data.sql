-- ============================================
-- Script d'insertion de données de test
-- ============================================

-- Vérifier et insérer des services de test
INSERT INTO `services` (`nom`, `notes`) VALUES
('Direction Générale', 'Service de direction'),
('Ressources Humaines', 'Gestion du personnel'),
('Informatique', 'Service IT'),
('Comptabilité', 'Service financier'),
('Logistique', 'Gestion des stocks et approvisionnements')
ON DUPLICATE KEY UPDATE nom=nom;

-- Vérifier et insérer des fournisseurs de test
INSERT INTO `fournisseurs` (`nom_complet`, `adresse`, `ville`, `pays`, `tel1`, `mail`, `actif`) VALUES
('Fournisseur Informatique SARL', '123 Rue de la Tech', 'Paris', 'France', '0123456789', 'contact@fournisseur-it.fr', 1),
('Bureautique Pro', '456 Avenue du Bureau', 'Lyon', 'France', '0987654321', 'info@bureautique-pro.fr', 1),
('Matériel Express', '789 Boulevard du Stock', 'Marseille', 'France', '0147258369', 'contact@materiel-express.fr', 1)
ON DUPLICATE KEY UPDATE nom_complet=nom_complet;

-- Récupérer les IDs des services pour les employés
SET @service_direction = (SELECT id FROM services WHERE nom = 'Direction Générale' LIMIT 1);
SET @service_rh = (SELECT id FROM services WHERE nom = 'Ressources Humaines' LIMIT 1);
SET @service_it = (SELECT id FROM services WHERE nom = 'Informatique' LIMIT 1);

-- Insérer des employés de test
INSERT INTO `employes` (`matricule`, `nom`, `prenom`, `service_id`, `mail`, `tel1`, `actif`) VALUES
('EMP001', 'DUPONT', 'Jean', @service_direction, 'j.dupont@entreprise.com', '0601020304', 1),
('EMP002', 'MARTIN', 'Marie', @service_rh, 'm.martin@entreprise.com', '0605060708', 1),
('EMP003', 'BERNARD', 'Pierre', @service_it, 'p.bernard@entreprise.com', '0609101112', 1),
('EMP004', 'DURAND', 'Sophie', @service_it, 's.durand@entreprise.com', '0613141516', 1)
ON DUPLICATE KEY UPDATE matricule=matricule;

-- Insérer des bureaux de test
INSERT INTO `bureaux` (`code_local`, `service_id`) VALUES
('B001', @service_direction),
('B002', @service_rh),
('B003', @service_it),
('B004', @service_it)
ON DUPLICATE KEY UPDATE code_local=code_local;

-- Insérer des armoires de test
INSERT INTO `armoires` (`numero`, `nom`) VALUES
('ARM-001', 'Armoire Fournitures Bureau'),
('ARM-002', 'Armoire Matériel Informatique'),
('ARM-003', 'Armoire Archive'),
('ARM-004', 'Armoire Stock Principal')
ON DUPLICATE KEY UPDATE numero=numero;

-- Insérer des équipes d'inventaire de test
INSERT INTO `equipes_inventaire` (`nom`, `description`) VALUES
('Équipe Alpha', 'Équipe principale d\'inventaire'),
('Équipe Beta', 'Équipe secondaire'),
('Équipe Gamma', 'Équipe spécialisée matériel IT')
ON DUPLICATE KEY UPDATE nom=nom;

-- Message de confirmation
SELECT '✅ Données de test insérées avec succès !' as message;

-- Vérification
SELECT 'Services' as table_name, COUNT(*) as count FROM services
UNION ALL
SELECT 'Fournisseurs', COUNT(*) FROM fournisseurs WHERE actif = 1
UNION ALL
SELECT 'Employés', COUNT(*) FROM employes WHERE actif = 1
UNION ALL
SELECT 'Bureaux', COUNT(*) FROM bureaux
UNION ALL
SELECT 'Armoires', COUNT(*) FROM armoires
UNION ALL
SELECT 'Équipes', COUNT(*) FROM equipes_inventaire;
