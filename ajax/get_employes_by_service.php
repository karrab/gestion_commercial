<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

$service_id = $_POST['service_id'] ?? 0;

if (empty($service_id)) {
    echo json_encode(['success' => false, 'message' => 'Service ID requis']);
    exit;
}

$db->prepare("
    SELECT id, matricule, nom, prenom 
    FROM employes 
    WHERE service_id = :service_id AND actif = 1 
    ORDER BY nom, prenom
");
$db->bind(':service_id', $service_id);
$employes = $db->fetchAll();

echo json_encode([
    'success' => true,
    'employes' => $employes
]);