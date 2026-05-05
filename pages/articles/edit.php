<?php
$page_title = 'Modifier article';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('articles', 'update');
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

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_article = trim($_POST['code_article'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $stock_min = floatval($_POST['stock_min'] ?? 0);
    $stock_max = floatval($_POST['stock_max'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($code_article)) $errors[] = 'Le code article est obligatoire.';
    if (empty($designation)) $errors[] = 'La désignation est obligatoire.';

    // Vérifier l'unicité du code (sauf pour l'article actuel)
    $db->prepare("SELECT COUNT(*) as count FROM articles WHERE code_article = :code AND id != :id");
    $db->bind(':code', $code_article);
    $db->bind(':id', $id);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Un article avec ce code existe déjà.';
    }

    if (empty($errors)) {
        try {
            // Construction de la requête UPDATE
            $sql = "UPDATE articles SET
                        code_article = :code,
                        designation = :designation,
                        stock_min = :stock_min,
                        stock_max = :stock_max,
                        notes = :notes,
                        updated_at = NOW()";

            // IMPORTANT: Seul l'admin peut modifier stock_initial
            if ($auth->isAdmin() && isset($_POST['stock_initial'])) {
                $stock_initial = floatval($_POST['stock_initial']);

                // Recalculer qte_disponible si stock_initial change
                $old_stock_initial = floatval($article['stock_initial']);
                $diff = $stock_initial - $old_stock_initial;

                $sql .= ", stock_initial = :stock_initial,
                          qte_disponible = qte_disponible + :diff";
            }

            $sql .= " WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':code', $code_article);
            $db->bind(':designation', $designation);
            $db->bind(':stock_min', $stock_min);
            $db->bind(':stock_max', $stock_max);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);

            if ($auth->isAdmin() && isset($_POST['stock_initial'])) {
                $db->bind(':stock_initial', $stock_initial);
                $db->bind(':diff', $diff);
            }

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'articles', 'update', 'articles', $id, "Modification: $code_article");
                $_SESSION['success'] = 'Article modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/articles/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
} else {
    $code_article = $article['code_article'];
    $designation = $article['designation'];
    $stock_initial = $article['stock_initial'];
    $stock_min = $article['stock_min'];
    $stock_max = $article['stock_max'];
    $notes = $article['notes'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-seam"></i> Modifier l'article</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/articles/index.php">Articles</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations générales</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code_article" class="form-label required">Code article</label>
                                <input type="text" class="form-control" id="code_article" name="code_article" required
                                       value="<?php echo htmlspecialchars($code_article); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="designation" class="form-label required">Désignation</label>
                                <input type="text" class="form-control" id="designation" name="designation" required
                                       value="<?php echo htmlspecialchars($designation); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes / Description</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-graph-up"></i> Gestion du stock</div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Stock calculé automatiquement :</strong>
                            <p class="mb-0 mt-2">
                                <code>Disponible = Stock initial + Entrées - Sorties</code><br>
                                <code><?php echo number_format($article['qte_disponible'], 0, ',', ' '); ?> =
                                      <?php echo number_format($article['stock_initial'], 0, ',', ' '); ?> +
                                      <?php echo number_format($article['qte_entree'], 0, ',', ' '); ?> -
                                      <?php echo number_format($article['qte_sortie'], 0, ',', ' '); ?></code>
                            </p>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="stock_initial" class="form-label required">Stock initial</label>
                                <input type="number" class="form-control" id="stock_initial" name="stock_initial"
                                       value="<?php echo htmlspecialchars($stock_initial); ?>" min="0" step="0.01"
                                       <?php echo !$auth->isAdmin() ? 'readonly' : ''; ?>>
                                <?php if (!$auth->isAdmin()): ?>
                                    <small class="text-danger">
                                        <i class="bi bi-lock"></i> Seul l'administrateur peut modifier ce champ
                                    </small>
                                <?php else: ?>
                                    <small class="text-warning">
                                        <i class="bi bi-exclamation-triangle"></i> Modifier ce champ ajustera automatiquement le stock disponible
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock_min" class="form-label">Stock minimum</label>
                                <input type="number" class="form-control" id="stock_min" name="stock_min"
                                       value="<?php echo htmlspecialchars($stock_min); ?>" min="0" step="0.01">
                                <small class="text-muted">Seuil d'alerte</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock_max" class="form-label">Stock maximum</label>
                                <input type="number" class="form-control" id="stock_max" name="stock_max"
                                       value="<?php echo htmlspecialchars($stock_max); ?>" min="0" step="0.01">
                                <small class="text-muted">Seuil maximum</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <small class="text-muted">Total entrées</small>
                                        <h5 class="text-success mb-0"><?php echo number_format($article['qte_entree'], 0, ',', ' '); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <small class="text-muted">Total sorties</small>
                                        <h5 class="text-danger mb-0"><?php echo number_format($article['qte_sortie'], 0, ',', ' '); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <small>Stock disponible</small>
                                        <h5 class="mb-0"><?php echo number_format($article['qte_disponible'], 0, ',', ' '); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/articles/view.php?id=<?php echo $id; ?>" class="btn btn-info">
                                <i class="bi bi-eye"></i> Voir
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-clock-history"></i> Informations</div>
                    <div class="card-body">
                        <p><strong>ID:</strong> <?php echo $article['id']; ?></p>
                        <p><strong>Créé le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($article['created_at'])); ?></p>
                        <p><strong>Modifié le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($article['updated_at'])); ?></p>
                        <p class="mb-0"><strong>Actif:</strong>
                            <?php echo $article['actif'] ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-danger">Non</span>'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
