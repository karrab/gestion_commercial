<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';

$auth = Auth::getInstance();
$auth->requirePermission('users', 'update');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/users/index.php');
    exit;
}

$db = Database::getInstance();
$id = $_POST['id'] ?? 0;

// Récupérer l'utilisateur
$db->prepare("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $id);
$user = $db->fetch();

if (!$user) {
    $_SESSION['error'] = 'Utilisateur introuvable.';
    header('Location: ' . BASE_URL . '/pages/users/index.php');
    exit;
}

// Empêcher la désactivation de soi-même
if ($user['id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = 'Vous ne pouvez pas désactiver votre propre compte.';
    header('Location: ' . BASE_URL . '/pages/users/view.php?id=' . $id);
    exit;
}

try {
    $new_status = $user['actif'] ? 0 : 1;

    $db->prepare("UPDATE users SET actif = :actif WHERE id = :id");
    $db->bind(':actif', $new_status);
    $db->bind(':id', $id);

    if ($db->execute()) {
        $action = $new_status ? 'activé' : 'désactivé';
        $_SESSION['success'] = "Utilisateur $action avec succès !";
    } else {
        $_SESSION['error'] = 'Erreur lors de la modification du statut.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/users/view.php?id=' . $id);
exit;
