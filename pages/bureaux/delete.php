<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('bureaux', 'delete');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$db->prepare("SELECT * FROM bureaux WHERE id = :id");
$db->bind(':id', $id);
$bureau = $db->fetch();

if (!$bureau) {
    $_SESSION['error'] = 'Bureau introuvable.';
    header('Location: ' . BASE_URL . '/pages/bureaux/index.php');
    exit;
}

// Vérifier si le bureau est utilisé
$db->prepare("SELECT COUNT(*) as count FROM sorties WHERE bureau_id = :id");
$db->bind(':id', $id);
$usage = $db->fetch();

if ($usage['count'] > 0) {
    $_SESSION['error'] = "Impossible de supprimer ce bureau car il est utilisé dans {$usage['count']} sortie(s).";
    header('Location: ' . BASE_URL . '/pages/bureaux/view.php?id=' . $id);
    exit;
}

// Suppression
try {
    $db->prepare("DELETE FROM bureaux WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'bureaux', 'delete', 'bureaux', $id, "Suppression: {$bureau['code_local']}");
        $_SESSION['success'] = 'Bureau supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/bureaux/index.php');
exit;
