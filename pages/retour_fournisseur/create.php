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
            foreach ($articles_post as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = intval($quantites[$index]);

                // Vérifier stock disponible
                $db->prepare("SELECT qte_disponible, designation FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $db->execute();
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception("Article ID $article_id introuvable.");
                }

                if ($article['qte_disponible'] < $qte) {
                    throw new Exception("Stock insuffisant pour \"" . $article['designation'] . "\". Disponible: " . number_format($article['qte_disponible'], 0, ',', ' ') . " - Demandé: " . number_format($qte, 0, ',', ' '));
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

            // DEBUG: Afficher les données pour vérification
            // error_log("Retour créé avec ID: $retour_id");
            // error_log("Articles à retourner: " . print_r($articlesData, true));

           // Enregistrer l'historique des articles retournés un par un
foreach ($articlesData as $articleData) {
    $article_id = $articleData['article_id'];
    $qte_retour = $articleData['qte'];
    
    // IMPORTANT: Récupérer les informations de l'article AVANT création du retour
    // On doit d'abord récupérer les valeurs AVANT toute modification
    $db->prepare("SELECT 
        code_article, 
        designation, 
        qte_disponible,
        qte_retour_frs,
        stock_initial,
        qte_entree,
        qte_sortie,
        qte_retour,
        stock_min,
        stock_max 
        FROM articles 
        WHERE id = :id");
    $db->bind(':id', $article_id);
    $db->execute();
    $article = $db->fetch();
    
    if ($article) {
        // CORRECTION: Le stock AVANT opération doit être calculé AVANT l'ajout du retour
        // stock_avant_operation = qte_disponible AVANT l'opération
        // Mais attention: qte_disponible actuel pourrait déjà être modifié par RetourFournisseur::create()
        
        // Donc on recalcule le stock AVANT opération à partir des valeurs brutes:
        $stock_avant_operation = $article['qte_disponible'];
        
        // MAIS: Si RetourFournisseur::create() a déjà ajouté à qte_retour_frs,
        // alors qte_disponible actuel est DÉJÀ diminué!
        // Il faut donc RE-ajouter la quantité retournée pour avoir le vrai "avant"
        // Correction: stock_avant_operation = qte_disponible_actuel + qte_retour
        $stock_avant_operation_corrige = $article['qte_disponible'] + $qte_retour;
        
        // Stock APRÈS opération = stock_avant_corrigé - qte_retour
        $stock_apres_operation = $stock_avant_operation_corrige - $qte_retour;
        
        // Vérification: $stock_apres_operation devrait être égal à $article['qte_disponible']
        // car c'est la valeur après que RetourFournisseur::create() a déjà fait les calculs
        
        // CALCUL IMPORTANT: Ajouter la quantité retournée à qte_retour_frs
        $nouvelle_qte_retour_frs = intval($article['qte_retour_frs']) + $qte_retour;
        
        // Récupérer le nom du fournisseur pour le commentaire
        $db->prepare("SELECT nom_complet FROM fournisseurs WHERE id = :fournisseur_id");
        $db->bind(':fournisseur_id', $fournisseur_id);
        $db->execute();
        $fournisseur = $db->fetch();
        $nom_fournisseur = $fournisseur ? $fournisseur['nom_complet'] : 'N/A';
        
        $commentaire = "Création de retour fournisseur : $nom_fournisseur. Retour de " . number_format($qte_retour, 0, '.', '') . " unité(s). Retour fournisseur N° : $retour_id";
        
        // Insérer dans l'historique avec stock_avant_operation CORRIGÉ
        $db->prepare("INSERT INTO historique_article 
            (code_article, designation, operation, qte_retour_fournisseur, qte, 
             stock_avant_operation, stock_apres_operation, stock_initial, 
             stock_min, stock_max, article_id, retour_fournisseur_id, 
             user_id, date_operation, commentaire) 
            VALUES 
            (:code_article, :designation, 'retour_fournisseur', :qte_retour_fournisseur, :qte_val,
             :stock_avant_operation, :stock_apres_operation, :stock_initial,
             :stock_min, :stock_max, :article_id, :retour_fournisseur_id,
             :user_id, :date_operation, :commentaire)");
        
        $db->bind(':code_article', $article['code_article']);
        $db->bind(':designation', $article['designation']);
        $db->bind(':qte_retour_fournisseur', $qte_retour);
        $db->bind(':qte_val', $qte_retour);
        $db->bind(':stock_avant_operation', $stock_avant_operation_corrige); // CORRIGÉ
        $db->bind(':stock_apres_operation', $stock_apres_operation);
        $db->bind(':stock_initial', $article['stock_initial']);
        $db->bind(':stock_min', $article['stock_min']);
        $db->bind(':stock_max', $article['stock_max']);
        $db->bind(':article_id', $article_id);
        $db->bind(':retour_fournisseur_id', $retour_id);
        $db->bind(':user_id', $auth->getUserId());
        $db->bind(':date_operation', date('Y-m-d H:i:s'));
        $db->bind(':commentaire', $commentaire);
        
        $db->execute();
        
        // MISE À JOUR CRITIQUE: Mettre à jour la colonne qte_retour_frs dans la table articles
        // 1. D'abord, mettre à jour qte_retour_frs (addition)
        $db->prepare("UPDATE articles 
            SET qte_retour_frs = :nouvelle_qte_retour_frs,
                updated_at = NOW()
            WHERE id = :id_article");
        $db->bind(':nouvelle_qte_retour_frs', $nouvelle_qte_retour_frs);
        $db->bind(':id_article', $article_id);
        $db->execute();
        
        // 2. Ensuite, recalculer qte_disponible avec la formule
        $db->prepare("UPDATE articles 
            SET qte_disponible = stock_initial + qte_entree + qte_retour - qte_sortie - qte_retour_frs,
                updated_at = NOW()
            WHERE id = :id_article");
        $db->bind(':id_article', $article_id);
        $db->execute();
    }
}
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
            // DEBUG: Afficher l'erreur complète
            // error_log("Erreur détaillée: " . $e->getMessage());
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
                <div class="card sticky-top" style="top: 20px;">
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
const BASE_URL = '<?php echo BASE_URL; ?>';
let articleLineCounter = 0;

$(document).ready(function() {
    // Ajouter une première ligne d'article
    addArticleLine();

    // Initialiser Select2 pour le fournisseur
    $('#fournisseur_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Sélectionner un fournisseur...',
        allowClear: false
    });

    // Charger la dernière entrée du fournisseur
    function loadLastEntree(fournisseurId) {
        if (!fournisseurId) {
            $('#lastEntreeCard').hide();
            return;
        }

        $('#lastEntreeContent').html(`
            <div class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <p class="mt-2 mb-0">Chargement...</p>
            </div>
        `);
        $('#lastEntreeCard').show();

        $.ajax({
            url: BASE_URL + '/api/last_entree_articles.php',
            method: 'GET',
            data: { fournisseur_id: fournisseurId, _t: Date.now() },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (response.success && response.articles && response.articles.length > 0) {
                    const d = new Date(response.entree.date);
                    const dateStr = d.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
                    let html = `<p class="mb-2"><small class="text-muted">Date: ${dateStr}</small></p>`;
                    html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
                    html += '<thead class="table-light"><tr><th>Code</th><th>Désignation</th><th class="text-end">Qté</th><th class="text-end">Stock</th></tr></thead><tbody>';
                    response.articles.forEach(function(a) {
                        html += `<tr>
                            <td><small><strong>${a.code_article}</strong></small></td>
                            <td><small>${a.designation}</small></td>
                            <td class="text-end"><span class="badge bg-success">${parseInt(a.qte_entree).toLocaleString('fr-FR')}</span></td>
                            <td class="text-end"><span class="badge bg-info">${parseInt(a.stock_actuel).toLocaleString('fr-FR')}</span></td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    $('#lastEntreeContent').html(html);
                } else {
                    $('#lastEntreeContent').html('<p class="text-muted text-center mb-0"><i class="bi bi-inbox"></i><br>Aucune entrée trouvée</p>');
                }
            },
            error: function() {
                $('#lastEntreeContent').html('<p class="text-danger text-center mb-0"><i class="bi bi-exclamation-triangle"></i><br>Erreur de chargement</p>');
            }
        });
    }

    $('#fournisseur_id').on('change', function() {
        loadLastEntree($(this).val());
    });

    // Validation au submit
    $('#retourForm').on('submit', function(e) {
        const articleCount = $('#articlesBody select[name="article_id[]"]').filter(function() {
            return $(this).val() !== '';
        }).length;

        if (articleCount === 0) {
            e.preventDefault();
            alert('Veuillez ajouter au moins un article.');
            return false;
        }

        let hasError = false;
        let errorMessages = [];

        $('input[name="quantite[]"]').each(function(index) {
            const qteInput = $(this);
            const qte = parseInt(qteInput.val());
            const row = qteInput.closest('tr');
            const select = row.find('.article-select');

            if (!select.val() || isNaN(qte)) return;

            const data = select.select2('data');
            const item = (data && data[0]) ? data[0] : null;
            const stock = item ? (parseFloat(item.qte_disponible) || 0) : 0;

            if (qte <= 0) {
                hasError = true;
                errorMessages.push(`Ligne ${index + 1}: la quantité doit être supérieure à 0.`);
                qteInput.addClass('is-invalid');
            } else if (qte > stock) {
                hasError = true;
                errorMessages.push(`Ligne ${index + 1}: quantité (${qte}) dépasse le stock (${stock}).`);
                qteInput.addClass('is-invalid');
            } else {
                qteInput.removeClass('is-invalid');
            }
        });

        if (hasError) {
            e.preventDefault();
            alert('Erreurs de validation:\n\n' + errorMessages.join('\n'));
            return false;
        }
        return true;
    });

    // Mise à jour stock en temps réel
    $(document).on('input', 'input[name="quantite[]"]', function() {
        updateStockDisplay($(this).closest('tr'));
    });
    $(document).on('select2:select', '.article-select', function() {
        updateStockDisplay($(this).closest('tr'));
    });
});

function addArticleLine() {
    articleLineCounter++;

    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required></select>
            </td>
            <td>
                <input type="number" class="form-control" name="quantite[]" min="1" step="1" required>
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
    initArticleSelect('#article_' + articleLineCounter);
}

function removeArticleLine(lineId) {
    if ($('#articlesBody tr').length > 1) {
        $('#articleLine' + lineId).remove();
    } else {
        alert('Vous devez avoir au moins un article.');
    }
}

function updateStockDisplay(row) {
    const select = row.find('.article-select');
    const quantiteInput = row.find('input[name="quantite[]"]');
    const badge = row.find('.stock-disponible');

    if (!select.val()) {
        badge.text('-').removeClass('bg-success bg-warning bg-danger').addClass('bg-info');
        return;
    }

    const data = select.select2('data');
    const item = (data && data[0]) ? data[0] : null;
    const stock = item ? (parseFloat(item.qte_disponible) || 0) : 0;
    const stockMin = item ? (parseFloat(item.stock_min) || 0) : 0;
    const qte = parseInt(quantiteInput.val()) || 0;
    const apresRetour = stock - qte;

    badge.removeClass('bg-success bg-warning bg-danger bg-info');
    quantiteInput.removeClass('is-invalid');

    if (qte > stock) {
        badge.addClass('bg-danger').text(stock.toLocaleString('fr-FR') + ' (Dépassement!)');
        quantiteInput.addClass('is-invalid');
    } else if (qte === stock) {
        badge.addClass('bg-warning').text(stock.toLocaleString('fr-FR') + ' (Tout le stock)');
    } else if (apresRetour < stockMin) {
        badge.addClass('bg-warning').text(stock.toLocaleString('fr-FR') + ' (Bas après retour)');
    } else {
        badge.addClass('bg-success').text(stock.toLocaleString('fr-FR'));
    }
}
</script>