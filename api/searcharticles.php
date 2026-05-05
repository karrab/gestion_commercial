<?php
// searcharticles.php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$db = Database::getInstance();

$search = $_GET['search'] ?? $_GET['q'] ?? $_GET['term'] ?? '';

$sql = "SELECT id, code_article, designation, qte_disponible 
        FROM articles 
        WHERE actif = 1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (code_article LIKE :search1 OR designation LIKE :search2)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
}

$sql .= " ORDER BY code_article ASC LIMIT 20";

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
        'text' => $item['code_article'] . ' - ' . $item['designation'],
        'code_article' => $item['code_article'],
        'designation' => $item['designation'],
        'qte_disponible' => $item['qte_disponible']
    ];
}

echo json_encode($results);