<?php
$page_title = 'Nouvel article';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('articles', 'create');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_article = trim($_POST['code_article'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $stock_initial = floatval($_POST['stock_initial'] ?? 0);
    $stock_min = floatval($_POST['stock_min'] ?? 0);
    $stock_max = floatval($_POST['stock_max'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    $errors = [];

    if (empty($code_article)) $errors[] = 'Le code article est obligatoire.';
    if (empty($designation)) $errors[] = 'La désignation est obligatoire.';

    // Vérifier unicité code
    $db->prepare("SELECT COUNT(*) as count FROM articles WHERE code_article = :code");
    $db->bind(':code', $code_article);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Un article avec ce code existe déjà.';
    }

    if (empty($errors)) {
        try {
            // À la création : qte_disponible = stock_initial
            $sql = "INSERT INTO articles (code_article, designation, stock_initial, qte_disponible, stock_min, stock_max, notes, actif)
                    VALUES (:code, :designation, :stock_initial, :qte_disponible, :stock_min, :stock_max, :notes, :actif)";

            $db->prepare($sql);
            $db->bind(':code', $code_article);
            $db->bind(':designation', $designation);
            $db->bind(':stock_initial', $stock_initial);
            $db->bind(':qte_disponible', $stock_initial); // IMPORTANT: qte_disponible = stock_initial
            $db->bind(':stock_min', $stock_min);
            $db->bind(':stock_max', $stock_max);
            $db->bind(':notes', $notes);
            $db->bind(':actif', $actif);

            if ($db->execute()) {
                $id = $db->lastInsertId();
                $auth->logTrace($auth->getUserId(), 'articles', 'create', 'articles', $id, "Création: $code_article");
                $_SESSION['success'] = 'Article créé avec succès.';
                header('Location: ' . BASE_URL . '/pages/articles/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Nouvel article</h2>
                <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Retour à la liste
                </a>
            </div>
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb bg-light p-2 rounded">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-house-door"></i> Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/articles/index.php"><i class="bi bi-box-seam"></i> Articles</a></li>
                    <li class="breadcrumb-item active"><i class="bi bi-plus"></i> Nouveau</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                <div>
                    <h5 class="alert-heading mb-2">Erreur de validation</h5>
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Création d'un nouvel article</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row">
                            <!-- Colonne principale -->
                            <div class="col-lg-8">
                                <!-- Section: Informations générales -->
                                <div class="card border mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Informations générales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="code_article" class="form-label required">Code article</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="bi bi-upc-scan text-muted"></i></span>
                                                    <input type="text" class="form-control" id="code_article" name="code_article" required
                                                           value="<?php echo htmlspecialchars($code_article ?? ''); ?>" 
                                                           placeholder="PC-001, MON-001, etc.">
                                                </div>
                                                <div class="form-text">Code unique pour identifier l'article dans le système</div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="designation" class="form-label required">Désignation</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="bi bi-card-text text-muted"></i></span>
                                                    <input type="text" class="form-control" id="designation" name="designation" required
                                                           value="<?php echo htmlspecialchars($designation ?? ''); ?>" 
                                                           placeholder="Ex: Ordinateur portable Dell Latitude 5520">
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" 
                                                           id="actif" name="actif" value="1" <?php echo (!isset($_POST['actif']) || $_POST['actif'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label fw-medium" for="actif">Article actif</label>
                                                </div>
                                                <div class="form-text">Désactivez cette option pour rendre l'article indisponible sans le supprimer</div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="notes" class="form-label">Notes / Description</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light align-items-start pt-2"><i class="bi bi-chat-text text-muted"></i></span>
                                                    <textarea class="form-control" id="notes" name="notes" rows="4"
                                                              placeholder="Description détaillée de l'article, spécifications techniques, remarques particulières..."><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                                                </div>
                                                <div class="form-text">Informations complémentaires (optionnel)</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section: Gestion du stock -->
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-graph-up me-2 text-success"></i>Paramètres de stock</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info border-0 bg-info-subtle mb-4">
                                            <div class="d-flex">
                                                <i class="bi bi-info-circle-fill text-info me-2 fs-5"></i>
                                                <div>
                                                    <h6 class="alert-heading mb-2">Important</h6>
                                                    <p class="mb-0">Le stock initial est modifiable uniquement lors de la création. Par la suite, seule une action spécifique d'administration permettra de le modifier.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="stock_initial" class="form-label required">Stock initial</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="bi bi-box-arrow-in-down text-success"></i></span>
                                                    <input type="number" class="form-control" id="stock_initial" name="stock_initial" required
                                                           value="<?php echo htmlspecialchars($stock_initial ?? 0); ?>" min="0" step="0.01">
                                                </div>
                                                <div class="form-text">Quantité de départ en stock</div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label for="stock_min" class="form-label">Stock minimum</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="bi bi-exclamation-triangle text-warning"></i></span>
                                                    <input type="number" class="form-control" id="stock_min" name="stock_min"
                                                           value="<?php echo htmlspecialchars($stock_min ?? 0); ?>" min="0" step="0.01">
                                                </div>
                                                <div class="form-text">Seuil d'alerte pour réapprovisionnement</div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label for="stock_max" class="form-label">Stock maximum</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="bi bi-boxes text-primary"></i></span>
                                                    <input type="number" class="form-control" id="stock_max" name="stock_max"
                                                           value="<?php echo htmlspecialchars($stock_max ?? 0); ?>" min="0" step="0.01">
                                                </div>
                                                <div class="form-text">Capacité maximale de stockage</div>
                                            </div>
                                        </div>

                                        <div class="alert alert-warning border-0 bg-warning-subtle mt-4">
                                            <div class="d-flex">
                                                <i class="bi bi-lightbulb-fill text-warning me-2 fs-5"></i>
                                                <div>
                                                    <h6 class="alert-heading mb-2">Calcul automatique</h6>
                                                    <p class="mb-0">Les quantités d'entrées, sorties et stock disponible seront calculées automatiquement par les mouvements de stock. Vous n'avez pas à les saisir manuellement.</p>
                                                    <p class="mb-0 mt-2"><strong>Formule :</strong> Stock disponible = Stock initial + Entrées - Sorties</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne latérale -->
                            <div class="col-lg-4">
                                <!-- Carte: Actions -->
                                <div class="card border mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-lightning-charge me-2 text-primary"></i>Actions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                                <i class="bi bi-check-circle me-2"></i>Créer l'article
                                            </button>
                                            <a href="<?php echo BASE_URL; ?>/pages/articles/index.php" class="btn btn-outline-secondary py-3">
                                                <i class="bi bi-x-circle me-2"></i>Annuler
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Carte: Aide -->
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-question-circle me-2 text-info"></i>Conseils et aide</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6 class="text-primary"><i class="bi bi-tags me-1"></i>Code article</h6>
                                            <p class="small text-muted mb-2">Utilisez un code clair et significatif :</p>
                                            <ul class="small text-muted ps-3 mb-0">
                                                <li>PC- pour les ordinateurs</li>
                                                <li>MON- pour les écrans</li>
                                                <li>IMP- pour les imprimantes</li>
                                                <li>Ex: PC-001, MON-024</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6 class="text-primary"><i class="bi bi-graph-up me-1"></i>Gestion des stocks</h6>
                                            <p class="small text-muted mb-2">Définissez des seuils pertinents :</p>
                                            <ul class="small text-muted ps-3 mb-0">
                                                <li><strong>Stock min :</strong> Déclenche les alertes de réappro</li>
                                                <li><strong>Stock max :</strong> Évite le surstockage</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="mb-0">
                                            <h6 class="text-primary"><i class="bi bi-shield-check me-1"></i>Sécurité</h6>
                                            <p class="small text-muted mb-0">Toutes les modifications sont tracées dans le journal d'audit pour assurer la traçabilité complète des opérations.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Carte: Statut -->
                                <div class="card border mt-4">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <i class="bi bi-clock-history fs-3 text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="small text-muted mb-0">Article créé le : <strong><?php echo date('d/m/Y H:i'); ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation en temps réel du code article
document.getElementById('code_article').addEventListener('blur', function() {
    const code = this.value.trim();
    if (code.length > 0) {
        // Mettre en majuscules automatiquement
        this.value = this.value.toUpperCase();
        
        // Vérifier l'unicité via AJAX si nécessaire
        // (à implémenter selon vos besoins)
    }
});

// Validation des seuils de stock
document.getElementById('stock_max').addEventListener('change', function() {
    const stockMin = parseFloat(document.getElementById('stock_min').value) || 0;
    const stockMax = parseFloat(this.value) || 0;
    
    if (stockMax > 0 && stockMin > stockMax) {
        alert('Le stock maximum doit être supérieur au stock minimum');
        this.focus();
    }
});

// Confirmation avant envoi
document.querySelector('form').addEventListener('submit', function(e) {
    const code = document.getElementById('code_article').value.trim();
    const designation = document.getElementById('designation').value.trim();
    
    if (!code || !designation) {
        e.preventDefault();
        alert('Veuillez remplir les champs obligatoires (code article et désignation)');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>