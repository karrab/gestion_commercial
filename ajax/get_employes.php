<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$service_id = intval($_GET['service_id'] ?? 0);

if ($service_id) {
    $sql = "SELECT id, nom, prenom FROM employes WHERE service_id = :service_id AND actif = 1 ORDER BY nom, prenom";
    $db->prepare($sql);
    $db->bind(':service_id', $service_id);
    $db->execute();
    $employes = $db->fetchAll();
    echo json_encode($employes);
} else {
    echo json_encode([]);
}