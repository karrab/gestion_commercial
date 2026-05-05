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
    $sql = "SELECT id, numero, nom FROM armoires WHERE id = :id";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $results = [[
            'id' => $item['id'],
            'text' => $item['numero'] . ' - ' . $item['nom'],
            'numero' => $item['numero'],
            'nom' => $item['nom']
        ]];
    } else {
        $results = [];
    }

    echo json_encode($results);
    exit;
}

// Recherche normale
$search = $_GET['search'] ?? $_GET['q'] ?? $_GET['term'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$sql = "SELECT id, numero, nom
        FROM armoires
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (numero LIKE :search OR nom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY numero ASC LIMIT :limit OFFSET :offset";

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
        'text' => $item['numero'] . ' - ' . $item['nom'],
        'numero' => $item['numero'],
        'nom' => $item['nom']
    ];
}

echo json_encode($results);
