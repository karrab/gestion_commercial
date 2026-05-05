<?php
$page_title = 'Nouveau retour fournisseur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retour_fournisseur', 'create');
$db = Database::getInstance();

// Récupérer la liste des fournisseurs
$db->prepare("SELECT id, nom_complet FROM fournisseurs ORDER BY nom_complet");
$db->execute();
$fournisseurs = $db->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fournisseur_id = intval($_POST['fournisseur_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $articles = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    $errors = [];

    // Validation
    if (empty($fournisseur_id)) {
        $errors[] = 'Le fournisseur est obligatoire.';
    }
    if (empty($date)) {
        $errors[] = 'La date est obligatoire.';
    }
    if (empty($articles) || count($articles) === 0) {
        $errors[] = 'Veuillez ajouter au moins un article.';
    }

    // Gestion de l'upload
    $fichier = '';
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $allowed = ALLOWED_FILE_TYPES;
        $filename = $_FILES['fichier']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= MAX_FILE_SIZE) {
            $uploadDir = UPLOAD_PATH . '/retour_fournisseur';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newname = uniqid() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadDir . '/' . $newname)) {
                $fichier = $newname;
            } else {
                $errors[] = 'Erreur lors de l\'upload du fichier.';
            }
        } else {
            $errors[] = 'Fichier non autorisé ou trop volumineux.';
        }
    }

    if (empty($errors)) {
        try {
            $retourFournisseur = new RetourFournisseur();

            // Préparer les données
            $articlesData = [];
            foreach ($articles as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Vérifier stock disponible
                $db->prepare("SELECT qte_disponible, designation FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $db->execute();
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception("Article ID $article_id introuvable.");
                }

                if ($article['qte_disponible'] < $qte) {
                    throw new Exception("Stock insuffisant pour \"" . $article['designation'] . "\". Disponible: " . number_format($article['qte_disponible'], 2, ',', ' ') . " - Demandé: " . number_format($qte, 2, ',', ' '));
                }

                $articlesData[] = [
                    'article_id' => $article_id,
                    'qte' => $qte
                ];
            }

            if (empty($articlesData)) {
                throw new Exception("Aucun article valide à retourner.");
            }

            $data = [
                'fournisseur_id' => $fournisseur_id,
                'date' => $date,
                'fichier' => $fichier,
                'notes' => $notes,
                'user_id' => $auth->getUserId(),
                'articles' => $articlesData
            ];

            $retour_id = $retourFournisseur->create($data);

            // Log de la trace
            $auth->logTrace($auth->getUserId(), 'retour_fournisseur', 'create', 'retour_fournisseur', $retour_id, "Création retour fournisseur");

            $_SESSION['success'] = 'Retour fournisseur créé avec succès.';
            header('Location: ' . BASE_URL . '/pages/retour_fournisseur/view.php?id=' . $retour_id);
            exit;

        } catch (Exception $e) {
            // Supprimer le fichier uploadé en cas d'erreur
            if (!empty($fichier) && file_exists(UPLOAD_PATH . '/retour_fournisseur/' . $fichier)) {
                unlink(UPLOAD_PATH . '/retour_fournisseur/' . $fichier);
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
            <h2><i class="bi bi-box-arrow-left"></i> Nouveau retour fournisseur</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php">Retours Fournisseur</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
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

    <form method="POST" enctype="multipart/form-data" id="retourForm">
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
                                <select class="form-select" id="fournisseur_id" name="fournisseur_id" required>
                                    <option value="">Sélectionner un fournisseur...</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <option value="<?php echo $fournisseur['id']; ?>">
                                            <?php echo htmlspecialchars($fournisseur['nom_complet']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Fournisseur auquel les articles sont retournés</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label required">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="fichier" class="form-label">Fichier joint</label>
                            <input type="file" class="form-control" id="fichier" name="fichier"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">
                                Bon de retour, bordereau, etc. Formats acceptés: PDF, DOC, DOCX, JPG, PNG. Taille max: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?> MB
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes / Motif du retour</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Ex: Articles défectueux, non conformes, erreur de livraison..."><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Articles -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam"></i> Articles à retourner</span>
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
                                <i class="bi bi-save"></i> Enregistrer le retour
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>

                        <hr>

                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Attention</strong>
                            <p class="mb-0 mt-2 small">
                                Le retour fournisseur <strong>diminue</strong> le stock disponible (articles défectueux retournés au fournisseur).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dernière entrée du fournisseur -->
                <div class="card mt-3" id="lastEntreeCard" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-clock-history"></i> Dernière entrée de ce fournisseur
                    </div>
                    <div class="card-body" id="lastEntreeContent">
                        <p class="text-muted text-center">
                            <i class="bi bi-arrow-up"></i><br>
                            Sélectionnez un fournisseur pour voir sa dernière entrée
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    let articleLineCounter = 0;
    
    // Fonction pour formater la date
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('fr-FR', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }
    
    // Fonction pour formater les nombres
    function formatNumber(number) {
        return parseFloat(number).toLocaleString('fr-FR', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }
    
    // Fonction pour initialiser Select2 sur un élément
    function initSelect2(elementId) {
        $(elementId).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionner un article...',
            allowClear: true,
            ajax: {
                url: '<?php echo BASE_URL; ?>/api/articles.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term || '',
                        stock_only: 1
                    };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            minimumInputLength: 0,
            templateResult: function(item) {
                if (item.loading) return 'Chargement...';
                if (!item.id) return item.text;
                return $('<div>').text(item.text).html();
            },
            templateSelection: function(item) {
                return item.text;
            }
        }).on('select2:select', function(e) {
            const data = e.params.data;
            $(this).closest('tr').find('.stock-disponible')
                .text(formatNumber(data.qte_disponible || 0));
        });
    }
    
    // Ajouter une ligne d'article
    window.addArticleLine = function() {
        articleLineCounter++;
        
        const row = `
            <tr id="articleLine${articleLineCounter}">
                <td>
                    <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                        <option value="">Sélectionner un article...</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control" name="quantite[]" min="0.01" step="0.01" required>
                </td>
                <td>
                    <span class="stock-disponible badge bg-info">-</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${articleLineCounter})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#articlesBody').append(row);
        
        // Initialiser Select2 pour le nouvel article
        initSelect2(`#article_${articleLineCounter}`);
    }
    
    // Supprimer une ligne d'article
    window.removeArticleLine = function(lineId) {
        if ($('#articlesBody tr').length > 1) {
            $(`#articleLine${lineId}`).remove();
        } else {
            alert('Vous devez avoir au moins un article.');
        }
    }
    
    // Charger la dernière entrée du fournisseur
    function loadLastEntree(fournisseurId) {
        if (!fournisseurId) {
            $('#lastEntreeCard').hide();
            return;
        }
        
        $('#lastEntreeContent').html(`
            <div class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2 mb-0">Chargement de la dernière entrée...</p>
            </div>
        `);
        $('#lastEntreeCard').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/last_entree_articles.php',
            method: 'GET',
            data: { 
                fournisseur_id: fournisseurId,
                _t: new Date().getTime() 
            },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (response.success && response.articles && response.articles.length > 0) {
                    let html = `<p class="mb-2"><small class="text-muted">Date: ${formatDate(response.entree.date)}</small></p>`;
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-sm table-bordered mb-0">';
                    html += '<thead class="table-light">';
                    html += '<tr>';
                    html += '<th>Code Article</th>';
                    html += '<th>Designation</th>';
                    html += '<th class="text-end">Qté entrée</th>';
                    html += '<th class="text-end">Stock actuel</th>';
                    html += '</tr>';
                    html += '</thead>';
                    html += '<tbody>';
                    
                    response.articles.forEach(function(article) {
                        html += '<tr>';
                        html += `<td><small><strong>${article.code_article}</strong></small></td>`;
                        html += `<td><small>${article.designation}</small></td>`;
                        html += `<td class="text-end"><span class="badge bg-success">${formatNumber(article.qte_entree)}</span></td>`;
                        html += `<td class="text-end"><span class="badge bg-info">${formatNumber(article.stock_actuel)}</span></td>`;
                        html += '</tr>';
                    });
                    
                    html += '</tbody>';
                    html += '</table>';
                    html += '</div>';
                    html += '<p class="mt-2 mb-0"><small class="text-muted"><i class="bi bi-info-circle"></i> Articles de la dernière entrée</small></p>';
                    
                    $('#lastEntreeContent').html(html);
                } else {
                    $('#lastEntreeContent').html(`
                        <p class="text-muted text-center mb-0">
                            <i class="bi bi-inbox"></i><br>
                            Aucune entrée trouvée pour ce fournisseur
                        </p>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur:', error);
                $('#lastEntreeContent').html(`
                    <p class="text-danger text-center mb-0">
                        <i class="bi bi-exclamation-triangle"></i><br>
                        Erreur lors du chargement
                    </p>
                `);
            }
        });
    }
    
    // Événement lors du changement de fournisseur
    $('#fournisseur_id').on('change', function() {
        const fournisseurId = $(this).val();
        loadLastEntree(fournisseurId);
    });
    
    // Événement pour le formulaire
    $('#retourForm').on('submit', function(e) {
        // Vérifier qu'il y a au moins un article
        const articleCount = $('#articlesBody select[name="article_id[]"]').filter(function() {
            return $(this).val() !== '';
        }).length;
        
        if (articleCount === 0) {
            e.preventDefault();
            alert('Veuillez ajouter au moins un article.');
            return false;
        }
        
        // Vérifier les quantités
        let hasError = false;
        $('input[name="quantite[]"]').each(function() {
            const qte = parseFloat($(this).val());
            const stockSpan = $(this).closest('tr').find('.stock-disponible');
            const stockText = stockSpan.text();
            
            if (stockText !== '-' && !isNaN(qte)) {
                const stock = parseFloat(stockText.replace(/\s/g, '').replace(',', '.'));
                if (qte > stock) {
                    hasError = true;
                    $(this).addClass('is-invalid');
                    stockSpan.addClass('bg-danger').removeClass('bg-info');
                } else {
                    $(this).removeClass('is-invalid');
                    stockSpan.removeClass('bg-danger').addClass('bg-info');
                }
            }
        });
        
        if (hasError) {
            e.preventDefault();
            alert('Certaines quantités dépassent le stock disponible. Veuillez corriger.');
            return false;
        }
    });
    
    // Ajouter la première ligne d'article au chargement
    addArticleLine();
});
</script>
</body>
</html>