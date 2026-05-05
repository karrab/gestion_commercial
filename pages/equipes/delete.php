<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('equipes', 'delete');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM equipes_inventaire WHERE id = :id");
$db->bind(':id', $id);
$equipe = $db->fetch();

if (!$equipe) {
    $_SESSION['error'] = 'Équipe introuvable.';
    header('Location: ' . BASE_URL . '/pages/equipes/index.php');
    exit;
}

// Vérifier si le equipe est utilisé
$db->prepare("SELECT COUNT(*) as count FROM employes WHERE equipe_id = :id");
$db->bind(':id', $id);
$nb_employes = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE equipe_id = :id");
$db->bind(':id', $id);
$nb_bureaux = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE equipe_id = :id OR equipe_affectation_id = :id");
$db->bind(':id', $id);
$nb_sorties = $db->fetch()['count'];

if ($nb_employes > 0 || $nb_bureaux > 0 || $nb_sorties > 0) {
    $_SESSION['error'] = 'Impossible de supprimer ce equipe car il est utilisé (' . $nb_employes . ' employé(s), ' . $nb_bureaux . ' bureau(x), ' . $nb_sorties . ' sortie(s)).';
    header('Location: ' . BASE_URL . '/pages/equipes/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM equipes WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'equipes', 'delete', 'equipes', $id, "Suppression: " . $equipe['nom']);

        $_SESSION['success'] = 'Équipe supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/equipes/index.php');
exit;
