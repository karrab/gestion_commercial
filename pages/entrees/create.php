<?php
$page_title = 'Nouvelle entrée';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('entrees', 'create');
$db = Database::getInstance();

// Récupérer la liste des fournisseurs
$db->prepare("SELECT id, nom_complet FROM fournisseurs WHERE actif = 1 ORDER BY nom_complet");
$fournisseurs = $db->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $articles_post = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    $errors = [];

    // Validation
    if (empty($fournisseur_id)) {
        $errors[] = 'Le fournisseur est obligatoire.';
    }
    if (empty($date)) {
        $errors[] = 'La date est obligatoire.';
    }
    if (empty($articles_post) || count($articles_post) === 0) {
        $errors[] = 'Veuillez ajouter au moins un article.';
    }

    // Gestion de l'upload
    $fichier = '';
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite serveur php.ini).',
                UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (limite formulaire).',
                UPLOAD_ERR_PARTIAL    => 'Fichier partiellement telecharge.',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
                UPLOAD_ERR_CANT_WRITE => 'Impossible d\'ecrire le fichier sur le disque.',
                UPLOAD_ERR_EXTENSION  => 'Upload bloque par une extension PHP.',
            ];
            $errors[] = $upload_errors[$_FILES['fichier']['error']] ?? 'Erreur upload inconnue (code ' . $_FILES['fichier']['error'] . ').';
        } else {
            $allowed = ALLOWED_FILE_TYPES;
            $filename = $_FILES['fichier']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $errors[] = 'Type de fichier non autorise. Formats acceptes : ' . implode(', ', $allowed);
            } elseif ($_FILES['fichier']['size'] > MAX_FILE_SIZE) {
                $errors[] = 'Fichier trop volumineux. Maximum : ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB.';
            } else {
                // Creer le dossier s'il n'existe pas
                if (!is_dir(UPLOAD_ENTREES_PATH)) {
                    mkdir(UPLOAD_ENTREES_PATH, 0775, true);
                }
                $newname = uniqid() . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], UPLOAD_ENTREES_PATH . DIRECTORY_SEPARATOR . $newname)) {
                    $fichier = $newname;
                } else {
                    $errors[] = 'Echec de l\'enregistrement du fichier. Verifiez les permissions du dossier : ' . UPLOAD_ENTREES_PATH;
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert entête d'entrée avec paramètres de position
            $sql = "INSERT INTO entrees (fournisseur_id, date, fichier, notes, user_id, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";

            $db->prepare($sql);
            $db->bind(1, $fournisseur_id);
            $db->bind(2, $date);
            $db->bind(3, $fichier);
            $db->bind(4, $notes);
            $db->bind(5, $auth->getUserId());
            $db->execute();

            $entree_id = $db->lastInsertId();

            // Insert lignes d'entrée
            foreach ($articles_post as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer les infos de l'article
                $db->prepare("SELECT code_article, designation, qte_disponible, stock_initial, stock_min, stock_max FROM articles WHERE id = ?");
                $db->bind(1, $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // Stock avant l'opération
                $stock_avant = $article['qte_disponible'];
                $stock_apres = $stock_avant + $qte;

                // Insert ligne avec paramètres de position
                $sql = "INSERT INTO ligne_entrees (entree_id, article_id, code_article, designation, qte_entree)
                        VALUES (?, ?, ?, ?, ?)";

                $db->prepare($sql);
                $db->bind(1, $entree_id);
                $db->bind(2, $article_id);
                $db->bind(3, $article['code_article']);
                $db->bind(4, $article['designation']);
                $db->bind(5, $qte);
                $db->execute();

                // Mise à jour du stock de l'article avec paramètres de position
                $sql = "UPDATE articles
                        SET qte_entree = qte_entree + ?,
                            qte_disponible = qte_disponible + ?
                        WHERE id = ?";

                $db->prepare($sql);
                $db->bind(1, $qte);
                $db->bind(2, $qte);
                $db->bind(3, $article_id);
                $db->execute();

                // Enregistrement dans l'historique des articles
$sql = "INSERT INTO historique_article 
        (code_article, designation, operation, qte_entree, qte,
         stock_avant_operation, stock_apres_operation,
         stock_initial, stock_min, stock_max,
         article_id, entree_id, user_id, date_operation, commentaire, created_at)
        VALUES (?, ?, 'entree', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
$db->prepare($sql);
$db->bind(1, $article['code_article']);
$db->bind(2, $article['designation']);
$db->bind(3, $qte);  // qte_entree
$db->bind(4, $qte);  // qte (même valeur que qte_entree)
$db->bind(5, $stock_avant);
$db->bind(6, $stock_apres);
$db->bind(7, $article['stock_initial']);
$db->bind(8, $article['stock_min']);
$db->bind(9, $article['stock_max']);
$db->bind(10, $article_id);
$db->bind(11, $entree_id);
$db->bind(12, $auth->getUserId());
$db->bind(13, $date . ' 00:00:00');
$db->bind(14, "Entrée depuis fournisseur (Entrée ID: $entree_id)");
$db->execute();
            }

            // Log de la trace
            $auth->logTrace($auth->getUserId(), 'entrees', 'create', 'entrees', $entree_id, "Création entrée");

            $db->commit();

            $_SESSION['success'] = 'Entrée créée avec succès.';
            // Redirection vers index.php au lieu de view.php pour éviter l'erreur
            header('Location: ' . BASE_URL . '/pages/entrees/index.php');
            exit;

        } catch (Exception $e) {
            $db->rollback();
            // Supprimer le fichier uploadé en cas d'erreur
            if (!empty($fichier) && file_exists(UPLOAD_ENTREES_PATH . '/' . $fichier)) {
                unlink(UPLOAD_ENTREES_PATH . '/' . $fichier);
            }
            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-arrow-in-down"></i> Nouvelle entrée</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/entrees/index.php">Entrées</a></li>
                    <li class="breadcrumb-item active">Nouvelle</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="entreeForm">
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-md-8">
                <!-- Informations générales -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Informations générales
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fournisseur_id" class="form-label required">Fournisseur</label>
                                <select class="form-select select2" id="fournisseur_id" name="fournisseur_id" required>
                                    <option value="">Sélectionner un fournisseur...</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <option value="<?php echo $fournisseur['id']; ?>" <?php echo isset($fournisseur_id) && $fournisseur_id == $fournisseur['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fournisseur['nom_complet']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label required">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required
                                       value="<?php echo isset($date) ? $date : date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="fichier" class="form-label">Fichier joint</label>
                            <input type="file" class="form-control" id="fichier" name="fichier"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">
                                Formats acceptés: PDF, DOC, DOCX, JPG, PNG. Taille max: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?> MB
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($notes) ? htmlspecialchars($notes) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Articles -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                            <i class="bi bi-plus-circle"></i> Ajouter un article
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="articlesTable">
                                <thead>
                                    <tr>
                                        <th width="50%">Article</th>
                                        <th width="20%">Quantité</th>
                                        <th width="20%">Stock disponible</th>
                                        <th width="10%" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody">
                                    <!-- Les lignes seront ajoutées ici par JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne latérale -->
            <div class="col-md-4">
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="bi bi-gear"></i> Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer l'entrée
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>

                        <hr>

                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Information</strong>
                            <p class="mb-0 mt-2 small">
                                Le stock des articles sera automatiquement mis à jour lors de l'enregistrement.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let articleLineCounter = 0;

$(document).ready(function() {
    $('#fournisseur_id').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Sélectionner un fournisseur...', allowClear: true });
    addArticleLine();
});

function addArticleLine() {
    articleLineCounter++;
    const id = articleLineCounter;

    const row = `
        <tr id="articleLine${id}">
            <td>
                <select class="form-select" name="article_id[]" id="article_${id}" required>
                    <option value=""></option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control qte-input" name="quantite[]" min="0.01" step="0.01" required>
            </td>
            <td>
                <span class="stock-disponible badge bg-info">-</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;

    $('#articlesBody').append(row);

    initArticleSelect('#article_' + id);

    $('#article_' + id).on('select2:select select2:clear', function() {
        updateStockDisplay($(this).closest('tr'));
    });

    $('#article_' + id).closest('tr').find('.qte-input').on('input', function() {
        updateStockDisplay($(this).closest('tr'));
    });
}

function removeArticleLine(lineId) {
    if ($('#articlesBody tr').length > 1) {
        $('#articleLine' + lineId).remove();
    } else {
        alert('Vous devez avoir au moins un article.');
    }
}

function updateStockDisplay(row) {
    const select = row.find('select');
    const data = select.select2('data');
    const badge = row.find('.stock-disponible');
    badge.removeClass('bg-success bg-warning bg-danger bg-info');

    if (!data || data.length === 0 || !data[0].id) {
        badge.text('-').addClass('bg-info');
        return;
    }

    const item = data[0];
    const stockActuel = parseFloat(item.qte_disponible) || 0;
    const stockMin    = parseFloat(item.stock_min) || 0;
    const stockMax    = parseFloat(item.stock_max) || 0;
    const quantite    = parseFloat(row.find('.qte-input').val()) || 0;
    const nouveauStock = stockActuel + quantite;

    badge.text(formatNumber(nouveauStock));

    if (stockMax > 0 && nouveauStock > stockMax) {
        badge.addClass('bg-danger');
    } else if (nouveauStock < stockMin) {
        badge.addClass('bg-warning');
    } else if (nouveauStock === 0) {
        badge.addClass('bg-danger');
    } else {
        badge.addClass('bg-success');
    }
}

function formatNumber(num) {
    if (isNaN(num)) return '0.00';
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}
</script>