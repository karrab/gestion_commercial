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

// Si un ID spécifique est demandé
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT id, nom_complet as text FROM fournisseurs WHERE id = :id";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
    exit;
}

// Recherche normale
$search = $_GET['search'] ?? $_GET['q'] ?? $_GET['term'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$sql = "SELECT id, nom_complet, ville, pays, tel1, tel2
        FROM fournisseurs
        WHERE actif = 1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (nom_complet LIKE :search OR ville LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY nom_complet ASC LIMIT :limit OFFSET :offset";

$stmt = $db->getConnection()->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$items = $stmt->fetchAll();

// Formater pour Select2
$results = [];
foreach ($items as $item) {
    $results[] = [
        'id' => $item['id'],
        'text' => $item['nom_complet'],
        'ville' => $item['ville'] ?? '',
        'pays' => $item['pays'] ?? '',
        'tel1' => $item['tel1'] ?? ''
    ];
}

echo json_encode($results);
