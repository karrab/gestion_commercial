<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('services', 'delete');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM services WHERE id = :id");
$db->bind(':id', $id);
$service = $db->fetch();

if (!$service) {
    $_SESSION['error'] = 'Service introuvable.';
    header('Location: ' . BASE_URL . '/pages/services/index.php');
    exit;
}

// Vérifier si le service est utilisé
$db->prepare("SELECT COUNT(*) as count FROM employes WHERE service_id = :id");
$db->bind(':id', $id);
$nb_employes = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE service_id = :id");
$db->bind(':id', $id);
$nb_bureaux = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE service_id = :id OR service_affectation_id = :id");
$db->bind(':id', $id);
$nb_sorties = $db->fetch()['count'];

if ($nb_employes > 0 || $nb_bureaux > 0 || $nb_sorties > 0) {
    $_SESSION['error'] = 'Impossible de supprimer ce service car il est utilisé (' . $nb_employes . ' employé(s), ' . $nb_bureaux . ' bureau(x), ' . $nb_sorties . ' sortie(s)).';
    header('Location: ' . BASE_URL . '/pages/services/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM services WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'services', 'delete', 'services', $id, "Suppression: " . $service['nom']);

        $_SESSION['success'] = 'Service supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/services/index.php');
exit;
