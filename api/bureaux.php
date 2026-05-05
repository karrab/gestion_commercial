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
    $sql = "SELECT b.id, b.code_local, b.service_id, s.nom as service_nom, b.employe_id,
                   e.nom as employe_nom, e.prenom as employe_prenom
            FROM bureaux b
            INNER JOIN services s ON b.service_id = s.id
            LEFT JOIN employes e ON b.employe_id = e.id
            WHERE b.id = :id";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $text = $item['code_local'];
        if ($item['service_nom']) {
            $text .= ' - ' . $item['service_nom'];
        }
        if ($item['employe_nom']) {
            $text .= ' (' . $item['employe_nom'] . ' ' . $item['employe_prenom'] . ')';
        }

        $results = [[
            'id' => $item['id'],
            'text' => $text,
            'code_local' => $item['code_local'],
            'service_id' => $item['service_id'],
            'service_nom' => $item['service_nom'],
            'employe_id' => $item['employe_id']
        ]];
    } else {
        $results = [];
    }

    echo json_encode($results);
    exit;
}

// Recherche normale
$search = $_GET['search'] ?? $_GET['q'] ?? $_GET['term'] ?? '';
$service_id = $_GET['service_id'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$sql = "SELECT b.id, b.code_local, b.service_id, s.nom as service_nom, b.employe_id,
               e.nom as employe_nom, e.prenom as employe_prenom
        FROM bureaux b
        INNER JOIN services s ON b.service_id = s.id
        LEFT JOIN employes e ON b.employe_id = e.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (b.code_local LIKE :search OR s.nom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND b.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY b.code_local ASC LIMIT :limit OFFSET :offset";

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
    $text = $item['code_local'];
    if ($item['service_nom']) {
        $text .= ' - ' . $item['service_nom'];
    }
    if ($item['employe_nom']) {
        $text .= ' (' . $item['employe_nom'] . ' ' . $item['employe_prenom'] . ')';
    }

    $results[] = [
        'id' => $item['id'],
        'text' => $text,
        'code_local' => $item['code_local'],
        'service_id' => $item['service_id'],
        'service_nom' => $item['service_nom'],
        'employe_id' => $item['employe_id']
    ];
}

echo json_encode($results);
