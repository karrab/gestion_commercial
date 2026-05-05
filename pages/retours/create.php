<?php
$page_title = 'Nouveau retour';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retours', 'create');
$db = Database::getInstance();

// Récupérer les articles

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $employe_id = intval($_POST['employe_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $articles_data = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    $errors = [];

    if (empty($service_id)) $errors[] = 'Le service est obligatoire.';
    if (empty($employe_id)) $errors[] = 'L\'employé est obligatoire.';
    if (empty($date)) $errors[] = 'La date est obligatoire.';
    if (empty($articles_data)) $errors[] = 'Veuillez ajouter au moins un article.';

    // Vérifier que l'employé appartient au service sélectionné
    $db->prepare("SELECT service_id FROM employes WHERE id = ?");
    $db->execute([$employe_id]);
    $employe = $db->fetch();
    if ($employe && $employe['service_id'] != $service_id) {
        $errors[] = 'L\'employé sélectionné n\'appartient pas au service choisi.';
    }

    // Upload fichier
    $fichier = '';
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite php.ini : upload_max_filesize=' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (limite formulaire).',
                UPLOAD_ERR_PARTIAL    => 'Fichier partiellement telecharge.',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
                UPLOAD_ERR_CANT_WRITE => 'Impossible d\'ecrire le fichier sur le disque.',
                UPLOAD_ERR_EXTENSION  => 'Upload bloque par une extension PHP.',
            ];
            $errors[] = $upload_errors[$_FILES['fichier']['error']] ?? 'Erreur upload inconnue (code ' . $_FILES['fichier']['error'] . ').';
        } else {
            $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_FILE_TYPES)) {
                $errors[] = 'Type de fichier non autorise. Formats acceptes : ' . implode(', ', ALLOWED_FILE_TYPES);
            } elseif ($_FILES['fichier']['size'] > MAX_FILE_SIZE) {
                $errors[] = 'Fichier trop volumineux. Maximum : ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB.';
            } else {
                if (!is_dir(UPLOAD_RETOURS_PATH)) {
                    mkdir(UPLOAD_RETOURS_PATH, 0775, true);
                }
                $newname = uniqid() . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], UPLOAD_RETOURS_PATH . DIRECTORY_SEPARATOR . $newname)) {
                    $fichier = $newname;
                } else {
                    $errors[] = 'Echec de l\'enregistrement du fichier.';
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert entête retour avec paramètres positionnels
            $sql = "INSERT INTO retours (service_id, employe_id, date, fichier, notes, user_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $db->prepare($sql);
            $db->execute([$service_id, $employe_id, $date, $fichier, $notes, $auth->getUserId()]);

            $retour_id = $db->lastInsertId();

            // Insert lignes retour + mise à jour stock
            foreach ($articles_data as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer info article
                $db->prepare("SELECT code_article, designation FROM articles WHERE id = ?");
                $db->execute([$article_id]);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // Insert ligne retour avec paramètres positionnels
                $sql = "INSERT INTO ligne_retours (retour_id, article_id, code_article, designation, qte_retour, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())";
                
                $db->prepare($sql);
                $db->execute([$retour_id, $article_id, $article['code_article'], $article['designation'], $qte]);

                // MISE À JOUR DU STOCK - RETOUR AJOUTE AU STOCK avec paramètres positionnels
                $sql = "UPDATE articles
                        SET qte_retour = qte_retour + ?,
                            qte_disponible = qte_disponible + ?
                        WHERE id = ?";
                
                $db->prepare($sql);
                $db->execute([$qte, $qte, $article_id]);
            }

            $auth->logTrace($auth->getUserId(), 'retours', 'create', 'retours', $retour_id, "Création retour #$retour_id");
            $db->commit();

            $_SESSION['success'] = 'Retour créé avec succès.';
            header('Location: ' . BASE_URL . '/pages/retours/index.php?id=' . $retour_id);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            if (!empty($fichier) && file_exists(UPLOAD_RETOURS_PATH . DIRECTORY_SEPARATOR . $fichier)) {
                unlink(UPLOAD_RETOURS_PATH . DIRECTORY_SEPARATOR . $fichier);
            }
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<style>
.select2-article-option {
    padding: 5px;
    border-bottom: 1px solid #eee;
}

.select2-article-option:last-child {
    border-bottom: none;
}

.select2-article-option small {
    font-size: 0.85em;
    opacity: 0.8;
}

.stock-info {
    font-size: 0.8rem;
    margin-top: 2px;
    display: block;
}

.select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
}
</style>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-arrow-in-up"></i> Nouveau retour</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/retours/index.php">Retours</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="retourForm">
        <div class="row">
            <div class="col-md-8">
                <!-- Informations retour -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person"></i> Retour par</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label required">Service</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Sélectionner un service...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_id" class="form-label required">Employé</label>
                                <select class="form-select" id="employe_id" name="employe_id" required disabled>
                                    <option value="">Sélectionnez d'abord un service</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label required">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Articles -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles retournés</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                            <i class="bi bi-plus-circle"></i> Ajouter
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50%">Article</th>
                                        <th width="30%">Quantité</th>
                                        <th width="20%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody">
                                    <!-- La première ligne sera ajoutée automatiquement par JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fichier et notes -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-file-text"></i> Informations complémentaires</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="fichier" class="form-label">Fichier joint</label>
                            <input type="file" class="form-control" id="fichier" name="fichier">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mt-3">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/retours/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Information</strong>
                            <p class="mb-0 mt-2 small">
                                Les articles retournés seront automatiquement ajoutés au stock disponible.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dernière sortie de l'employé -->
                <div class="card mt-3" id="derniereSortieCard" style="display:none;">
                    <div class="card-header bg-warning-subtle">
                        <i class="bi bi-clock-history"></i> Dernière sortie de l'employé
                    </div>
                    <div class="card-body p-2" id="derniereSortieBody">
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

    // Service via AJAX (initServiceSelect de main.js)
    initServiceSelect('#service_id');

    // Employé : désactivé par défaut
    $('#employe_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Sélectionnez d\'abord un service',
        allowClear: true,
        width: '100%'
    }).prop('disabled', true);

    // Quand service change → charger employés
    $('#service_id').on('change', function() {
        const serviceId = $(this).val();
        const $emp = $('#employe_id');
        $emp.empty().append('<option value="">Sélectionner un employé...</option>').prop('disabled', true);
        $('#derniereSortieCard').hide();

        if (!serviceId) return;

        $.ajax({
            url: BASE_URL + '/api/getemployebyservice1.php',
            data: { service_id: serviceId },
            dataType: 'json',
            success: function(data) {
                if (data && data.length > 0) {
                    $.each(data, function(i, e) {
                        $emp.append(new Option(e.text, e.id));
                    });
                    $emp.prop('disabled', false);
                } else {
                    $emp.append('<option value="">Aucun employé dans ce service</option>');
                }
                $emp.trigger('change.select2');
            }
        });
    });

    // Quand employé change → charger dernière sortie
    $('#employe_id').on('change', function() {
        const employeId = $(this).val();
        if (!employeId) { $('#derniereSortieCard').hide(); return; }
        chargerDerniereSortie(employeId);
    });

    // Première ligne article
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
                <small class="stock-info form-text"></small>
            </td>
            <td>
                <input type="number" class="form-control article-quantity" name="quantite[]" min="0.01" step="0.01" required>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;

    $('#articlesBody').append(row);
    initArticleSelect('#article_' + id);

    $('#article_' + id).on('select2:select select2:clear', function() {
        updateStockInfo($(this).closest('tr'));
    });
    $('#articleLine' + id + ' .article-quantity').on('input', function() {
        updateStockInfo($(this).closest('tr'));
    });
}

function removeArticleLine(id) {
    if ($('#articlesBody tr').length > 1) {
        $('#articleLine' + id).remove();
    } else {
        alert('Vous devez avoir au moins un article.');
    }
}

function updateStockInfo(row) {
    const data = row.find('select').select2('data');
    const $info = row.find('.stock-info');
    $info.removeClass('text-success text-warning text-danger');

    if (!data || !data[0] || !data[0].id) { $info.text(''); return; }

    const item      = data[0];
    const stockAct  = parseFloat(item.qte_disponible) || 0;
    const stockMin  = parseFloat(item.stock_min) || 0;
    const stockMax  = parseFloat(item.stock_max) || 0;
    const qte       = parseFloat(row.find('.article-quantity').val()) || 0;
    const newStock  = stockAct + qte;

    let msg = 'Stock actuel: ' + stockAct.toFixed(2) + ' | Après retour: ' + newStock.toFixed(2);

    if (stockMax > 0 && newStock > stockMax) {
        msg += ' — dépassera le maximum'; $info.addClass('text-danger');
    } else if (newStock < stockMin) {
        msg += ' — en dessous du minimum'; $info.addClass('text-warning');
    } else {
        $info.addClass('text-success');
    }
    $info.text(msg);
}

function chargerDerniereSortie(employeId) {
    $.ajax({
        url: BASE_URL + '/api/getlastsortie.php',
        data: { employe_id: employeId },
        dataType: 'json',
        success: function(sortie) {
            if (!sortie) {
                $('#derniereSortieCard').hide();
                return;
            }
            let html = '<p class="mb-1"><small class="text-muted">Sortie #' + sortie.id + ' du <strong>'
                + sortie.date + '</strong></small></p>'
                + '<table class="table table-sm table-bordered mb-0">'
                + '<thead><tr><th>Code</th><th>Désignation</th><th class="text-end">Qté</th></tr></thead><tbody>';
            sortie.lignes.forEach(function(l) {
                html += '<tr><td>' + l.code_article + '</td><td>' + l.designation
                    + '</td><td class="text-end">' + parseFloat(l.qte_sortie).toFixed(2) + '</td></tr>';
            });
            html += '</tbody></table>';
            if (sortie.notes) {
                html += '<p class="mt-1 mb-0"><small class="text-muted">' + sortie.notes + '</small></p>';
            }
            $('#derniereSortieBody').html(html);
            $('#derniereSortieCard').show();
        },
        error: function() { $('#derniereSortieCard').hide(); }
    });
}
</script>