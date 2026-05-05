<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

if ($id == $auth->getUserId()) {
    $_SESSION['error'] = 'Vous ne pouvez pas supprimer votre propre compte.';
    header('Location: index.php');
    exit;
}

$db->prepare("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $id);
$user = $db->fetch();

if (!$user) {
    $_SESSION['error'] = 'Utilisateur introuvable.';
    header('Location: index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM users WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'users', 'delete', 'users', $id, "Suppression: {$user['login']}");
        $_SESSION['success'] = 'Utilisateur supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
}

header('Location: index.php');
exit;
