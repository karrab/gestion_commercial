<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$db = Database::getInstance();

// Récupérer le service_id
$service_id = $_GET['service_id'] ?? null;

if (empty($service_id)) {
    echo json_encode([]);
    exit;
}

// Recherche des employés du service
$search = $_GET['search'] ?? $_GET['q'] ?? $_GET['term'] ?? '';

$sql = "SELECT e.id, e.matricule, e.nom, e.prenom, e.service_id, s.nom as service_nom
        FROM employes e
        INNER JOIN services s ON e.service_id = s.id
        WHERE e.actif = 1 AND e.service_id = :service_id";

$params = [':service_id' => $service_id];

if (!empty($search)) {
    $sql .= " AND (e.matricule LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY e.nom ASC, e.prenom ASC";

$stmt = $db->getConnection()->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$items = $stmt->fetchAll();

// Formater pour Select2
$results = [];
foreach ($items as $item) {
    $results[] = [
        'id' => $item['id'],
        'text' => $item['matricule'] . ' - ' . $item['nom'] . ' ' . $item['prenom'],
        'matricule' => $item['matricule'],
        'nom' => $item['nom'],
        'prenom' => $item['prenom'],
        'service_nom' => $item['service_nom']
    ];
}

echo json_encode($results);
