<?php
$page_title = 'Détails du armoire';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du armoire
$db->prepare("SELECT * FROM armoires WHERE id = :id");
$db->bind(':id', $id);
$armoire = $db->fetch();

if (!$armoire) {
    $_SESSION['error'] = 'Armoire introuvable.';
    header('Location: ' . BASE_URL . '/pages/armoires/index.php');
    exit;
}

// Statistiques
$db->prepare("SELECT COUNT(*) as count FROM employes WHERE armoire_id = :id AND actif = 1");
$db->bind(':id', $id);
$nb_employes = $db->fetch()['count'];

$db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE armoire_id = :id");
$db->bind(':id', $id);
$nb_bureaux = $db->fetch()['count'];

// Liste des employés
$db->prepare("SELECT * FROM employes WHERE armoire_id = :id AND actif = 1 ORDER BY nom, prenom");
$db->bind(':id', $id);
$employes = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-archive"></i> Détails du armoire</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/armoires/index.php">Armoires</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <a href="<?php echo BASE_URL; ?>/pages/armoires/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/armoires/index.php" class="btn btn-secondary">
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
                            <td><?php echo $armoire['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td><strong><?php echo htmlspecialchars($armoire['nom']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($armoire['notes'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($armoire['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($armoire['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Liste des employés -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-people"></i> Employés du armoire (<?php echo $nb_employes; ?>)
                </div>
                <div class="card-body">
                    <?php if (count($employes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Matricule</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th class="no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employes as $employe): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($employe['matricule']); ?></code></td>
                                            <td><?php echo htmlspecialchars($employe['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($employe['prenom']); ?></td>
                                            <td><?php echo htmlspecialchars($employe['mail'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($employe['tel1'] ?? '-'); ?></td>
                                            <td class="no-print">
                                                <a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $employe['id']; ?>"
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucun employé dans ce armoire.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Statistiques
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-primary"><?php echo $nb_employes; ?></h5>
                        <small class="text-muted">Employé(s)</small>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-success"><?php echo $nb_bureaux; ?></h5>
                        <small class="text-muted">Bureau(x)</small>
                    </div>
                </div>
            </div>

            <div class="card no-print">
                <div class="card-header">
                    <i class="bi bi-link"></i> Liens rapides
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/pages/employes/create.php?armoire_id=<?php echo $id; ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Ajouter un employé
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/employes/index.php?armoire_id=<?php echo $id; ?>"
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
