<?php
require_once __DIR__ . '/../config/config.php';
$auth = new Auth();

// Vérifier si l'utilisateur est connecté
$auth->requireLogin();

// Récupérer les informations de l'utilisateur
$current_user = $auth->getUser();

// Récupérer les paramètres de l'établissement
$db = Database::getInstance();
$db->prepare("SELECT * FROM parametres WHERE id = 1");
$parametres = $db->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap-icons.css">

    <!-- Select2 CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/select2.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/colReorder.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/fixedHeader.bootstrap5.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <!-- Favicon -->
    <?php if (!empty($parametres['logo'])): ?>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/uploads/logo/<?php echo htmlspecialchars($parametres['logo']); ?>">
    <?php endif; ?>
</head>
<body>
