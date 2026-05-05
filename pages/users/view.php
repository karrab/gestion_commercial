<?php
$page_title = 'Détails utilisateur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('users', 'view');
$db = Database::getInstance();

$id = $_GET['id'] ?? 0;

// Récupérer l'utilisateur avec son rôle
$sql = "SELECT u.*, r.nom as role_nom, r.description as role_description
        FROM users u
        INNER JOIN roles r ON u.role_id = r.id
        WHERE u.id = :id";

$db->prepare($sql);
$db->bind(':id', $id);
$user = $db->fetch();

if (!$user) {
    $_SESSION['error'] = 'Utilisateur introuvable.';
    header('Location: ' . BASE_URL . '/pages/users/index.php');
    exit;
}

// Récupérer statistiques d'activité (si traces existe)
$sql_traces = "SELECT
                COUNT(*) as total_actions,
                MAX(created_at) as derniere_action
               FROM traces
               WHERE user_id = :user_id";
$db->prepare($sql_traces);
$db->bind(':user_id', $id);
$stats = $db->fetch();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/users/index.php">Utilisateurs</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/users/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <?php if ($auth->hasPermission('users', 'update')): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Informations personnelles -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-badge"></i> Informations personnelles
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Nom</label>
                            <p class="mb-0 fs-5"><strong><?php echo htmlspecialchars($user['nom']); ?></strong></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Prénom</label>
                            <p class="mb-0 fs-5"><strong><?php echo htmlspecialchars($user['prenom']); ?></strong></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small"><i class="bi bi-envelope"></i> Email</label>
                            <p class="mb-0">
                                <a href="mailto:<?php echo htmlspecialchars($user['mail']); ?>">
                                    <?php echo htmlspecialchars($user['mail']); ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small"><i class="bi bi-person"></i> Login</label>
                            <p class="mb-0">
                                <span class="badge bg-secondary fs-6">
                                    <?php echo htmlspecialchars($user['login']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small"><i class="bi bi-shield-check"></i> Rôle</label>
                            <p class="mb-0">
                                <span class="badge bg-info fs-6">
                                    <?php echo htmlspecialchars($user['role_nom']); ?>
                                </span>
                                <?php if (!empty($user['role_description'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($user['role_description']); ?></small>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Statut</label>
                            <p class="mb-0">
                                <?php if ($user['actif']): ?>
                                    <span class="badge bg-success fs-6">
                                        <i class="bi bi-check-circle"></i> Actif
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger fs-6">
                                        <i class="bi bi-x-circle"></i> Inactif
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques d'activité -->
            <?php if ($stats && $stats['total_actions'] > 0): ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-activity"></i> Activité
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small">Total actions enregistrées</label>
                            <p class="mb-0 fs-4">
                                <strong><?php echo number_format($stats['total_actions'], 0, ',', ' '); ?></strong>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Dernière action</label>
                            <p class="mb-0">
                                <?php if ($stats['derniere_action']): ?>
                                    <?php echo date('d/m/Y à H:i', strtotime($stats['derniere_action'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/pages/historique/index.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-clock-history"></i> Voir l'historique complet
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Informations système -->
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-info-circle"></i> Informations système
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Date de création</label>
                        <p class="mb-0">
                            <i class="bi bi-calendar-plus"></i>
                            <?php echo date('d/m/Y à H:i', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small">Dernière modification</label>
                        <p class="mb-0">
                            <i class="bi bi-calendar-check"></i>
                            <?php echo date('d/m/Y à H:i', strtotime($user['updated_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header bg-warning">
                    <i class="bi bi-gear"></i> Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($auth->hasPermission('users', 'update')): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Modifier l'utilisateur
                            </a>

                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form action="<?php echo BASE_URL; ?>/pages/users/toggle_status.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-<?php echo $user['actif'] ? 'secondary' : 'success'; ?> w-100"
                                            onclick="return confirm('<?php echo $user['actif'] ? 'Désactiver' : 'Activer'; ?> cet utilisateur ?');">
                                        <i class="bi bi-<?php echo $user['actif'] ? 'x-circle' : 'check-circle'; ?>"></i>
                                        <?php echo $user['actif'] ? 'Désactiver' : 'Activer'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($auth->hasPermission('users', 'delete') && $user['id'] != $_SESSION['user_id']): ?>
                            <hr>
                            <a href="<?php echo BASE_URL; ?>/pages/users/delete.php?id=<?php echo $user['id']; ?>"
                               class="btn btn-danger"
                               onclick="return confirm('⚠️ ATTENTION\n\nSupprimer définitivement cet utilisateur ?\n\nCette action est irréversible.');">
                                <i class="bi bi-trash"></i> Supprimer
                            </a>
                        <?php endif; ?>

                        <hr>
                        <a href="<?php echo BASE_URL; ?>/pages/users/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
