<?php
$page_title = 'Modifier retour fournisseur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retour_fournisseur', 'update');
$db = Database::getInstance();
$retourFournisseur = new RetourFournisseur();

$id = $_GET['id'] ?? 0;
$retour = $retourFournisseur->getById($id);

if (!$retour) {
    $_SESSION['error'] = 'Retour fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
    exit;
}

// Récupérer la liste des fournisseurs
$db->prepare("SELECT id, nom_complet FROM fournisseurs WHERE actif = 1 ORDER BY nom_complet");
$fournisseurs = $db->fetchAll();

// Récupérer la liste de tous les articles actifs (pour le select)
$db->prepare("SELECT id, code_article, designation, qte_disponible FROM articles WHERE actif = 1 ORDER BY designation");
$all_articles = $db->fetchAll();

// Récupérer les lignes de retour existantes avec les informations détaillées des articles
$db->prepare("
    SELECT lrf.*, a.code_article, a.designation, a.qte_disponible 
    FROM ligne_retour_fournisseur lrf 
    JOIN articles a ON lrf.article_id = a.id 
    WHERE lrf.retour_fournisseur_id = ?
");
$db->bind(1, $id);
$lignes_existantes = $db->fetchAll();

// Fonction pour formater les nombres (éliminer les virgules et zéros inutiles)
function formatNumber($number) {
    if (is_numeric($number)) {
        // Convertir en float et formater
        $floatVal = floatval($number);
        // Supprimer les zéros décimaux inutiles
        if ($floatVal == intval($floatVal)) {
            return intval($floatVal);
        }
        // Pour les nombres décimaux, garder 2 décimales maximum
        return rtrim(rtrim(number_format($floatVal, 2, '.', ''), '0'), '.');
    }
    return $number;
}

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
    $fichier = $retour['fichier'];
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $allowed = ALLOWED_FILE_TYPES;
        $filename = $_FILES['fichier']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= MAX_FILE_SIZE) {
            $newname = uniqid() . '_' . time() . '.' . $ext;
            $upload_path = UPLOAD_PATH . '/retour_fournisseur';
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }

            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_path . '/' . $newname)) {
                // Supprimer l'ancien fichier
                if (!empty($retour['fichier']) && file_exists($upload_path . '/' . $retour['fichier'])) {
                    unlink($upload_path . '/' . $retour['fichier']);
                }
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
            $db->beginTransaction();

            $user_id = $auth->getUserId();

            // Mettre à jour le retour avec paramètres de position
            $sql = "UPDATE retour_fournisseur 
                    SET fournisseur_id = ?, 
                        date = ?, 
                        fichier = ?, 
                        notes = ?,
                        updated_at = NOW()
                    WHERE id = ?";

            $db->prepare($sql);
            $db->bind(1, $fournisseur_id);
            $db->bind(2, $date);
            $db->bind(3, $fichier);
            $db->bind(4, $notes);
            $db->bind(5, $id);
            $db->execute();

            // Créer un tableau des anciennes lignes par article_id pour comparaison
            $anciennes_lignes = [];
            foreach ($lignes_existantes as $ligne) {
                $anciennes_lignes[$ligne['article_id']] = $ligne;
            }

            // Tableau pour suivre les articles traités
            $articles_traites = [];

            // Traiter chaque article du formulaire
            foreach ($articles_post as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $article_id = intval($article_id);
                $nouvelle_qte = floatval($quantites[$index]);
                $articles_traites[] = $article_id;

                // Récupérer les infos de l'article
                $db->prepare("SELECT code_article, designation, qte_disponible, stock_initial, stock_min, stock_max FROM articles WHERE id = ?");
                $db->bind(1, $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                $stock_avant = $article['qte_disponible'];

                // CAS 1: Article existant déjà dans le retour (modification de quantité)
                if (isset($anciennes_lignes[$article_id])) {
                    $ancienne_ligne = $anciennes_lignes[$article_id];
                    $ancienne_qte = floatval($ancienne_ligne['qte']);

                    // Si la quantité a changé
                    if ($nouvelle_qte != $ancienne_qte) {
                        $difference = $nouvelle_qte - $ancienne_qte;

                        // CAS 1A: Quantité augmentée - DIMINUTION DU STOCK
                        if ($difference > 0) {
                            // Vérifier si le stock est suffisant pour l'augmentation
                            if ($stock_avant < $difference) {
                                throw new Exception("Stock insuffisant pour augmenter le retour de l'article " . $article['code_article'] .
                                    ". Stock disponible: $stock_avant, Quantité à ajouter: $difference");
                            }

                            // Mettre à jour le stock (DIMINUER car c'est un retour fournisseur)
                            $sql_update = "UPDATE articles
                                        SET qte_retour = qte_retour + ?,
                                            qte_disponible = qte_disponible - ?
                                        WHERE id = ?";

                            $db->prepare($sql_update);
                            $db->bind(1, $difference);
                            $db->bind(2, $difference);
                            $db->bind(3, $article_id);
                            $db->execute();

                            $stock_apres = $stock_avant - $difference;

                            // Enregistrer dans l'historique pour augmentation de retour
                            $sql_hist = "INSERT INTO historique_article 
                                        (code_article, designation, operation, qte, stock_avant_operation, stock_apres_operation, 
                                         stock_initial, stock_min, stock_max, article_id, retour_fournisseur_id, user_id, 
                                         date_operation, commentaire, created_at)
                                         VALUES (?, ?, 'retour_fournisseur', ?, ?, ?, 
                                                 ?, ?, ?, 
                                                 ?, ?, ?, NOW(), ?, NOW())";

                            $db->prepare($sql_hist);
                            $db->bind(1, $article['code_article']);
                            $db->bind(2, $article['designation']);
                            $db->bind(3, $difference);
                            $db->bind(4, $stock_avant);
                            $db->bind(5, $stock_apres);
                            $db->bind(6, $article['stock_initial']);
                            $db->bind(7, $article['stock_min']);
                            $db->bind(8, $article['stock_max']);
                            $db->bind(9, $article_id);
                            $db->bind(10, $id);
                            $db->bind(11, $user_id);
                            $db->bind(12, "Modification retour fournisseur $id - augmentation de retour de " . formatNumber($difference) . " unité(s) pour l'article " . $article['code_article']);
                            $db->execute();

                            // CAS 1B: Quantité diminuée - AUGMENTATION DU STOCK (car on retourne moins)
                        } else if ($difference < 0) {
                            $difference_abs = abs($difference);

                            // Mettre à jour le stock (AUGMENTER car on retourne moins)
                            $sql_update = "UPDATE articles
                                        SET qte_retour = qte_retour - ?,
                                            qte_disponible = qte_disponible + ?
                                        WHERE id = ?";

                            $db->prepare($sql_update);
                            $db->bind(1, $difference_abs);
                            $db->bind(2, $difference_abs);
                            $db->bind(3, $article_id);
                            $db->execute();

                            $stock_apres = $stock_avant + $difference_abs;

                            // Enregistrer dans l'historique comme 'retour_fournisseur' avec quantité négative
                            $sql_hist = "INSERT INTO historique_article 
                                        (code_article, designation, operation, qte, stock_avant_operation, stock_apres_operation, 
                                         stock_initial, stock_min, stock_max, article_id, retour_fournisseur_id, user_id, 
                                         date_operation, commentaire, created_at)
                                         VALUES (?, ?, 'retour_fournisseur', ?, ?, ?, 
                                                 ?, ?, ?, 
                                                 ?, ?, ?, NOW(), ?, NOW())";

                            $db->prepare($sql_hist);
                            $db->bind(1, $article['code_article']);
                            $db->bind(2, $article['designation']);
                            $db->bind(3, -$difference_abs); // Négatif pour diminution de retour
                            $db->bind(4, $stock_avant);
                            $db->bind(5, $stock_apres);
                            $db->bind(6, $article['stock_initial']);
                            $db->bind(7, $article['stock_min']);
                            $db->bind(8, $article['stock_max']);
                            $db->bind(9, $article_id);
                            $db->bind(10, $id);
                            $db->bind(11, $user_id);
                            $db->bind(12, "Modification retour fournisseur $id - diminution de retour de " . formatNumber($difference_abs) . " unité(s) pour l'article " . $article['code_article']);
                            $db->execute();
                        }
                    }
                    // Si quantité inchangée, rien à faire pour l'historique

                    // CAS 2: Nouvel article ajouté au retour - DIMINUTION DU STOCK
                } else {
                    // Vérifier si le stock est suffisant pour le nouveau retour
                    if ($stock_avant < $nouvelle_qte) {
                        throw new Exception("Stock insuffisant pour retourner l'article " . $article['code_article'] .
                            ". Stock disponible: $stock_avant, Quantité à retourner: $nouvelle_qte");
                    }

                    // Mettre à jour le stock (DIMINUER car c'est un nouveau retour)
                    $sql_update = "UPDATE articles
                                SET qte_retour = qte_retour + ?,
                                    qte_disponible = qte_disponible - ?
                                WHERE id = ?";

                    $db->prepare($sql_update);
                    $db->bind(1, $nouvelle_qte);
                    $db->bind(2, $nouvelle_qte);
                    $db->bind(3, $article_id);
                    $db->execute();

                    $stock_apres = $stock_avant - $nouvelle_qte;

                    // Enregistrer dans l'historique pour nouvel article retourné
                    $sql_hist = "INSERT INTO historique_article 
                                (code_article, designation, operation, qte, stock_avant_operation, stock_apres_operation, 
                                 stock_initial, stock_min, stock_max, article_id, retour_fournisseur_id, user_id, 
                                 date_operation, commentaire, created_at)
                                 VALUES (?, ?, 'retour_fournisseur', ?, ?, ?, 
                                         ?, ?, ?, 
                                         ?, ?, ?, NOW(), ?, NOW())";

                    $db->prepare($sql_hist);
                    $db->bind(1, $article['code_article']);
                    $db->bind(2, $article['designation']);
                    $db->bind(3, $nouvelle_qte);
                    $db->bind(4, $stock_avant);
                    $db->bind(5, $stock_apres);
                    $db->bind(6, $article['stock_initial']);
                    $db->bind(7, $article['stock_min']);
                    $db->bind(8, $article['stock_max']);
                    $db->bind(9, $article_id);
                    $db->bind(10, $id);
                    $db->bind(11, $user_id);
                    $db->bind(12, "Modification retour fournisseur $id - nouveau retour de " . formatNumber($nouvelle_qte) . " unité(s) pour l'article " . $article['code_article']);
                    $db->execute();
                }
            }

            // CAS 3: Articles supprimés du retour (présents dans anciennes lignes mais pas dans nouvelles)
            foreach ($anciennes_lignes as $article_id => $ancienne_ligne) {
                if (!in_array($article_id, $articles_traites)) {
                    $ancienne_qte = floatval($ancienne_ligne['qte']);

                    // Récupérer les infos actuelles de l'article
                    $db->prepare("SELECT qte_disponible, stock_initial, stock_min, stock_max FROM articles WHERE id = ?");
                    $db->bind(1, $article_id);
                    $article_info = $db->fetch();

                    $stock_avant = $article_info['qte_disponible'];
                    $stock_initial = $article_info['stock_initial'];
                    $stock_min = $article_info['stock_min'];
                    $stock_max = $article_info['stock_max'];

                    // Mettre à jour le stock (AUGMENTER car on annule le retour)
                    $sql_update = "UPDATE articles 
                                SET qte_retour = qte_retour - ?,
                                    qte_disponible = qte_disponible + ?
                                WHERE id = ?";

                    $db->prepare($sql_update);
                    $db->bind(1, $ancienne_qte);
                    $db->bind(2, $ancienne_qte);
                    $db->bind(3, $article_id);
                    $db->execute();

                    $stock_apres = $stock_avant + $ancienne_qte;

                    // Enregistrer dans l'historique comme 'retour_fournisseur' avec quantité négative
                    $sql_hist = "INSERT INTO historique_article 
                                (code_article, designation, operation, qte, stock_avant_operation, stock_apres_operation, 
                                 stock_initial, stock_min, stock_max, article_id, retour_fournisseur_id, user_id, 
                                 date_operation, commentaire, created_at)
                                 VALUES (?, ?, 'retour_fournisseur', ?, ?, ?, 
                                         ?, ?, ?, 
                                         ?, ?, ?, NOW(), ?, NOW())";

                    $db->prepare($sql_hist);
                    $db->bind(1, $ancienne_ligne['code_article']);
                    $db->bind(2, $ancienne_ligne['designation']);
                    $db->bind(3, -$ancienne_qte); // Négatif pour annulation de retour
                    $db->bind(4, $stock_avant);
                    $db->bind(5, $stock_apres);
                    $db->bind(6, $stock_initial);
                    $db->bind(7, $stock_min);
                    $db->bind(8, $stock_max);
                    $db->bind(9, $article_id);
                    $db->bind(10, $id); // Garder le retour_fournisseur_id pour référence
                    $db->bind(11, $user_id);
                    $db->bind(12, "Modification retour fournisseur $id - suppression du retour de l'article " . $ancienne_ligne['code_article'] . " (" . formatNumber($ancienne_qte) . " unité(s))");
                    $db->execute();
                }
            }

            // Supprimer les anciennes lignes
            $db->prepare("DELETE FROM ligne_retour_fournisseur WHERE retour_fournisseur_id = ?");
            $db->bind(1, $id);
            $db->execute();

            // Ajouter les nouvelles lignes
            foreach ($articles_post as $index => $article_id) {
                if (empty($article_id) || empty($quantites[$index]) || $quantites[$index] <= 0) {
                    continue;
                }

                $qte = floatval($quantites[$index]);

                // Récupérer les infos de l'article
                $db->prepare("SELECT code_article, designation FROM articles WHERE id = ?");
                $db->bind(1, $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // Insert ligne avec paramètres de position
                $sql = "INSERT INTO ligne_retour_fournisseur (retour_fournisseur_id, article_id, code_article, designation, qte)
                        VALUES (?, ?, ?, ?, ?)";

                $db->prepare($sql);
                $db->bind(1, $id);
                $db->bind(2, $article_id);
                $db->bind(3, $article['code_article']);
                $db->bind(4, $article['designation']);
                $db->bind(5, $qte);
                $db->execute();
            }

            // Log de la trace
            $auth->logTrace($auth->getUserId(), 'retour_fournisseur', 'update', 'retour_fournisseur', $id, "Modification retour fournisseur avec historique des articles");

            $db->commit();

            $_SESSION['success'] = 'Retour fournisseur modifié avec succès. L\'historique des articles a été mis à jour.';
            header('Location: ' . BASE_URL . '/pages/retour_fournisseur/view.php?id=' . $id);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    $fournisseur_id = $retour['fournisseur_id'];
    $date = $retour['date'];
    $notes = $retour['notes'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <!-- jQuery et Select2 chargés via footer.php -->

    <div class="container-fluid main-container">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="bi bi-pencil"></i> Modifier retour fournisseur N°:<?php echo $id; ?></h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php">Retours Fournisseur</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
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
                                    <select class="form-select select2" id="fournisseur_id" name="fournisseur_id" required>
                                        <option value="">Sélectionner un fournisseur...</option>
                                        <?php foreach ($fournisseurs as $fournisseur): ?>
                                            <option value="<?php echo $fournisseur['id']; ?>" <?php echo $fournisseur_id == $fournisseur['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($fournisseur['nom_complet']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="date" class="form-label required">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" required
                                           value="<?php echo htmlspecialchars($date); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="fichier" class="form-label">Fichier joint</label>
                                <input type="file" class="form-control" id="fichier" name="fichier"
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="form-text text-muted">
                                    Formats acceptés: PDF, DOC, DOCX, JPG, PNG. Taille max: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?> MB
                                    <?php if (!empty($retour['fichier'])): ?>
                                        <br>Fichier actuel: <a href="<?php echo UPLOAD_URL; ?>/retour_fournisseur/<?php echo $retour['fichier']; ?>" target="_blank">Télécharger</a>
                                    <?php endif; ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes / Motif du retour</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
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
                                    <?php if (count($lignes_existantes) > 0): ?>
                                        <?php foreach ($lignes_existantes as $index => $ligne): ?>
                                            <tr id="articleLine<?php echo $index + 1; ?>">
                                                <td>
                                                    <select class="form-select " name="article_id[]" id="article_<?php echo $index + 1; ?>" required>
                                                        <option value="">Sélectionner un article...</option>
                                                        <?php foreach ($all_articles as $article): ?>
                                                            <option value="<?php echo $article['id']; ?>"
                                                                    data-qte_disponible="<?php echo formatNumber($article['qte_disponible']); ?>"
                                                                <?php echo $ligne['article_id'] == $article['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($article['code_article'] . ' - ' . $article['designation']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control" name="quantite[]" min="0.01" step="0.01" required
                                                           value="<?php echo formatNumber($ligne['qte']); ?>">
                                                </td>
                                                <td>
                                                    <span class="stock-disponible badge bg-info">
                                                        <?php echo formatNumber($ligne['qte_disponible']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(<?php echo $index + 1; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Aucune ligne existante, afficher une ligne vide -->
                                        <tr id="articleLine1">
                                            <td>
                                                <select class="form-select article-select" name="article_id[]" id="article_1" required>
                                                    <option value="">Sélectionner un article...</option>
                                                    <?php foreach ($all_articles as $article): ?>
                                                        <option value="<?php echo $article['id']; ?>" data-qte_disponible="<?php echo formatNumber($article['qte_disponible']); ?>">
                                                            <?php echo htmlspecialchars($article['code_article'] . ' - ' . $article['designation']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="quantite[]" min="0.01" step="0.01" required>
                                            </td>
                                            <td>
                                                <span class="stock-disponible badge bg-info">-</span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(1)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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
                                    <i class="bi bi-save"></i> Enregistrer les modifications
                                </button>
                                <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour
                                </a>
                            </div>

                            <hr>

                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Attention</strong>
                                <p class="mb-0 mt-2 small">
                                    Le retour fournisseur <strong>diminue</strong> le stock des articles.<br>
                                    L'historique des articles sera mis à jour avec les modifications apportées.
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

    <script>
        // Vérifier si jQuery est chargé
        if (typeof jQuery === 'undefined') {
            console.error('jQuery n\'est pas chargé!');
            // Charger jQuery dynamiquement
            var script = document.createElement('script');
            script.src = '<?php echo BASE_URL; ?>/assets/js/jquery.min.js';
            script.onload = initApplication;
            document.head.appendChild(script);
        } else {
            $(document).ready(initApplication);
        }

        // Fonction pour formater les nombres (éliminer les virgules et zéros inutiles)
        function formatNumber(num) {
            if (isNaN(num)) return '0';
            // Convertir en float
            const floatVal = parseFloat(num);
            // Supprimer les zéros décimaux inutiles
            if (floatVal === parseInt(floatVal)) {
                return parseInt(floatVal).toString();
            }
            // Pour les nombres décimaux, garder 2 décimales maximum
            return floatVal.toFixed(2).replace(/\.?0+$/, '');
        }

        // Fonction pour formater les nombres sans virgule ni zéros décimaux (pour l'affichage uniquement)
        function formatDisplayNumber(num) {
            if (isNaN(num)) return '0';
            // Convertir en float et supprimer les décimales si c'est un entier
            const floatVal = parseFloat(num);
            if (floatVal === parseInt(floatVal)) {
                return parseInt(floatVal).toString();
            }
            // Pour les nombres décimaux, garder 2 décimales maximum et supprimer les zéros inutiles
            return floatVal.toFixed(2).replace(/\.?0+$/, '');
        }

        function initApplication() {
            console.log('jQuery chargé, initialisation de l\'application...');

            let articleLineCounter = <?php echo count($lignes_existantes) > 0 ? count($lignes_existantes) : 1; ?>;

            // Fonction pour formater la date
            function formatDate(dateStr) {
                try {
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('fr-FR', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                } catch(e) {
                    return dateStr;
                }
            }

            // Charger la dernière entrée du fournisseur ET les ajouter au tableau
            function loadLastEntree(fournisseurId) {
                if (!fournisseurId || fournisseurId === '' || fournisseurId === '0') {
                    $('#lastEntreeCard').hide();
                    return;
                }

                $('#lastEntreeContent').html(`
            <div class="text-center py-3">
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
                        if (response.success && response.entree && response.articles && response.articles.length > 0) {
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
                                // Utiliser formatDisplayNumber pour les quantités (éliminer virgule et zéros)
                                html += `<td class="text-end"><span class="badge bg-success">${formatDisplayNumber(article.qte_entree)}</span></td>`;
                                html += `<td class="text-end"><span class="badge bg-info">${formatDisplayNumber(article.stock_actuel)}</span></td>`;
                                html += '</tr>';

                                // Ajouter cet article comme option dans tous les select d'articles
                                addArticleToAllSelects(article);
                            });

                            html += '</tbody>';
                            html += '</table>';
                            html += '</div>';
                            html += '<p class="mt-2 mb-0"><small class="text-muted"><i class="bi bi-info-circle"></i> Articles de la dernière entrée (disponibles dans la liste des articles)</small></p>';

                            $('#lastEntreeContent').html(html);
                        } else {
                            $('#lastEntreeContent').html(`
                        <p class="text-muted text-center mb-0 py-3">
                            <i class="bi bi-inbox"></i><br>
                            Aucune entrée trouvée pour ce fournisseur
                        </p>
                    `);
                        }
                    },
                    error: function() {
                        $('#lastEntreeContent').html(`
                    <p class="text-danger text-center mb-0 py-3">
                        <i class="bi bi-exclamation-triangle"></i><br>
                        Erreur lors du chargement des données
                    </p>
                `);
                    }
                });
            }

            // Fonction pour ajouter un article à tous les selects d'articles
            function addArticleToAllSelects(article) {
                // Vérifier si l'article existe déjà dans les options
                $('.article-select').each(function() {
                    const select = $(this);
                    const articleId = article.id;

                    // Vérifier si l'article existe déjà
                    if (select.find(`option[value="${articleId}"]`).length === 0) {
                        // Ajouter l'article comme option
                        const option = new Option(
                            `${article.code_article} - ${article.designation} (Stock: ${formatDisplayNumber(article.stock_actuel)})`,
                            articleId,
                            false,
                            false
                        );
                        option.setAttribute('data-qte_disponible', article.stock_actuel);
                        select.append(option);

                        // Mettre à jour Select2 si nécessaire
                        if (select.hasClass('select2-hidden-accessible')) {
                            select.trigger('change');
                        }
                    }
                });
            }

            // Initialiser Select2 pour le fournisseur
            $('#fournisseur_id').select2({
                width: '100%'
            });

            // Initialiser Select2 pour tous les selects d'articles existants
            $('.article-select').each(function() {
                $(this).select2({
                    width: '100%'
                });

                // Événement lors de la sélection d'un article
                $(this).on('select2:select', function(e) {
                    const selectedOption = $(this).find('option:selected');
                    const stock = selectedOption.data('qte_disponible');
                    $(this).closest('tr').find('.stock-disponible').text(formatDisplayNumber(stock));
                });
            });

            // Charger la dernière entrée au démarrage si un fournisseur est sélectionné
            const fournisseurId = $('#fournisseur_id').val();

            // Attendre un peu pour être sûr que tout est chargé
            setTimeout(function() {
                if (fournisseurId && fournisseurId !== '' && fournisseurId !== '0') {
                    loadLastEntree(fournisseurId);
                }
            }, 300);

            // Événement lors du changement de fournisseur
            $('#fournisseur_id').on('change', function() {
                const newFournisseurId = $(this).val();
                loadLastEntree(newFournisseurId);
            });

            // Fonction pour ajouter une ligne d'article
            window.addArticleLine = function() {
                articleLineCounter++;

                const row = `
            <tr id="articleLine${articleLineCounter}">
                <td>
                    <select class="form-select article-select" name="article_id[]" id="article_${articleLineCounter}" required>
                        <option value="">Sélectionner un article...</option>
                        <?php foreach ($all_articles as $article): ?>
                            <option value="<?php echo $article['id']; ?>" data-qte_disponible="<?php echo formatNumber($article['qte_disponible']); ?>">
                                <?php echo htmlspecialchars($article['code_article'] . ' - ' . $article['designation']); ?>
                            </option>
                        <?php endforeach; ?>
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
                const newSelect = $(`#article_${articleLineCounter}`);
                newSelect.select2({
                    width: '100%'
                });

                // Événement lors de la sélection d'un article
                newSelect.on('select2:select', function(e) {
                    const selectedOption = $(this).find('option:selected');
                    const stock = selectedOption.data('qte_disponible');
                    $(this).closest('tr').find('.stock-disponible').text(formatDisplayNumber(stock));
                });

                // Ajouter les articles chargés précédemment au nouveau select
                $('.article-select').not(`#article_${articleLineCounter}`).each(function() {
                    const existingSelect = $(this);
                    existingSelect.find('option').each(function() {
                        const option = $(this);
                        const value = option.val();
                        const text = option.text();
                        const qteDisponible = option.data('qte_disponible');

                        // Ne pas ajouter l'option vide ni les doublons
                        if (value && value !== '' && !newSelect.find(`option[value="${value}"]`).length) {
                            const newOption = new Option(text, value, false, false);
                            if (qteDisponible) {
                                newOption.setAttribute('data-qte_disponible', qteDisponible);
                            }
                            newSelect.append(newOption);
                        }
                    });
                });
            };

            // Fonction pour supprimer une ligne d'article
            window.removeArticleLine = function(lineId) {
                if ($('#articlesBody tr').length > 1) {
                    $(`#articleLine${lineId}`).remove();
                } else {
                    alert('Vous devez avoir au moins un article.');
                }
            };

            console.log('Application initialisée avec succès');
        }
    </script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
