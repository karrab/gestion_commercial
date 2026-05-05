<?php
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('versions', 'delete');
$db = Database::getInstance();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/pages/versions/index.php');
    exit;
}

$db->prepare("SELECT num_version FROM versions WHERE id = :id");
$db->bind(':id', $id);
$version = $db->fetch();

if (!$version) {
    $_SESSION['error'] = 'Version introuvable.';
    header('Location: ' . BASE_URL . '/pages/versions/index.php');
    exit;
}

$db->prepare("DELETE FROM versions WHERE id = :id");
$db->bind(':id', $id);
$db->execute();

$auth->logTrace($auth->getUserId(), 'versions', 'delete', 'versions', $id, 'Suppression version ' . $version['num_version']);

$_SESSION['success'] = 'Version ' . $version['num_version'] . ' supprimée.';
header('Location: ' . BASE_URL . '/pages/versions/index.php');
exit;
