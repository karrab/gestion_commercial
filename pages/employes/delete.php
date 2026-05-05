<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('employes', 'delete');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Verification de l'existence
$db->prepare("SELECT * FROM employes WHERE id = :id");
$db->bind(':id', $id);
$employe = $db->fetch();

if (!$employe) {
    $_SESSION['error'] = 'Employe introuvable.';
    header('Location: ' . BASE_URL . '/pages/employes/index.php');
    exit;
}

// Verifier si l'employe est reference dans les sorties
$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE employe_id = :id");
$db->bind(':id', $id);
$nb_sorties = $db->fetch()['count'];

if ($nb_sorties > 0) {
    $_SESSION['error'] = 'Impossible de supprimer cet employe car il est utilise dans ' . $nb_sorties . ' sortie(s).';
    header('Location: ' . BASE_URL . '/pages/employes/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM employes WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'employes', 'delete', 'employes', $id, 'Suppression: ' . $employe['nom']);
        $_SESSION['success'] = 'Employe supprime avec succes.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/employes/index.php');
exit;
