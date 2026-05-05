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

// Recherche des services
$search = $_GET['search'] ?? $_GET['q'] ?? $_GET['term'] ?? '';

$sql = "SELECT id, nom
        FROM services
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND nom LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY nom ASC";

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
        'text' => $item['nom']
    ];
}

echo json_encode($results);