<?php
$page_title = 'Nouvelle sortie';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('sorties', 'create');
$db = Database::getInstance();

/**
 * Formate un nombre en supprimant les zéros décimaux inutiles
 * @param float $number Le nombre à formater
 * @param int $decimals Nombre maximum de décimales
 * @return string Nombre formaté
 */
function formatNumber($number, $decimals = 2) {
    if (!is_numeric($number)) return $number;
    
    // Formater avec séparateur de milliers espace et virgule pour décimales
    $formatted = number_format($number, $decimals, ',', ' ');
    
    // Supprimer les zéros inutiles à la fin
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, ',');
    
    return $formatted;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $employe_id = intval($_POST['employe_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $articles = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    $errors = [];

    if (empty($service_id)) $errors[] = 'Le service est obligatoire.';
    if (empty($employe_id)) $errors[] = 'L\'employé est obligatoire.';
    if (empty($date)) $errors[] = 'La date est obligatoire.';
    if (empty($articles)) $errors[] = 'Veuillez ajouter au moins un article.';

    // Upload fichier
    $fichier = '';
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $allowed = ALLOWED_FILE_TYPES;
        $filename = $_FILES['fichier']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= MAX_FILE_SIZE) {
            $newname = uniqid() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], UPLOAD_SORTIES_PATH . '/' . $newname)) {
                $fichier = $newname;
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Récupérer les informations de l'employé pour le commentaire
            $db->prepare("SELECT nom, prenom FROM employes WHERE id = :id");
            $db->bind(':id', $employe_id);
            $employe = $db->fetch();
            
            if (!$employe) {
                throw new Exception('Employé ID ' . $employe_id . ' introuvable.');
            }
            
            $employe_nom_complet = $employe['prenom'] . ' ' . $employe['nom'];

            // Insert entête sortie
            $sql = "INSERT INTO sorties (service_id, employe_id, date, fichier, notes, user_id)
                    VALUES (:service_id, :employe_id, :date, :fichier, :notes, :user_id)";

            $db->prepare($sql);
            $db->bind(':service_id', $service_id);
            $db->bind(':employe_id', $employe_id);
            $db->bind(':date', $date);
            $db->bind(':fichier', $fichier);
            $db->bind(':notes', $notes);
            $db->bind(':user_id', $auth->getUserId());
            $db->execute();

            $sortie_id = $db->lastInsertId();

            // Insert lignes sortie avec vérification stock
            foreach ($articles as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer info article ET vérifier stock disponible
                $db->prepare("SELECT id, code_article, designation, qte_disponible, stock_initial, stock_min, stock_max 
                             FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // ⚠️ VÉRIFICATION CRITIQUE DU STOCK
                if ($article['qte_disponible'] < $qte) {
                    throw new Exception('Quantité indisponible en stock pour "' . $article['designation'] .
                                      '". Disponible: ' . formatNumber($article['qte_disponible']) .
                                      ' - Demandé: ' . formatNumber($qte));
                }

                // Calcul des stocks avant/après opération
                $stock_avant_operation = $article['qte_disponible'];
                $stock_apres_operation = $stock_avant_operation - $qte;

                // Insert ligne sortie
                $sql = "INSERT INTO ligne_sorties (sortie_id, article_id, code_article, designation, qte_sortie)
                        VALUES (:sortie_id, :article_id, :code, :designation, :qte)";

                $db->prepare($sql);
                $db->bind(':sortie_id', $sortie_id);
                $db->bind(':article_id', $article_id);
                $db->bind(':code', $article['code_article']);
                $db->bind(':designation', $article['designation']);
                $db->bind(':qte', $qte);
                $db->execute();

                // Mise à jour stock article
                $sql = "UPDATE articles
                        SET qte_sortie = qte_sortie + ?,
                            qte_disponible = qte_disponible - ?
                        WHERE id = ?";

                $db->prepare($sql);
                $db->bind(1, $qte);
                $db->bind(2, $qte);
                $db->bind(3, $article_id);
                $db->execute();

                // ENREGISTRER L'HISTORIQUE DE L'ARTICLE
                $sql_historique = "INSERT INTO historique_article 
                    (code_article, designation, operation, qte, 
                     qte_sortie, stock_avant_operation, stock_apres_operation,
                     stock_initial, stock_min, stock_max,
                     article_id, sortie_id, user_id, date_operation, commentaire)
                    VALUES (?, ?, 'sortie', ?, 
                            ?, ?, ?,
                            ?, ?, ?,
                            ?, ?, ?, ?, ?)";

                $commentaire = 'Sortie employé: ' . $employe_nom_complet . ' de ' . formatNumber($qte) . ' unité(s). ID_sortie: ' . $sortie_id;

                $db->prepare($sql_historique);
                $db->bind(1, $article['code_article']);
                $db->bind(2, $article['designation']);
                $db->bind(3, $qte);
                $db->bind(4, $qte);
                $db->bind(5, $stock_avant_operation);
                $db->bind(6, $stock_apres_operation);
                $db->bind(7, $article['stock_initial']);
                $db->bind(8, $article['stock_min']);
                $db->bind(9, $article['stock_max']);
                $db->bind(10, $article_id);
                $db->bind(11, $sortie_id);
                $db->bind(12, $auth->getUserId());
                $db->bind(13, $date . ' ' . date('H:i:s'));
                $db->bind(14, $commentaire);
                $db->execute();
            }

            $auth->logTrace($auth->getUserId(), 'sorties', 'create', 'sorties', $sortie_id, "Création sortie");
            $db->commit();

            $_SESSION['success'] = 'Sortie créée avec succès.';
            header('Location: ' . BASE_URL . '/pages/sorties/view.php?id=' . $sortie_id);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            if (!empty($fichier) && file_exists(UPLOAD_SORTIES_PATH . '/' . $fichier)) {
                unlink(UPLOAD_SORTIES_PATH . '/' . $fichier);
            }
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-arrow-up"></i> Nouvelle sortie</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/sorties/index.php">Sorties</a></li>
                    <li class="breadcrumb-item active">Nouvelle</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <!-- Informations demandeur -->
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person"></i> Informations du demandeur</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label required">Service</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Sélectionner...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_id" class="form-label required">Employé</label>
                                <select class="form-select" id="employe_id" name="employe_id" required>
                                    <option value="">Sélectionner...</option>
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
                        <span><i class="bi bi-box-seam"></i> Articles</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                            <i class="bi bi-plus-circle"></i> Ajouter
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="45%">Article</th>
                                        <th width="20%">Quantité</th>
                                        <th width="25%">Stock disponible</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="articlesBody"></tbody>
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
                            <a href="<?php echo BASE_URL; ?>/pages/sorties/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                        <hr>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Attention</strong>
                            <p class="mb-0 mt-2 small">
                                La quantité demandée sera vérifiée avec le stock disponible. Un message d'erreur s'affichera si le stock est insuffisant.
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
// Définir BASE_URL pour JavaScript
const BASE_URL = '<?php echo BASE_URL; ?>';
let articleLineCounter = 0;

// Fonction pour formater les nombres sans zéros inutiles
function formatNumber(value) {
    if (value === null || value === undefined || value === '') return '-';
    
    // Convertir en nombre
    const num = parseFloat(value);
    if (isNaN(num)) return '-';
    
    // Formater avec séparateur d'espace pour les milliers
    return num.toLocaleString('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
        useGrouping: true
    }).replace(/\u202F/g, ' '); // Remplacer l'espace insécable par un espace normal
}

$(document).ready(function() {
    // Initialiser Select2 avec AJAX pour les services
    initServiceSelect('#service_id', 'Sélectionner un service...');

    // Charger employés quand service demandeur change
    $('#service_id').on('change', function() {
        let service_id = $(this).val();

        // Réinitialiser le select employe_id
        $('#employe_id').html('<option value="">Sélectionner un employé...</option>').prop('disabled', !service_id);

        if (service_id) {
            // Charger les employés du service sélectionné
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                data: { service_id: service_id, search: '' },
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(employe) {
                            $('#employe_id').append(new Option(employe.text, employe.id));
                        });
                    } else {
                        $('#employe_id').html('<option value="">Aucun employé dans ce service</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading employes:', error);
                    $('#employe_id').html('<option value="">Erreur de chargement</option>');
                }
            });
        }
    });

    addArticleLine();
});

function addArticleLine() {
    articleLineCounter++;
    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                    <option value="">Sélectionner...</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control qte-input" name="quantite[]" min="0.01" step="0.01" required>
            </td>
            <td>
                <span class="stock-disponible badge bg-info">-</span>
                <span class="stock-warning text-danger" style="display:none;">
                    <i class="bi bi-exclamation-triangle"></i> Stock insuffisant
                </span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${articleLineCounter})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#articlesBody').append(row);

    const selectId = '#article_' + articleLineCounter;
    initArticleSelect(selectId);

    $(selectId).on('select2:select', function(e) {
        const data = e.params.data;
        const row = $(this).closest('tr');
        const stockFormatted = formatNumber(data.qte_disponible);
        row.find('.stock-disponible').text('Stock: ' + stockFormatted);
        row.data('stock-disponible', data.qte_disponible);
    });

    // Vérifier stock quand quantité change
    $(selectId).closest('tr').find('.qte-input').on('input', function() {
        const row = $(this).closest('tr');
        const stock = parseFloat(row.data('stock-disponible')) || 0;
        const qte = parseFloat($(this).val()) || 0;

        if (qte > stock) {
            row.find('.stock-warning').show();
            $(this).addClass('is-invalid');
        } else {
            row.find('.stock-warning').hide();
            $(this).removeClass('is-invalid');
        }
    });
}

function removeArticleLine(lineId) {
    if ($('#articlesBody tr').length > 1) {
        $('#articleLine' + lineId).remove();
    } else {
        alert('Vous devez avoir au moins un article.');
    }
}
</script>