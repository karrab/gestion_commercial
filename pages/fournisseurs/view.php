<?php
$page_title = 'Détails du fournisseur';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('fournisseurs', 'view');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du fournisseur
$db->prepare("SELECT * FROM fournisseurs WHERE id = :id");
$db->bind(':id', $id);
$fournisseur = $db->fetch();

if (!$fournisseur) {
    $_SESSION['error'] = 'Fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/fournisseurs/index.php');
    exit;
}

// Statistiques avec requêtes optimisées
$stats = [];



// Nombre d'entrées
$db->prepare("SELECT COUNT(*) as count FROM entrees WHERE fournisseur_id = :id");
$db->bind(':id', $id);
$stats['entrees'] = $db->fetch()['count'];

// Nombre de retours fournisseur
$db->prepare("SELECT COUNT(*) as count FROM retour_fournisseur WHERE fournisseur_id = :id");
$db->bind(':id', $id);
$stats['retours'] = $db->fetch()['count'];


// Dernières entrées (5 dernières)
$db->prepare("SELECT e.*, u.nom as user_nom, u.prenom as user_prenom
              FROM entrees e
              LEFT JOIN users u ON e.user_id = u.id
              WHERE e.fournisseur_id = :id
              ORDER BY e.date DESC, e.id DESC
              LIMIT 5");
$db->bind(':id', $id);
$dernieres_entrees = $db->fetchAll();

// Derniers retours fournisseur (5 derniers)
$db->prepare("SELECT rf.*, u.nom as user_nom, u.prenom as user_prenom
              FROM retour_fournisseur rf
              LEFT JOIN users u ON rf.user_id = u.id
              WHERE rf.fournisseur_id = :id
              ORDER BY rf.date DESC, rf.id DESC
              LIMIT 5");
$db->bind(':id', $id);
$derniers_retours = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<!-- Styles pour l'impression PDF -->
<style>
@media print {
    .no-print, .btn, footer, nav.navbar, .breadcrumb, .action-buttons {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        break-inside: avoid;
    }
    body {
        background: white;
        font-size: 12pt;
    }
    .container-fluid {
        width: 100%;
        margin: 0;
        padding: 20px;
    }
    .badge {
        border: 1px solid #000;
        color: #000 !important;
        background: transparent !important;
    }
    a {
        text-decoration: none;
        color: #000;
    }
    .table {
        border-collapse: collapse;
        width: 100%;
    }
    .table th, .table td {
        border: 1px solid #ddd;
        padding: 8px;
    }
}
</style>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2>
                        <i class="bi bi-truck"></i> 
                        Détails du fournisseur
                        <?php if (!$fournisseur['actif']): ?>
                            <span class="badge bg-secondary ms-2">Inactif</span>
                        <?php endif; ?>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php">Fournisseurs</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($fournisseur['nom_complet']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group no-print">
                    <!-- Bouton PDF -->
                    <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/pdf.php?id=<?php echo $id; ?>" 
                       class="btn btn-danger" 
                       target="_blank"
                       title="Générer PDF">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    
                    <!-- Bouton Imprimer -->
                    <button onclick="window.print()" class="btn btn-secondary" title="Imprimer">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    
                    <!-- Bouton Modifier -->
                    <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/edit.php?id=<?php echo $id; ?>" 
                       class="btn btn-warning" 
                       title="Modifier">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    
                    <!-- Bouton Retour -->
                    <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php" 
                       class="btn btn-secondary" 
                       title="Retour à la liste">
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
                    <span class="float-end">
                        <small class="text-muted">ID: #<?php echo $fournisseur['id']; ?></small>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="150">Raison sociale</th>
                                    <td><strong><?php echo htmlspecialchars($fournisseur['nom_complet']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Matricule Fiscal</th>
                                    <td>
                                        <?php if (!empty($fournisseur['matricule_fiscal'])): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-upc-scan"></i>
                                                <?php echo htmlspecialchars($fournisseur['matricule_fiscal']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Non renseigné</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Adresse</th>
                                    <td>
                                        <?php 
                                        $adresse_complete = [];
                                        if (!empty($fournisseur['adresse'])) $adresse_complete[] = nl2br(htmlspecialchars($fournisseur['adresse']));
                                        if (!empty($fournisseur['code_postal'])) $adresse_complete[] = htmlspecialchars($fournisseur['code_postal']);
                                        if (!empty($fournisseur['ville'])) $adresse_complete[] = htmlspecialchars($fournisseur['ville']);
                                        if (!empty($fournisseur['pays'])) $adresse_complete[] = htmlspecialchars($fournisseur['pays']);
                                        
                                        echo !empty($adresse_complete) ? implode('<br>', $adresse_complete) : '<span class="text-muted">Non renseignée</span>';
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="150">Téléphone 1</th>
                                    <td>
                                        <?php if (!empty($fournisseur['tel1'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($fournisseur['tel1']); ?>" class="text-decoration-none">
                                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($fournisseur['tel1']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Téléphone 2</th>
                                    <td>
                                        <?php if (!empty($fournisseur['tel2'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($fournisseur['tel2']); ?>" class="text-decoration-none">
                                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($fournisseur['tel2']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Notes</th>
                                    <td><?php echo !empty($fournisseur['notes']) ? nl2br(htmlspecialchars($fournisseur['notes'])) : '<span class="text-muted">Aucune note</span>'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            

            <!-- Dernières entrées -->
            <?php if (count($dernieres_entrees) > 0): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-box-arrow-in-right"></i> Dernières entrées (5 dernières)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Utilisateur</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dernieres_entrees as $entree): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($entree['date'])); ?></td>
                                        <td>Entrée #<?php echo $entree['id']; ?></td>
                                        <td><?php echo htmlspecialchars($entree['user_nom'] . ' ' . $entree['user_prenom']); ?></td>
                                        <td class="no-print">
                                            <a href="<?php echo BASE_URL; ?>/pages/entrees/view.php?id=<?php echo $entree['id']; ?>"
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Derniers retours fournisseur -->
            <?php if (count($derniers_retours) > 0): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-arrow-return-left"></i> Derniers retours fournisseur (5 derniers)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Utilisateur</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers_retours as $retour): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($retour['date'])); ?></td>
                                        <td>Retour #<?php echo $retour['id']; ?></td>
                                        <td><?php echo htmlspecialchars($retour['user_nom'] . ' ' . $retour['user_prenom']); ?></td>
                                        <td class="no-print">
                                            <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/view.php?id=<?php echo $retour['id']; ?>"
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne de droite avec statistiques et informations système -->
        <div class="col-md-4">
            <!-- Statistiques -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-bar-chart"></i> Statistiques
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-info mb-0"><?php echo $stats['entrees']; ?></h3>
                                <small class="text-muted">Entrée(s)</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-warning mb-0"><?php echo $stats['retours']; ?></h3>
                                <small class="text-muted">Retour(s)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations système -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Informations système
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <small class="text-muted d-block">Créé le</small>
                            <strong><?php echo date('d/m/Y à H:i', strtotime($fournisseur['created_at'])); ?></strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted d-block">Dernière modification</small>
                            <strong><?php echo date('d/m/Y à H:i', strtotime($fournisseur['updated_at'])); ?></strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted d-block">Statut</small>
                            <?php if ($fournisseur['actif']): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactif</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card no-print">
                <div class="card-header">
                    <i class="bi bi-link"></i> Actions rapides
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/pages/employes/create.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Ajouter un employé
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/create.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-outline-success">
                            <i class="bi bi-box-arrow-in-right"></i> Nouvelle entrée
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/create.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-outline-warning">
                            <i class="bi bi-arrow-return-left"></i> Nouveau retour
                        </a>
                        <hr>
                        <a href="<?php echo BASE_URL; ?>/pages/employes/index.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-list"></i> Voir tous les employés
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-list"></i> Voir toutes les entrées
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-list"></i> Voir tous les retours
                        </a>
                    </div>
                </div>
            </div>

            <!-- Badge d'impression PDF -->
            <div class="card mt-3 text-center no-print">
                <div class="card-body">
                    <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/pdf.php?id=<?php echo $id; ?>" 
                       class="btn btn-danger btn-lg" 
                       target="_blank">
                        <i class="bi bi-file-pdf fs-1 d-block mb-2"></i>
                        Générer la fiche fournisseur (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour DataTables (optionnel) -->
<script>
$(document).ready(function() {
    // Initialisation de DataTables pour le tableau des employés si nécessaire
    if ($('#employesTable').length) {
        $('#employesTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "url": "<?php echo BASE_URL; ?>/assets/js/fr-FR.json"
            }
        });
    }
});

// Fonction pour imprimer directement
function imprimerFiche() {
    window.print();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>