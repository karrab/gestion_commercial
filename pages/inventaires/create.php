<?php
/**
 * Création d'un nouvel inventaire avec sélection manuelle des articles
 */

$page_title = 'Créer un inventaire';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'create');
$db = Database::getInstance();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $equipe_id = !empty($_POST['equipe_id']) ? intval($_POST['equipe_id']) : null;
    $action = $_POST['action'] ?? 'none'; // 'none', 'all', 'active'
    
    try {
        $conn = $db->getConnection();
        $conn->beginTransaction();

        // ── 1. Calculer le code_prefix et récupérer les articles ──────────────
        $code_prefix_inv = null;
        $articles = [];

        if ($action === 'all') {
            $code_prefix_inv = 'Tous';
            $stmt_a = $conn->query(
                "SELECT id, code_article, designation, qte_disponible FROM articles ORDER BY code_article"
            );
            $articles = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($action === 'active') {
            $code_prefix_inv = 'Actifs';
            $stmt_a = $conn->query(
                "SELECT id, code_article, designation, qte_disponible FROM articles WHERE actif = 1 ORDER BY code_article"
            );
            $articles = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($action === 'selection') {
            $selected_prefixes = array_values(array_filter(
                $_POST['selected_prefixes'] ?? [],
                function($p) { return preg_match('/^\d{4}$/', $p); }
            ));
            if (!empty($selected_prefixes)) {
                $code_prefix_inv = implode(',', $selected_prefixes);
                $like_conds = implode(' OR ', array_fill(0, count($selected_prefixes), 'code_article LIKE ?'));
                $stmt_a = $conn->prepare(
                    "SELECT id, code_article, designation, qte_disponible
                     FROM articles WHERE ($like_conds) AND actif = 1 ORDER BY code_article"
                );
                foreach ($selected_prefixes as $i => $p) {
                    $stmt_a->bindValue($i + 1, $p . '%');
                }
                $stmt_a->execute();
                $articles = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // ── 2. Générer la référence automatique INV-YEAR-NUMBER ──────────────
        $year = date('Y', strtotime($date));
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inventaires WHERE YEAR(date_debut) = :year");
        $stmt->execute([':year' => $year]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $numero = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $reference = "INV-{$year}-{$numero}";

        // ── 3. Insérer l'inventaire (avec code_prefix) ───────────────────────
        $stmt = $conn->prepare(
            "INSERT INTO inventaires (reference, date_debut, equipe_id, code_prefix, etat, user_id, created_at, reinitialise)
             VALUES (:reference, :date_debut, :equipe_id, :code_prefix, 'en_cours', :user_id, NOW(), 0)"
        );
        $stmt->execute([
            ':reference'   => $reference,
            ':date_debut'  => $date,
            ':equipe_id'   => $equipe_id,
            ':code_prefix' => $code_prefix_inv,
            ':user_id'     => $auth->getUserId()
        ]);
        $inventaire_id = $conn->lastInsertId();

        // ── 4. Insérer les lignes (avec reference_inventaire et code_prefix) ─
        if (!empty($articles)) {
            $sql_ligne = "INSERT INTO ligne_inventaires
                         (inventaire_id, reference_inventaire, article_id, code_article, code_prefix,
                          designation, qte_theorique, qte_physique, ecart)
                         VALUES (:inv_id, :ref_inv, :art_id, :code, :cpfx, :design, :qte_theo, 0, :ecart)";
            $stmt_ligne = $conn->prepare($sql_ligne);
            foreach ($articles as $article) {
                $stmt_ligne->execute([
                    ':inv_id'  => $inventaire_id,
                    ':ref_inv' => $reference,
                    ':art_id'  => $article['id'],
                    ':code'    => $article['code_article'],
                    ':cpfx'    => substr($article['code_article'], 0, 4),
                    ':design'  => $article['designation'],
                    ':qte_theo'=> $article['qte_disponible'],
                    ':ecart'   => -$article['qte_disponible']
                ]);
            }
        }

        $conn->commit();
        
        $nb_articles = count($articles ?? []);
        $_SESSION['success'] = "Inventaire {$reference} créé avec succès ! " . $nb_articles . " article(s) ajouté(s).";
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
        exit;
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        $_SESSION['error'] = 'Erreur lors de la création : ' . $e->getMessage();
        error_log('Erreur création inventaire: ' . $e->getMessage());
    }
}

// Compter les articles actifs par préfixe
$prefixes = ['2230','2231','2232','2233','2234','2235','2240','2280','2281'];
$counts_par_prefix = [];
$total_selection = 0;
foreach ($prefixes as $p) {
    $stmt_c = $db->getConnection()->prepare(
        "SELECT COUNT(*) FROM articles WHERE code_article LIKE ? AND actif = 1"
    );
    $stmt_c->execute([$p . '%']);
    $cnt = (int)$stmt_c->fetchColumn();
    $counts_par_prefix[$p] = $cnt;
    $total_selection += $cnt;
}

// Récupérer les équipes
$stmt_equipes = $db->getConnection()->query("SELECT id, nom FROM equipes_inventaire ORDER BY nom");
$equipes = $stmt_equipes->fetchAll(PDO::FETCH_ASSOC);

// Compter les articles
$stmt_all = $db->getConnection()->query("SELECT COUNT(*) as count FROM articles");
$nb_all_articles = $stmt_all->fetch(PDO::FETCH_ASSOC)['count'];

$stmt_active = $db->getConnection()->query("SELECT COUNT(*) as count FROM articles WHERE actif = 1");
$nb_active_articles = $stmt_active->fetch(PDO::FETCH_ASSOC)['count'];
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-plus-circle"></i> Créer un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Créer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-clipboard-plus"></i> Nouvel inventaire
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date de l'inventaire <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="equipe_id" class="form-label">Équipe d'inventaire</label>
                            <select class="form-select" id="equipe_id" name="equipe_id">
                                <option value="">Aucune équipe</option>
                                <?php foreach ($equipes as $equipe): ?>
                                    <option value="<?php echo $equipe['id']; ?>">
                                        <?php echo htmlspecialchars($equipe['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Optionnel - Sélectionnez l'équipe qui effectuera l'inventaire</div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="bi bi-box-seam"></i> Sélection des articles
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <h5><i class="bi bi-file-earmark"></i> Inventaire vide</h5>
                                                <p class="text-muted">Créez un inventaire sans articles</p>
                                                <button type="button" class="btn btn-outline-secondary w-100 select-action" data-action="none">
                                                    <i class="bi bi-check2-square"></i> Sélectionner
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <h5><i class="bi bi-box"></i> Tous les articles</h5>
                                                <p class="text-muted">Ajoute tous les articles (<?php echo $nb_all_articles; ?>)</p>
                                                <button type="button" class="btn btn-outline-primary w-100 select-action" data-action="all">
                                                    <i class="bi bi-check2-square"></i> Sélectionner
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <h5><i class="bi bi-check-circle"></i> Articles actifs</h5>
                                                <p class="text-muted">Ajoute seulement les articles actifs (<?php echo $nb_active_articles; ?>)</p>
                                                <button type="button" class="btn btn-outline-success w-100 select-action" data-action="active">
                                                    <i class="bi bi-check2-square"></i> Sélectionner
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ligne 2 : sélection par préfixes de code -->
                                <div class="col-12 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="mb-1"><i class="bi bi-funnel"></i> Sélection par code article</h5>
                                                    <p class="text-muted mb-0">
                                                        Choisissez les codes de début — tous les articles actifs correspondants seront ajoutés.
                                                        &mdash; <strong><?php echo $total_selection; ?></strong> article(s) disponible(s)
                                                    </p>
                                                </div>
                                                <button type="button" class="btn btn-outline-warning select-action ms-3" data-action="selection">
                                                    <i class="bi bi-check2-square"></i> Sélectionner
                                                </button>
                                            </div>

                                            <div id="bloc_selection" style="display:none;" class="mt-3">
                                                <label for="selected_prefixes" class="form-label fw-semibold">
                                                    Choisir les codes de début <span class="text-danger">*</span>
                                                </label>
                                                <select name="selected_prefixes[]" id="selected_prefixes"
                                                        class="form-select" multiple size="9">
                                                    <?php foreach ($prefixes as $p): ?>
                                                        <option value="<?php echo $p; ?>">
                                                            <?php echo $p; ?>
                                                            &mdash; <?php echo $counts_par_prefix[$p]; ?> article(s)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">Maintenez <kbd>Ctrl</kbd> pour sélectionner plusieurs codes.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="action" id="selected_action" value="none" required>
                                
                                <div class="alert alert-info mt-2" id="selection-info">
                                    <i class="bi bi-info-circle"></i>
                                    <span id="selection-text">Inventaire vide - Vous ajouterez des articles plus tard</span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Important :</strong>
                            <ul class="mb-0 mt-2">
                                <li>L'inventaire sera créé en état "En cours"</li>
                                <li>Vous pourrez ajouter/supprimer des articles sur la page de détail</li>
                                <li>Les quantités physiques seront à 0 par défaut</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Créer l'inventaire
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {

    // Select2 pour la sélection des codes préfixes
    $('#selected_prefixes').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Sélectionner des codes...',
        allowClear: true,
        language: {
            noResults: function() { return 'Aucun code trouvé'; }
        }
    });

    // Gestion de la sélection des articles
    $('.select-action').on('click', function() {
        var action = $(this).data('action');
        var text = '';

        // Réinitialiser tous les boutons
        $('.select-action')
            .removeClass('btn-primary btn-success btn-secondary btn-warning')
            .addClass(function() {
                var a = $(this).data('action');
                if (a === 'none')      return 'btn-outline-secondary';
                if (a === 'all')       return 'btn-outline-primary';
                if (a === 'active')    return 'btn-outline-success';
                if (a === 'selection') return 'btn-outline-warning';
            });

        $(this).removeClass('btn-outline-secondary btn-outline-primary btn-outline-success btn-outline-warning');

        switch(action) {
            case 'none':
                $(this).addClass('btn-secondary');
                text = 'Inventaire vide — vous ajouterez des articles plus tard';
                $('#bloc_selection').hide();
                break;
            case 'all':
                $(this).addClass('btn-primary');
                text = 'Tous les articles seront ajoutés à l\'inventaire';
                $('#bloc_selection').hide();
                break;
            case 'active':
                $(this).addClass('btn-success');
                text = 'Seuls les articles actifs seront ajoutés à l\'inventaire';
                $('#bloc_selection').hide();
                break;
            case 'selection':
                $(this).addClass('btn-warning');
                text = 'Seuls les articles sélectionnés ci-dessous seront ajoutés';
                $('#bloc_selection').show();
                break;
        }

        $('#selected_action').val(action);
        $('#selection-text').text(text);
    });

    // Sélection par défaut : inventaire vide
    $('.select-action[data-action="none"]').trigger('click');
});
</script>