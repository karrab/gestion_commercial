<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM armoires WHERE id = :id");
$db->bind(':id', $id);
$armoire = $db->fetch();

if (!$armoire) {
    $_SESSION['error'] = 'Armoire introuvable.';
    header('Location: ' . BASE_URL . '/pages/armoires/index.php');
    exit;
}

// Vérifier si le armoire est utilisé
$db->prepare("SELECT COUNT(*) as count FROM employes WHERE armoire_id = :id");
$db->bind(':id', $id);
$nb_employes = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE armoire_id = :id");
$db->bind(':id', $id);
$nb_bureaux = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE armoire_id = :id OR armoire_affectation_id = :id");
$db->bind(':id', $id);
$nb_sorties = $db->fetch()['count'];

if ($nb_employes > 0 || $nb_bureaux > 0 || $nb_sorties > 0) {
    $_SESSION['error'] = 'Impossible de supprimer ce armoire car il est utilisé (' . $nb_employes . ' employé(s), ' . $nb_bureaux . ' bureau(x), ' . $nb_sorties . ' sortie(s)).';
    header('Location: ' . BASE_URL . '/pages/armoires/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM armoires WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'armoires', 'delete', 'armoires', $id, "Suppression: " . $armoire['nom']);

        $_SESSION['success'] = 'Armoire supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/armoires/index.php');
exit;
