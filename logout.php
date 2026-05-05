<?php
require_once __DIR__ . '/config/config.php';

$auth = new Auth();
$auth->logout();

$_SESSION['info'] = 'Vous avez été déconnecté avec succès.';

header('Location: ' . BASE_URL . '/login.php');
exit;
