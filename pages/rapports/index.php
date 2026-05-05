<?php
$page_title = 'Rapports';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('rapports', 'view');
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-bar-chart"></i> Rapports</h2>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-box-arrow-in-down"></i> Rapport des entrées</h5>
                    <p class="card-text">Consulter les entrées de stock par période et fournisseur</p>
                    <a href="<?php echo BASE_URL; ?>/pages/rapports/entrees.php" class="btn btn-primary">Consulter</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-box-arrow-up"></i> Rapport des sorties</h5>
                    <p class="card-text">Consulter les sorties de stock par période et service</p>
                    <a href="<?php echo BASE_URL; ?>/pages/rapports/sorties.php" class="btn btn-primary">Consulter</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-boxes"></i> État du stock</h5>
                    <p class="card-text">Consulter l'état actuel du stock avec alertes</p>
                    <a href="<?php echo BASE_URL; ?>/pages/rapports/stock.php" class="btn btn-primary">Consulter</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clock-history"></i> Traces d'activité</h5>
                    <p class="card-text">Consulter l'historique des actions des utilisateurs</p>
                    <a href="<?php echo BASE_URL; ?>/pages/rapports/traces.php" class="btn btn-primary">Consulter</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
