<?php
$page_title = 'À propos';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

$db->prepare("SELECT * FROM parametres WHERE id = 1");
$parametres = $db->fetch();

$db->prepare("SELECT * FROM versions ORDER BY date DESC, id DESC");
$versions = $db->fetchAll();

$derniere_version = !empty($versions) ? $versions[0] : null;
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-info-circle"></i> À propos</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">À propos</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Ligne 1 : Établissement (gauche) + Application (droite) -->
    <div class="row mb-4">

        <!-- Card Informations de l'Établissement -->
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-building"></i> <strong>Informations de l'Établissement</strong>
                </div>
                <div class="card-body">
                    <?php if ($parametres): ?>

                        <?php
                        $logo_file = ROOT_PATH . '/uploads/logo/' . $parametres['logo'];
                        if (!empty($parametres['logo']) && file_exists($logo_file)):
                        ?>
                        <div class="text-center mb-4">
                            <img src="<?php echo BASE_URL . '/uploads/logo/' . htmlspecialchars($parametres['logo']); ?>"
                                 alt="Logo établissement"
                                 style="max-height:150px; max-width:300px; object-fit:contain;">
                        </div>
                        <?php endif; ?>

                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th style="width:150px;"><i class="bi bi-building text-primary"></i> Établissement</th>
                                    <td><strong><?php echo htmlspecialchars($parametres['nom_etablissement'] ?? '-'); ?></strong></td>
                                </tr>
                                <?php if (!empty($parametres['adresse'])): ?>
                                <tr>
                                    <th><i class="bi bi-geo-alt text-primary"></i> Adresse</th>
                                    <td><?php echo nl2br(htmlspecialchars($parametres['adresse'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($parametres['tel_fixe'])): ?>
                                <tr>
                                    <th><i class="bi bi-telephone text-primary"></i> Tél. fixe</th>
                                    <td><?php echo htmlspecialchars($parametres['tel_fixe']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($parametres['tel_mobile'])): ?>
                                <tr>
                                    <th><i class="bi bi-phone text-primary"></i> Tél. mobile</th>
                                    <td><?php echo htmlspecialchars($parametres['tel_mobile']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($parametres['fax'])): ?>
                                <tr>
                                    <th><i class="bi bi-printer text-primary"></i> Fax</th>
                                    <td><?php echo htmlspecialchars($parametres['fax']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($parametres['email'])): ?>
                                <tr>
                                    <th><i class="bi bi-envelope text-primary"></i> Email</th>
                                    <td><a href="mailto:<?php echo htmlspecialchars($parametres['email']); ?>"><?php echo htmlspecialchars($parametres['email']); ?></a></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($parametres['site_web'])): ?>
                                <tr>
                                    <th><i class="bi bi-globe text-primary"></i> Site web</th>
                                    <td><a href="<?php echo htmlspecialchars($parametres['site_web']); ?>" target="_blank"><?php echo htmlspecialchars($parametres['site_web']); ?></a></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    <?php else: ?>
                        <p class="text-muted text-center mb-0">
                            <i class="bi bi-exclamation-circle"></i> Aucune information configurée.
                            <?php if ($auth->isAdmin()): ?>
                                <a href="<?php echo BASE_URL; ?>/pages/parametres/edit.php">Configurer</a>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card Informations de l'Application -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-app-indicator"></i> <strong>Informations de l'Application</strong>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <th style="width:150px;"><i class="bi bi-box-seam text-success"></i> Nom</th>
                                <td><strong><?php echo htmlspecialchars(APP_NAME); ?></strong></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-tag text-success"></i> Version</th>
                                <td>
                                    <?php if ($derniere_version): ?>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($derniere_version['num_version']); ?></span>
                                        &nbsp;<small class="text-muted"><?php echo date('d/m/Y', strtotime($derniere_version['date'])); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars(APP_VERSION); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-person-gear text-success"></i> Développé par</th>
                                <td><?php echo htmlspecialchars($derniere_version['developpe_par'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-diagram-3 text-success"></i> Direction</th>
                                <td><?php echo htmlspecialchars($derniere_version['direction'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th><i class="bi bi-stack text-success"></i> Technologies</th>
                                <td>PHP, MySQL, Bootstrap 5, jQuery, Select2</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Ligne 2 : Historique des Versions (pleine largeur) -->
    <div class="row">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-clock-history"></i> <strong>Historique des Versions</strong>
                    <span class="badge bg-light text-dark ms-2"><?php echo count($versions); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($versions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Aucune version enregistrée
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">Version</th>
                                        <th width="12%">Date</th>
                                        <th width="20%">Développé par</th>
                                        <th width="20%">Direction</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($versions as $v): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($v['num_version']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($v['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($v['developpe_par'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($v['direction'] ?? '-'); ?></td>
                                            <td><small><?php echo nl2br(htmlspecialchars($v['notes'] ?? '')); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($auth->isAdmin()): ?>
                <div class="card-footer text-end">
                    <a href="<?php echo BASE_URL; ?>/pages/versions/index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Gérer les versions
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
