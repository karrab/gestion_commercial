<?php
$page_title = 'Détails de l\'article';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de l'article
$db->prepare("SELECT * FROM articles WHERE id = :id");
$db->bind(':id', $id);
$article = $db->fetch();

if (!$article) {
    $_SESSION['error'] = 'Article introuvable.';
    header('Location: ' . BASE_URL . '/pages/articles/index.php');
    exit;
}

// Récupération des mouvements de l'article
$mouvements = [];

// 1. Entrées
$db->prepare("
    SELECT 
        'entree' as type,
        e.id,
        e.date,
        e.fichier,
        e.notes,
        e.created_at,
        f.nom_complet as fournisseur_nom,
        le.qte_entree as quantite,
        u.nom as user_nom,
        u.prenom as user_prenom
    FROM entrees e
    INNER JOIN ligne_entrees le ON e.id = le.entree_id
    INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
    INNER JOIN users u ON e.user_id = u.id
    WHERE le.article_id = :article_id
    ORDER BY e.date DESC, e.created_at DESC
");
$db->bind(':article_id', $id);
$entrees = $db->fetchAll();
$mouvements = array_merge($mouvements, array_map(function($item) {
    $item['type_label'] = 'Entrée';
    $item['type_class'] = 'success';
    $item['icon'] = 'bi-box-arrow-in-down';
    return $item;
}, $entrees));

// 2. Sorties
$db->prepare("
    SELECT 
        'sortie' as type,
        s.id,
        s.date,
        s.fichier,
        s.notes,
        s.created_at,
        emp.nom as employe_nom,
        emp.prenom as employe_prenom,
        ser.nom as service_nom,
        ls.qte_sortie as quantite,
        u.nom as user_nom,
        u.prenom as user_prenom
    FROM sorties s
    INNER JOIN ligne_sorties ls ON s.id = ls.sortie_id
    INNER JOIN employes emp ON s.employe_id = emp.id
    INNER JOIN services ser ON s.service_id = ser.id
    INNER JOIN users u ON s.user_id = u.id
    WHERE ls.article_id = :article_id
    ORDER BY s.date DESC, s.created_at DESC
");
$db->bind(':article_id', $id);
$sorties = $db->fetchAll();
$mouvements = array_merge($mouvements, array_map(function($item) {
    $item['type_label'] = 'Sortie';
    $item['type_class'] = 'danger';
    $item['icon'] = 'bi-box-arrow-up';
    return $item;
}, $sorties));

// 3. Retours employés
$db->prepare("
    SELECT 
        'retour_employe' as type,
        r.id,
        r.date,
        r.fichier,
        r.notes,
        r.created_at,
        emp.nom as employe_nom,
        emp.prenom as employe_prenom,
        ser.nom as service_nom,
        lr.qte_retour as quantite,
        u.nom as user_nom,
        u.prenom as user_prenom
    FROM retours r
    INNER JOIN ligne_retours lr ON r.id = lr.retour_id
    INNER JOIN employes emp ON r.employe_id = emp.id
    INNER JOIN services ser ON r.service_id = ser.id
    INNER JOIN users u ON r.user_id = u.id
    WHERE lr.article_id = :article_id
    ORDER BY r.date DESC, r.created_at DESC
");
$db->bind(':article_id', $id);
$retours_employes = $db->fetchAll();
$mouvements = array_merge($mouvements, array_map(function($item) {
    $item['type_label'] = 'Retour employé';
    $item['type_class'] = 'warning';
    $item['icon'] = 'bi-arrow-counterclockwise';
    return $item;
}, $retours_employes));

// 4. Retours fournisseurs
$db->prepare("
    SELECT 
        'retour_fournisseur' as type,
        rf.id,
        rf.date,
        rf.fichier,
        rf.notes,
        rf.created_at,
        f.nom_complet as fournisseur_nom,
        lrf.qte as quantite,
        u.nom as user_nom,
        u.prenom as user_prenom
    FROM retour_fournisseur rf
    INNER JOIN ligne_retour_fournisseur lrf ON rf.id = lrf.retour_fournisseur_id
    INNER JOIN fournisseurs f ON rf.fournisseur_id = f.id
    INNER JOIN users u ON rf.user_id = u.id
    WHERE lrf.article_id = :article_id
    ORDER BY rf.date DESC, rf.created_at DESC
");
$db->bind(':article_id', $id);
$retours_fournisseurs = $db->fetchAll();
$mouvements = array_merge($mouvements, array_map(function($item) {
    $item['type_label'] = 'Retour fournisseur';
    $item['type_class'] = 'info';
    $item['icon'] = 'bi-truck';
    return $item;
}, $retours_fournisseurs));

// Tri des mouvements par date (plus récent en premier)
usort($mouvements, function($a, $b) {
    return strtotime($b['date'] . ' ' . $b['created_at']) - strtotime($a['date'] . ' ' . $a['created_at']);
});

// Compteurs pour les onglets
$count_entrees = count($entrees);
$count_sorties = count($sorties);
$count_retours_employes = count($retours_employes);
$count_retours_fournisseurs = count($retours_fournisseurs);
$count_total = $count_entrees + $count_sorties + $count_retours_employes + $count_retours_fournisseurs;
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-box-seam"></i> Détails de l'article</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/articles/index.php">Articles</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <a href="<?php echo BASE_URL; ?>/pages/articles/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations générales
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Code article</th>
                            <td><code><?php echo htmlspecialchars($article['code_article']); ?></code></td>
                        </tr>
                        <tr>
                            <th>Désignation</th>
                            <td><strong><?php echo htmlspecialchars($article['designation']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td>
                                <span class="badge bg-<?php echo $article['actif'] ? 'success' : 'danger'; ?>">
                                    <?php echo $article['actif'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Stock initial</th>
                            <td><?php echo $article['stock_initial']; ?></td>
                        </tr>
                        <tr>
                            <th>Stock minimum</th>
                            <td><?php echo $article['stock_min']; ?></td>
                        </tr>
                        <tr>
                            <th>Stock maximum</th>
                            <td><?php echo $article['stock_max']; ?></td>
                        </tr>
                        <tr>
                            <th>Stock disponible</th>
                            <td>
                                <span class="badge bg-<?php echo $article['qte_disponible'] > $article['stock_min'] ? 'success' : ($article['qte_disponible'] == 0 ? 'danger' : 'warning'); ?>">
                                    <?php echo $article['qte_disponible']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($article['notes'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($article['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($article['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Statistiques
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border rounded p-2">
                                <h5 class="text-success mb-1"><?php echo $article['qte_entree']; ?></h5>
                                <small class="text-muted">Entrées</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-2">
                                <h5 class="text-danger mb-1"><?php echo $article['qte_sortie']; ?></h5>
                                <small class="text-muted">Sorties</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-2">
                                <h5 class="text-warning mb-1"><?php echo $article['qte_retour']; ?></h5>
                                <small class="text-muted">Retours</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-2">
                                <h5 class="text-info mb-1"><?php echo $article['qte_retour_frs']; ?></h5>
                                <small class="text-muted">Retours fournisseurs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets des mouvements -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="mouvementsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                <i class="bi bi-list"></i> Tous les mouvements
                                <span class="badge bg-secondary"><?php echo $count_total; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="entrees-tab" data-bs-toggle="tab" data-bs-target="#entrees" type="button" role="tab">
                                <i class="bi bi-box-arrow-in-down"></i> Entrées
                                <span class="badge bg-success"><?php echo $count_entrees; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sorties-tab" data-bs-toggle="tab" data-bs-target="#sorties" type="button" role="tab">
                                <i class="bi bi-box-arrow-up"></i> Sorties
                                <span class="badge bg-danger"><?php echo $count_sorties; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="retours-tab" data-bs-toggle="tab" data-bs-target="#retours" type="button" role="tab">
                                <i class="bi bi-arrow-counterclockwise"></i> Retours employés
                                <span class="badge bg-warning"><?php echo $count_retours_employes; ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="retours-fournisseurs-tab" data-bs-toggle="tab" data-bs-target="#retours-fournisseurs" type="button" role="tab">
                                <i class="bi bi-truck"></i> Retours fournisseurs
                                <span class="badge bg-info"><?php echo $count_retours_fournisseurs; ?></span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="mouvementsTabContent">
                        <!-- Onglet Tous les mouvements -->
                        <div class="tab-pane fade show active" id="all" role="tabpanel">
                            <!-- Barre de recherche -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchAll" placeholder="Rechercher dans tous les mouvements...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchAll">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($count_total > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th width="140">Date</th>
                                                <th width="100">Type</th>
                                                <th>Détails</th>
                                                <th width="80" class="text-end">Quantité</th>
                                                <th width="140" class="text-end">Utilisateur</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableAllBody">
                                            <?php foreach ($mouvements as $mouvement): ?>
                                                <tr>
                                                    <td>
                                                        <small>
                                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($mouvement['date'])); ?>
                                                            <br>
                                                            <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($mouvement['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $mouvement['type_class']; ?>">
                                                            <i class="bi <?php echo $mouvement['icon']; ?>"></i>
                                                            <?php echo $mouvement['type_label']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($mouvement['type'] == 'entree'): ?>
                                                            <strong>Fournisseur :</strong> <?php echo htmlspecialchars($mouvement['fournisseur_nom']); ?>
                                                        <?php elseif ($mouvement['type'] == 'sortie'): ?>
                                                            <strong>Employé :</strong> <?php echo htmlspecialchars($mouvement['employe_prenom'] . ' ' . $mouvement['employe_nom']); ?>
                                                            <br>
                                                            <strong>Service :</strong> <?php echo htmlspecialchars($mouvement['service_nom']); ?>
                                                        <?php elseif ($mouvement['type'] == 'retour_employe'): ?>
                                                            <strong>Employé :</strong> <?php echo htmlspecialchars($mouvement['employe_prenom'] . ' ' . $mouvement['employe_nom']); ?>
                                                            <br>
                                                            <strong>Service :</strong> <?php echo htmlspecialchars($mouvement['service_nom']); ?>
                                                        <?php elseif ($mouvement['type'] == 'retour_fournisseur'): ?>
                                                            <strong>Fournisseur :</strong> <?php echo htmlspecialchars($mouvement['fournisseur_nom']); ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($mouvement['notes'])): ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($mouvement['notes']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge bg-<?php echo $mouvement['type_class']; ?>">
                                                            <?php echo $mouvement['quantite']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <small><?php echo htmlspecialchars($mouvement['user_prenom'] . ' ' . $mouvement['user_nom']); ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">Aucun mouvement enregistré pour cet article.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Onglet Entrées -->
                        <div class="tab-pane fade" id="entrees" role="tabpanel">
                            <!-- Barre de recherche -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchEntrees" placeholder="Rechercher dans les entrées...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchEntrees">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($count_entrees > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th width="140">Date</th>
                                                <th>Fournisseur</th>
                                                <th width="80" class="text-end">Quantité</th>
                                                <th width="140" class="text-end">Utilisateur</th>
                                                <th width="80" class="no-print">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableEntreesBody">
                                            <?php foreach ($entrees as $entree): ?>
                                                <tr>
                                                    <td>
                                                        <small>
                                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($entree['date'])); ?>
                                                            <br>
                                                            <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($entree['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($entree['fournisseur_nom']); ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-success"><?php echo $entree['quantite']; ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <small><?php echo htmlspecialchars($entree['user_prenom'] . ' ' . $entree['user_nom']); ?></small>
                                                    </td>
                                                    <td class="no-print">
                                                        <a href="<?php echo BASE_URL; ?>/pages/entrees/view.php?id=<?php echo $entree['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="Voir détails">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">Aucune entrée enregistrée pour cet article.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Onglet Sorties -->
                        <div class="tab-pane fade" id="sorties" role="tabpanel">
                            <!-- Barre de recherche -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchSorties" placeholder="Rechercher dans les sorties...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchSorties">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($count_sorties > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th width="140">Date</th>
                                                <th>Employé</th>
                                                <th>Service</th>
                                                <th width="80" class="text-end">Quantité</th>
                                                <th width="140" class="text-end">Utilisateur</th>
                                                <th width="80" class="no-print">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableSortiesBody">
                                            <?php foreach ($sorties as $sortie): ?>
                                                <tr>
                                                    <td>
                                                        <small>
                                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($sortie['date'])); ?>
                                                            <br>
                                                            <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($sortie['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($sortie['employe_prenom'] . ' ' . $sortie['employe_nom']); ?></td>
                                                    <td><?php echo htmlspecialchars($sortie['service_nom']); ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-danger"><?php echo $sortie['quantite']; ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <small><?php echo htmlspecialchars($sortie['user_prenom'] . ' ' . $sortie['user_nom']); ?></small>
                                                    </td>
                                                    <td class="no-print">
                                                        <a href="<?php echo BASE_URL; ?>/pages/sorties/view.php?id=<?php echo $sortie['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="Voir détails">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">Aucune sortie enregistrée pour cet article.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Onglet Retours employés -->
                        <div class="tab-pane fade" id="retours" role="tabpanel">
                            <!-- Barre de recherche -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchRetours" placeholder="Rechercher dans les retours employés...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchRetours">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($count_retours_employes > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th width="140">Date</th>
                                                <th>Employé</th>
                                                <th>Service</th>
                                                <th width="80" class="text-end">Quantité</th>
                                                <th width="140" class="text-end">Utilisateur</th>
                                                <th width="80" class="no-print">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableRetoursBody">
                                            <?php foreach ($retours_employes as $retour): ?>
                                                <tr>
                                                    <td>
                                                        <small>
                                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($retour['date'])); ?>
                                                            <br>
                                                            <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($retour['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($retour['employe_prenom'] . ' ' . $retour['employe_nom']); ?></td>
                                                    <td><?php echo htmlspecialchars($retour['service_nom']); ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-warning"><?php echo $retour['quantite']; ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <small><?php echo htmlspecialchars($retour['user_prenom'] . ' ' . $retour['user_nom']); ?></small>
                                                    </td>
                                                    <td class="no-print">
                                                        <a href="<?php echo BASE_URL; ?>/pages/retours/view.php?id=<?php echo $retour['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="Voir détails">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">Aucun retour employé enregistré pour cet article.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Onglet Retours fournisseurs -->
                        <div class="tab-pane fade" id="retours-fournisseurs" role="tabpanel">
                            <!-- Barre de recherche -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchRetoursFournisseurs" placeholder="Rechercher dans les retours fournisseurs...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchRetoursFournisseurs">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($count_retours_fournisseurs > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th width="140">Date</th>
                                                <th>Fournisseur</th>
                                                <th width="80" class="text-end">Quantité</th>
                                                <th width="140" class="text-end">Utilisateur</th>
                                                <th width="80" class="no-print">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableRetoursFournisseursBody">
                                            <?php foreach ($retours_fournisseurs as $retour): ?>
                                                <tr>
                                                    <td>
                                                        <small>
                                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($retour['date'])); ?>
                                                            <br>
                                                            <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($retour['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($retour['fournisseur_nom']); ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-info"><?php echo $retour['quantite']; ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <small><?php echo htmlspecialchars($retour['user_prenom'] . ' ' . $retour['user_nom']); ?></small>
                                                    </td>
                                                    <td class="no-print">
                                                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/view.php?id=<?php echo $retour['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="Voir détails">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">Aucun retour fournisseur enregistré pour cet article.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Activation automatique de l'onglet sauvegardé dans localStorage
document.addEventListener('DOMContentLoaded', function() {
    // Sauvegarde l'onglet actif dans localStorage
    var tabEl = document.querySelectorAll('#mouvementsTab button[data-bs-toggle="tab"]');
    tabEl.forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
            localStorage.setItem('articleMouvementsActiveTab', event.target.id);
        });
    });

    // Récupère l'onglet actif sauvegardé
    var activeTab = localStorage.getItem('articleMouvementsActiveTab');
    if (activeTab) {
        var tabTrigger = document.querySelector('#' + activeTab);
        if (tabTrigger) {
            var tab = new bootstrap.Tab(tabTrigger);
            tab.show();
        }
    }

    // Fonction de recherche générique
    function setupSearch(inputId, clearId, tableBodyId) {
        const searchInput = document.getElementById(inputId);
        const clearButton = document.getElementById(clearId);
        const tableBody = document.getElementById(tableBodyId);
        
        if (searchInput && tableBody) {
            // Fonction de filtrage
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const rows = tableBody.querySelectorAll('tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            // Écouteur pour la recherche en temps réel
            searchInput.addEventListener('input', filterTable);
            
            // Bouton pour effacer la recherche
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    searchInput.value = '';
                    filterTable();
                    searchInput.focus();
                });
            }
        }
    }
    

    
    // Configuration des recherches pour chaque onglet
    setupSearch('searchAll', 'clearSearchAll', 'tableAllBody');
    setupSearch('searchEntrees', 'clearSearchEntrees', 'tableEntreesBody');
    setupSearch('searchSorties', 'clearSearchSorties', 'tableSortiesBody');
    setupSearch('searchRetours', 'clearSearchRetours', 'tableRetoursBody');
    setupSearch('searchRetoursFournisseurs', 'clearSearchRetoursFournisseurs', 'tableRetoursFournisseursBody');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>