<?php
$page_title = 'Détails du equipe';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('equipes', 'view');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du equipe
$db->prepare("SELECT * FROM equipes_inventaire WHERE id = :id");
$db->bind(':id', $id);
$equipe = $db->fetch();

if (!$equipe) {
    $_SESSION['error'] = 'Équipe introuvable.';
    header('Location: ' . BASE_URL . '/pages/equipes/index.php');
    exit;
}


?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-person-workspace"></i> Détails du equipe</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/equipes/index.php">Équipes</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <a href="<?php echo BASE_URL; ?>/pages/equipes/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/equipes/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations générales
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">ID</th>
                            <td><?php echo $equipe['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td><strong><?php echo htmlspecialchars($equipe['nom']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($equipe['notes'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($equipe['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($equipe['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
