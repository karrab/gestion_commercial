<?php
$page_title = 'Détails de l\'employé';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('employes', 'view');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de l'employé avec son service
$db->prepare("SELECT e.*, s.nom as service_nom FROM employes e LEFT JOIN services s ON e.service_id = s.id WHERE e.id = :id");
$db->bind(':id', $id);
$employe = $db->fetch();

if (!$employe) {
    $_SESSION['error'] = 'Employé introuvable.';
    header('Location: ' . BASE_URL . '/pages/employes/index.php');
    exit;
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-person"></i> Détails de l'employé</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/employes/index.php">Employés</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <a href="<?php echo BASE_URL; ?>/pages/employes/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/employes/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations générales
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Matricule</th>
                            <td><code><?php echo htmlspecialchars($employe['matricule'] ?? '-'); ?></code></td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td><strong><?php echo htmlspecialchars($employe['nom']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Prénom</th>
                            <td><?php echo htmlspecialchars($employe['prenom'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($employe['mail'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td><?php echo htmlspecialchars($employe['tel1'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Service</th>
                            <td><?php echo htmlspecialchars($employe['service_nom'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td>
                                <?php if ($employe['actif']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($employe['notes'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($employe['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($employe['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4 no-print">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-link"></i> Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/pages/employes/edit.php?id=<?php echo $id; ?>"
                           class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-pencil"></i> Modifier cet employé
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/employes/index.php"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-list"></i> Voir tous les employés
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
