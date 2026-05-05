<?php
$page_title = 'Versions';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('versions', 'view');
$db = Database::getInstance();

$db->prepare("SELECT * FROM versions ORDER BY date DESC, id DESC");
$versions = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-tag"></i> Versions de l'application</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Versions</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($auth->hasPermission('versions', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/versions/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nouvelle version
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> Liste des versions
            <span class="badge bg-secondary ms-2"><?php echo count($versions); ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="10%">Version</th>
                            <th width="12%">Date</th>
                            <th width="20%">Développé par</th>
                            <th width="20%">Direction</th>
                            <th>Notes</th>
                            <th width="12%" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($versions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Aucune version enregistrée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($versions as $v): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($v['num_version']); ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($v['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($v['developpe_par'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['direction'] ?? '-'); ?></td>
                                    <td>
                                        <small><?php echo nl2br(htmlspecialchars($v['notes'] ?? '')); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($auth->hasPermission('versions', 'update')): ?>
                                            <a href="<?php echo BASE_URL; ?>/pages/versions/edit.php?id=<?php echo $v['id']; ?>"
                                               class="btn btn-sm btn-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('versions', 'delete')): ?>
                                            <a href="<?php echo BASE_URL; ?>/pages/versions/delete.php?id=<?php echo $v['id']; ?>"
                                               class="btn btn-sm btn-danger" title="Supprimer"
                                               onclick="return confirm('Supprimer la version <?php echo htmlspecialchars($v['num_version']); ?> ?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
