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

// Cas spécial: retourner TOUS les articles actifs (pour inventaires)
if (isset($_GET['all_active']) && $_GET['all_active'] == 1) {
    $sql = "SELECT id, code_article, designation, qte_disponible
            FROM articles
            WHERE actif = 1
            ORDER BY code_article ASC";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $items = $stmt->fetchAll();

    // Formater pour Select2
    $results = [];
    foreach ($items as $item) {
        $results[] = [
            'id' => $item['id'],
            'text' => $item['code_article'] . ' - ' . $item['designation']
        ];
    }

    echo json_encode($results);
    exit;
}

// Cas normal: recherche avec pagination (pour Select2)
$search = $_GET['search'] ?? $_GET['q'] ?? $_GET['term'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$sql = "SELECT id, code_article, designation, qte_disponible, stock_min, stock_max
        FROM articles
        WHERE actif = 1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (code_article LIKE :search OR designation LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY code_article ASC LIMIT :limit OFFSET :offset";

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
        'text' => $item['code_article'] . ' - ' . $item['designation'],
        'qte_disponible' => $item['qte_disponible']
    ];
}

echo json_encode($results);
