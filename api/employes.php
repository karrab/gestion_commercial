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
    $sql = "SELECT e.id, e.matricule, e.nom, e.prenom, e.service_id, s.nom as service_nom, b.id as bureau_id, b.code_local
            FROM employes e
            INNER JOIN services s ON e.service_id = s.id
            LEFT JOIN bureaux b ON b.employe_id = e.id
            WHERE e.id = :id";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $results = [[
            'id' => $item['id'],
            'text' => $item['matricule'] . ' - ' . $item['nom'] . ' ' . $item['prenom'],
            'bureau_id' => $item['bureau_id'],
            'code_local' => $item['code_local']
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
$sql = "SELECT e.id, e.matricule, e.nom, e.prenom, e.service_id, s.nom as service_nom
        FROM employes e
        INNER JOIN services s ON e.service_id = s.id
        WHERE e.actif = 1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (e.matricule LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND e.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY e.nom ASC, e.prenom ASC LIMIT :limit OFFSET :offset";

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
        'text' => $item['matricule'] . ' - ' . $item['nom'] . ' ' . $item['prenom'],
        'matricule' => $item['matricule'],
        'nom' => $item['nom'],
        'prenom' => $item['prenom'],
        'service_nom' => $item['service_nom']
    ];
}

echo json_encode($results);
