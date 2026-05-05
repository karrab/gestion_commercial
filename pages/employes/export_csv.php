<?php
// pages/employes/export_csv.php
require_once __DIR__ . '/../../../includes/header.php';

// Récupérer les paramètres
$search = $_GET['search'] ?? '';
$service_id = $_GET['service_id'] ?? '';

$db = Database::getInstance();

// Construire la requête (sans pagination pour l'export)
$sql = "SELECT e.*, s.nom as service_nom
        FROM employes e
        INNER JOIN services s ON e.service_id = s.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.matricule LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search OR e.mail LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND e.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY e.nom ASC, e.prenom ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$employes = $stmt->fetchAll();

// Nom du fichier
$filename = 'employes_' . date('Y-m-d_H-i-s') . '.csv';

// En-têtes pour le téléchargement CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Créer le fichier CSV
$output = fopen('php://output', 'w');

// Ajouter BOM pour Excel UTF-8
fputs($output, "\xEF\xBB\xBF");

// En-têtes CSV
fputcsv($output, [
    'Matricule',
    'Nom',
    'Prénom',
    'Service',
    'Email',
    'Téléphone 1',
    'Téléphone 2',
    'Statut',
    'Date création',
    'Date modification',
    'Notes'
], ';');

// Données
foreach ($employes as $employe) {
    fputcsv($output, [
        $employe['matricule'],
        $employe['nom'],
        $employe['prenom'],
        $employe['service_nom'],
        $employe['mail'] ?? '',
        $employe['tel1'] ?? '',
        $employe['tel2'] ?? '',
        $employe['actif'] ? 'Actif' : 'Inactif',
        date('d/m/Y H:i', strtotime($employe['created_at'])),
        date('d/m/Y H:i', strtotime($employe['updated_at'])),
        $employe['notes'] ?? ''
    ], ';');
}

fclose($output);
exit;
?>