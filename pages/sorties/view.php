<?php
$page_title = 'Détails de la sortie';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de la sortie avec les informations liées (SIMPLIFIÉE)
$sql = "SELECT s.*,
               serv.nom as service_nom,
               emp.nom as employe_nom, emp.prenom as employe_prenom,
               u.nom as user_nom
        FROM sorties s
        LEFT JOIN services serv ON s.service_id = serv.id
        LEFT JOIN employes emp ON s.employe_id = emp.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = :id";

$db->prepare($sql);
$db->bind(':id', $id);
$sortie = $db->fetch();

if (!$sortie) {
    $_SESSION['error'] = 'Sortie introuvable.';
    header('Location: ' . BASE_URL . '/pages/sorties/index.php');
    exit;
}

// Récupération des lignes de sortie
$db->prepare("SELECT * FROM ligne_sorties WHERE sortie_id = :id ORDER BY id");
$db->bind(':id', $id);
$lignes = $db->fetchAll();

// Calcul du total
$total_articles = count($lignes);
$total_quantite = 0;
foreach ($lignes as $ligne) {
    $total_quantite += $ligne['qte_sortie'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-arrow-up"></i> Détails de la sortie #<?php echo $sortie['id']; ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/sorties/index.php">Sorties</a></li>
                            <li class="breadcrumb-item active">Détails #<?php echo $sortie['id']; ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <a href="<?php echo BASE_URL; ?>/pages/sorties/pdf.php?id=<?php echo $id; ?>"
                       class="btn btn-danger" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/sorties/edit.php?id=<?php echo $id; ?>"
                       class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/pages/sorties/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-md-8">
            <!-- Informations demandeur -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-person"></i> Informations du demandeur
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th width="200">Service</th>
                            <td><?php echo htmlspecialchars($sortie['service_nom']); ?></td>
                        </tr>
                        <tr>
                            <th>Employé</th>
                            <td><?php echo htmlspecialchars($sortie['employe_nom'] . ' ' . $sortie['employe_prenom']); ?></td>
                        </tr>
                        <tr>
                            <th>Date de sortie</th>
                            <td><strong><?php echo date('d/m/Y', strtotime($sortie['date'])); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Liste des articles -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-box-seam"></i> Articles sortis (<?php echo $total_articles; ?>)
                </div>
                <div class="card-body">
                    <?php if (count($lignes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Code article</th>
                                        <th width="55%">Désignation</th>
                                        <th width="30%" class="text-end">Quantité sortie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($ligne['code_article']); ?></code></td>
                                            <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                            <td class="text-end">
                                                <strong><?php echo number_format($ligne['qte_sortie'], 0, '', ' '); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucun article dans cette sortie.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($sortie['notes'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-sticky"></i> Notes
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($sortie['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Panneau latéral -->
        <div class="col-md-4">
            <!-- Statistiques -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Statistiques
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h4 class="text-primary mb-0"><?php echo $total_articles; ?></h4>
                        <small class="text-muted">Article(s) différent(s)</small>
                    </div>
                    <div class="mb-0">
                        <h4 class="text-success mb-0"><?php echo number_format($total_quantite, 0, '', ' '); ?></h4>
                        <small class="text-muted">Quantité totale sortie</small>
                    </div>
                </div>
            </div>

            <!-- Fichier joint -->
            <?php if (!empty($sortie['fichier'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-paperclip"></i> Fichier joint
                </div>
                <div class="card-body">
                    <a href="<?php echo UPLOAD_SORTIES_URL . '/' . $sortie['fichier']; ?>"
                       class="btn btn-sm btn-outline-primary w-100"
                       target="_blank">
                        <i class="bi bi-download"></i> Télécharger le fichier
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informations système -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations système
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th width="100">ID</th>
                            <td><code>#<?php echo $sortie['id']; ?></code></td>
                        </tr>
                        <tr>
                            <th>Créé par</th>
                            <td><?php echo htmlspecialchars($sortie['user_nom'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Créé le</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($sortie['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Modifié le</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($sortie['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>