<?php
$page_title = 'Recherche globale';
require_once __DIR__ . '/../includes/header.php';

$auth->requireLogin();
$db = Database::getInstance();
$q = trim($_GET['q'] ?? '');

$results = [
    'articles'    => [],
    'employes'    => [],
    'fournisseurs'=> [],
    'services'    => [],
    'entrees'     => [],
    'sorties'     => [],
    'inventaires' => [],
];

if (!empty($q) && strlen($q) >= 2) {
    $s = '%' . $q . '%';

    // Articles
    $db->prepare(
        "SELECT id, code_article, designation, qte_disponible
         FROM articles
         WHERE code_article LIKE :s1 OR designation LIKE :s2
         ORDER BY designation LIMIT 20"
    );
    $db->bind(':s1', $s);
    $db->bind(':s2', $s);
    $results['articles'] = $db->fetchAll();

    // Employés
    $db->prepare(
        "SELECT e.id, e.matricule, e.nom, e.prenom, s.nom AS service
         FROM employes e
         LEFT JOIN services s ON e.service_id = s.id
         WHERE e.matricule LIKE :s1 OR e.nom LIKE :s2 OR e.prenom LIKE :s3 OR e.mail LIKE :s4
         ORDER BY e.nom LIMIT 20"
    );
    $db->bind(':s1', $s); $db->bind(':s2', $s);
    $db->bind(':s3', $s); $db->bind(':s4', $s);
    $results['employes'] = $db->fetchAll();

    // Fournisseurs (tous, actifs ou non)
    $db->prepare(
        "SELECT id, nom_complet, ville, tel1, tel2, actif
         FROM fournisseurs
         WHERE nom_complet LIKE :s1 OR ville LIKE :s2
            OR tel1 LIKE :s3 OR tel2 LIKE :s4
         ORDER BY nom_complet LIMIT 20"
    );
    $db->bind(':s1', $s); $db->bind(':s2', $s);
    $db->bind(':s3', $s); $db->bind(':s4', $s);
    $results['fournisseurs'] = $db->fetchAll();

    // Services
    $db->prepare(
        "SELECT id, nom, notes
         FROM services
         WHERE nom LIKE :s1 OR notes LIKE :s2
         ORDER BY nom LIMIT 20"
    );
    $db->bind(':s1', $s);
    $db->bind(':s2', $s);
    $results['services'] = $db->fetchAll();

    // Entrées (par fournisseur ou notes)
    $db->prepare(
        "SELECT e.id, e.date, e.notes, f.nom_complet AS fournisseur
         FROM entrees e
         LEFT JOIN fournisseurs f ON e.fournisseur_id = f.id
         WHERE f.nom_complet LIKE :s1 OR e.notes LIKE :s2
         ORDER BY e.date DESC LIMIT 20"
    );
    $db->bind(':s1', $s);
    $db->bind(':s2', $s);
    $results['entrees'] = $db->fetchAll();

    // Sorties (par service ou employé)
    $db->prepare(
        "SELECT s.id, s.date, s.notes, ser.nom AS service,
                CONCAT(emp.nom, ' ', emp.prenom) AS employe
         FROM sorties s
         LEFT JOIN services ser ON s.service_id = ser.id
         LEFT JOIN employes emp ON s.employe_id = emp.id
         WHERE ser.nom LIKE :s1 OR emp.nom LIKE :s2
            OR emp.prenom LIKE :s3 OR s.notes LIKE :s4
         ORDER BY s.date DESC LIMIT 20"
    );
    $db->bind(':s1', $s); $db->bind(':s2', $s);
    $db->bind(':s3', $s); $db->bind(':s4', $s);
    $results['sorties'] = $db->fetchAll();

    // Inventaires
    $db->prepare(
        "SELECT i.id, i.reference, i.date_debut, i.etat, ei.nom AS equipe
         FROM inventaires i
         LEFT JOIN equipes_inventaire ei ON i.equipe_id = ei.id
         WHERE i.reference LIKE :s1 OR ei.nom LIKE :s2
         ORDER BY i.date_debut DESC LIMIT 20"
    );
    $db->bind(':s1', $s);
    $db->bind(':s2', $s);
    $results['inventaires'] = $db->fetchAll();
}

$total = array_sum(array_map('count', $results));
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-search"></i> Recherche globale</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Recherche</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Barre de recherche -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="q"
                                   placeholder="Rechercher dans toute la base (articles, employés, fournisseurs, entrées, sorties…)"
                                   value="<?php echo htmlspecialchars($q); ?>" autofocus>
                            <button type="submit" class="btn btn-primary">Rechercher</button>
                            <?php if (!empty($q)): ?>
                                <a href="<?php echo BASE_URL; ?>/pages/search.php" class="btn btn-secondary">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="form-text mt-1">Minimum 2 caractères</div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($q)): ?>

        <!-- Résumé -->
        <div class="row mb-3">
            <div class="col-12">
                <?php if ($total > 0): ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle"></i>
                        <strong><?php echo $total; ?></strong> résultat(s) pour
                        "<strong><?php echo htmlspecialchars($q); ?></strong>"
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-circle"></i>
                        Aucun résultat pour "<strong><?php echo htmlspecialchars($q); ?></strong>"
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">

            <!-- ── Articles ──────────────────────────────────── -->
            <?php if (count($results['articles']) > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-box-seam"></i> Articles
                        <span class="badge bg-light text-dark ms-2"><?php echo count($results['articles']); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Code</th><th>Désignation</th><th class="text-end">Dispo</th></tr></thead>
                            <tbody>
                            <?php foreach ($results['articles'] as $r): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($r['code_article']); ?></code></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/articles/view.php?id=<?php echo $r['id']; ?>">
                                            <?php echo htmlspecialchars($r['designation']); ?>
                                        </a>
                                    </td>
                                    <td class="text-end"><?php echo number_format($r['qte_disponible'], 0, ',', ' '); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Fournisseurs ──────────────────────────────── -->
            <?php if (count($results['fournisseurs']) > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-truck"></i> Fournisseurs
                        <span class="badge bg-light text-dark ms-2"><?php echo count($results['fournisseurs']); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Nom</th><th>Ville</th><th>Tél</th><th class="text-center">Statut</th></tr></thead>
                            <tbody>
                            <?php foreach ($results['fournisseurs'] as $r): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/view.php?id=<?php echo $r['id']; ?>">
                                            <strong><?php echo htmlspecialchars($r['nom_complet']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['ville'] ?? ''); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($r['tel1'] ?? ''); ?>
                                        <?php if (!empty($r['tel2'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($r['tel2']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $r['actif'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $r['actif'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Employés ──────────────────────────────────── -->
            <?php if (count($results['employes']) > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-people"></i> Employés
                        <span class="badge bg-light text-dark ms-2"><?php echo count($results['employes']); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Matricule</th><th>Nom & Prénom</th><th>Service</th></tr></thead>
                            <tbody>
                            <?php foreach ($results['employes'] as $r): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($r['matricule']); ?></code></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $r['id']; ?>">
                                            <?php echo htmlspecialchars($r['nom'] . ' ' . $r['prenom']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['service'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Services ─────────────────────────────────── -->
            <?php if (count($results['services']) > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-secondary text-white">
                        <i class="bi bi-building"></i> Services
                        <span class="badge bg-light text-dark ms-2"><?php echo count($results['services']); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Nom</th><th>Notes</th></tr></thead>
                            <tbody>
                            <?php foreach ($results['services'] as $r): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/services/index.php">
                                            <strong><?php echo htmlspecialchars($r['nom']); ?></strong>
                                        </a>
                                    </td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($r['notes'] ?? ''); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Entrées ───────────────────────────────────── -->
            <?php if (count($results['entrees']) > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-box-arrow-in-down"></i> Entrées
                        <span class="badge bg-light text-dark ms-2"><?php echo count($results['entrees']); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Date</th><th>Fournisseur</th><th>Notes</th></tr></thead>
                            <tbody>
                            <?php foreach ($results['entrees'] as $r): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/entrees/view.php?id=<?php echo $r['id']; ?>">
                                            <?php echo !empty($r['date']) ? date('d/m/Y', strtotime($r['date'])) : '#'.$r['id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['fournisseur'] ?? ''); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($r['notes'] ?? '', 0, 60, '…')); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Sorties ───────────────────────────────────── -->
            <?php if (count($results['sorties']) > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-box-arrow-up"></i> Sorties
                        <span class="badge bg-dark ms-2"><?php echo count($results['sorties']); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Date</th><th>Service</th><th>Employé</th></tr></thead>
                            <tbody>
                            <?php foreach ($results['sorties'] as $r): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/sorties/view.php?id=<?php echo $r['id']; ?>">
                                            <?php echo !empty($r['date']) ? date('d/m/Y', strtotime($r['date'])) : '#'.$r['id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['service'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['employe'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Inventaires ───────────────────────────────── -->
            <?php if (count($results['inventaires']) > 0): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-clipboard-check"></i> Inventaires
                        <span class="badge bg-light text-dark ms-2"><?php echo count($results['inventaires']); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Référence</th><th>Date</th><th>État</th></tr></thead>
                            <tbody>
                            <?php foreach ($results['inventaires'] as $r): ?>
                                <?php
                                $etat_class = match($r['etat']) {
                                    'valide'       => 'bg-success',
                                    'en_cours'     => 'bg-warning text-dark',
                                    'reinitialise' => 'bg-info text-dark',
                                    default        => 'bg-secondary',
                                };
                                $etat_label = match($r['etat']) {
                                    'valide'       => 'Validé',
                                    'en_cours'     => 'En cours',
                                    'reinitialise' => 'Réinitialisé',
                                    default        => ucfirst($r['etat']),
                                };
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $r['id']; ?>">
                                            <code><?php echo htmlspecialchars($r['reference']); ?></code>
                                        </a>
                                    </td>
                                    <td><?php echo !empty($r['date_debut']) ? date('d/m/Y', strtotime($r['date_debut'])) : ''; ?></td>
                                    <td><span class="badge <?php echo $etat_class; ?>"><?php echo $etat_label; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.row -->
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>