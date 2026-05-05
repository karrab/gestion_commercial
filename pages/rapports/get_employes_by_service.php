<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../classes/Database.php';

$db = Database::getInstance();

$service_id = $_GET['service_id'] ?? '';

if (!empty($service_id)) {
    $stmt = $db->getConnection()->prepare("SELECT id, nom, prenom FROM employes WHERE service_id = :service_id ORDER BY nom, prenom");
    $stmt->bindValue(':service_id', $service_id);
    $stmt->execute();
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si aucun service n'est spécifié, retourner tous les employés
    $stmt = $db->getConnection()->prepare("SELECT id, nom, prenom FROM employes ORDER BY nom, prenom");
    $stmt->execute();
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($employes);