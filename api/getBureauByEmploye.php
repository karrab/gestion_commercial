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

// Récupérer l'employe_id
$employe_id = $_GET['employe_id'] ?? null;

if (empty($employe_id)) {
    echo json_encode(['bureau' => null]);
    exit;
}

// Rechercher le bureau de l'employé
$sql = "SELECT b.id, b.code_local, b.service_id, s.nom as service_nom
        FROM bureaux b
        INNER JOIN services s ON b.service_id = s.id
        WHERE b.employe_id = :employe_id
        LIMIT 1";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bindValue(':employe_id', intval($employe_id), PDO::PARAM_INT);
$stmt->execute();
$bureau = $stmt->fetch(PDO::FETCH_ASSOC);

if ($bureau) {
    echo json_encode([
        'bureau' => [
            'id' => $bureau['id'],
            'code_local' => $bureau['code_local'],
            'text' => $bureau['code_local'] . ' - ' . $bureau['service_nom'],
            'service_id' => $bureau['service_id'],
            'service_nom' => $bureau['service_nom']
        ]
    ]);
} else {
    echo json_encode(['bureau' => null]);
}
