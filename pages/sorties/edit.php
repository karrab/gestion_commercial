<?php
$page_title = 'Modifier sortie';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du sortie
$db->prepare("SELECT * FROM sorties WHERE id = :id");
$db->bind(':id', $id);
$sortie = $db->fetch();

if (!$sortie) {
    $_SESSION['error'] = 'Sortie introuvable.';
    header('Location: ' . BASE_URL . '/pages/sorties/view.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $employe_id = intval($_POST['employe_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Récupérer les articles du formulaire
    $articles = $_POST['article_id'] ?? [];
    $quantites = $_POST['quantite'] ?? [];
    $designations = $_POST['designation'] ?? [];

    $errors = [];

    if (empty($service_id)) {
        $errors[] = 'Le service est obligatoire.';
    }
    if (empty($employe_id)) {
        $errors[] = 'L\'employé est obligatoire.';
    }
    if (empty($date)) {
        $errors[] = 'La date est obligatoire.';
    }
    
    // Vérifier qu'il y a au moins un article
    $article_lines = [];
    $article_ids = [];
    foreach ($articles as $index => $article_id) {
        if (!empty($article_id) && !empty($quantites[$index]) && $quantites[$index] > 0) {
            $qte = floatval($quantites[$index]);
            $designation = $designations[$index] ?? '';
            
            // Vérifier les doublons
            if (in_array($article_id, $article_ids)) {
                $errors[] = 'L\'article "' . $designation . '" est en double.';
            } else {
                $article_ids[] = $article_id;
                $article_lines[] = [
                    'article_id' => intval($article_id),
                    'quantite' => $qte,
                    'designation' => $designation
                ];
            }
        }
    }
    
    if (empty($article_lines)) {
        $errors[] = 'Veuillez ajouter au moins un article.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Mettre à jour l'entête de la sortie
            $sql = "UPDATE sorties SET 
                    service_id = :service_id,
                    employe_id = :employe_id,
                    date = :date, 
                    notes = :notes, 
                    updated_at = NOW() 
                    WHERE id = :id";
            
            $db->prepare($sql);
            $db->bind(':service_id', $service_id);
            $db->bind(':employe_id', $employe_id);
            $db->bind(':date', $date);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);
            $db->execute();

            // 2. Récupérer les anciens articles pour restaurer le stock
            $db->prepare("SELECT ls.*, a.designation as article_designation, a.code_article as article_code, 
                                 a.qte_disponible as stock_disponible, a.qte_entree as stock_total
                          FROM ligne_sorties ls 
                          JOIN articles a ON ls.article_id = a.id 
                          WHERE ls.sortie_id = :sortie_id");
            $db->bind(':sortie_id', $id);
            $old_articles = $db->fetchAll();

            // 3. Préparer les données pour l'historique
            $old_articles_map = [];
            foreach ($old_articles as $old_article) {
                $old_articles_map[$old_article['article_id']] = $old_article;
            }

            // 4. Comparer les anciens et nouveaux articles pour générer l'historique
            $new_articles_map = [];
            foreach ($article_lines as $line) {
                $new_articles_map[$line['article_id']] = $line;
            }

            // Récupérer les informations de l'employé pour les commentaires
            $db->prepare("SELECT nom, prenom FROM employes WHERE id = :employe_id");
            $db->bind(':employe_id', $employe_id);
            $employe_info = $db->fetch();
            $employe_nom_complet = $employe_info['nom'] . ' ' . $employe_info['prenom'];

            // 5. Analyser les changements pour l'historique AVANT de modifier le stock
            $all_article_ids = array_unique(array_merge(array_keys($old_articles_map), array_keys($new_articles_map)));
            
            foreach ($all_article_ids as $article_id) {
                $old_qte = isset($old_articles_map[$article_id]) ? floatval($old_articles_map[$article_id]['qte_sortie']) : 0;
                $new_qte = isset($new_articles_map[$article_id]) ? floatval($new_articles_map[$article_id]['quantite']) : 0;
                
                // Récupérer les informations actuelles de l'article AVEC stock_initial
                $db->prepare("SELECT code_article, designation, qte_disponible, qte_entree, stock_min, stock_max, stock_initial 
                              FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $article_info = $db->fetch();
                
                if (!$article_info) continue;
                
                // qte_avant_operation = stock disponible actuel
                $qte_avant_operation = $article_info['qte_disponible'];
                $difference = $new_qte - $old_qte;
                
                // Calculer qte_apres_operation = stock disponible après modification
                $qte_apres_operation = $qte_avant_operation + $old_qte - $new_qte;
                
                // Si pas de changement, on passe
                if (abs($difference) < 0.001) continue;
                
                // Déterminer le type de changement
                $commentaire = '';
                $qte_historique = abs($difference);
                
               if ($old_qte == 0 && $new_qte > 0) {
    // Nouvelle ligne ajoutée
    $formatted_qte = rtrim(rtrim(number_format($qte_historique, 2, ',', ' '), '0'), ',');
    $commentaire = "Modification sortie employé: $employe_nom_complet ajout de ligne de " . $formatted_qte . " unité(s). ID_sortie: $id";
} elseif ($new_qte == 0 && $old_qte > 0) {
    // Ligne supprimée
    $formatted_qte = rtrim(rtrim(number_format($qte_historique, 2, ',', ' '), '0'), ',');
    $commentaire = "Modification sortie employé: $employe_nom_complet supprime ligne de " . $formatted_qte . " unité(s). ID_sortie: $id";
} elseif ($new_qte > $old_qte) {
    // Quantité augmentée
    $formatted_qte = rtrim(rtrim(number_format($qte_historique, 2, ',', ' '), '0'), ',');
    $commentaire = "Modification sortie employé: $employe_nom_complet augmenter de " . $formatted_qte . " unité(s). ID_sortie: $id";
} elseif ($new_qte < $old_qte) {
    // Quantité diminuée
    $formatted_qte = rtrim(rtrim(number_format($qte_historique, 2, ',', ' '), '0'), ',');
    $commentaire = "Modification sortie employé: $employe_nom_complet retrait de " . $formatted_qte . " unité(s). ID_sortie: $id";
}
                
                // Insérer dans l'historique AVEC stock_initial
                $sql_historique = "INSERT INTO historique_article 
                    (code_article, designation, operation, qte_sortie, qte, 
                     stock_avant_operation, stock_apres_operation, stock_initial, 
                     stock_min, stock_max, article_id, sortie_id, user_id, 
                     date_operation, commentaire, created_at)
                    VALUES 
                    (:code_article, :designation, 'sortie', :qte_sortie, :qte,
                     :stock_avant_operation, :stock_apres_operation, :stock_initial,
                     :stock_min, :stock_max, :article_id, :sortie_id, :user_id,
                     NOW(), :commentaire, NOW())";
                
                $db->prepare($sql_historique);
                $db->bind(':code_article', $article_info['code_article']);
                $db->bind(':designation', $article_info['designation']);
                $db->bind(':qte_sortie', $new_qte);
                $db->bind(':qte', $difference);
                $db->bind(':stock_avant_operation', $qte_avant_operation);
                $db->bind(':stock_apres_operation', $qte_apres_operation);
                $db->bind(':stock_initial', $article_info['stock_initial']);
                $db->bind(':stock_min', $article_info['stock_min']);
                $db->bind(':stock_max', $article_info['stock_max']);
                $db->bind(':article_id', $article_id);
                $db->bind(':sortie_id', $id);
                $db->bind(':user_id', $auth->getUserId());
                $db->bind(':commentaire', $commentaire);
                $db->execute();
            }

            // 6. Restaurer le stock pour les anciens articles
            foreach ($old_articles as $old_article) {
                $sql = "UPDATE articles 
                        SET qte_sortie = qte_sortie - ?,
                            qte_disponible = qte_disponible + ?
                        WHERE id = ?";
                $db->prepare($sql);
                $db->bind(1, $old_article['qte_sortie']);
                $db->bind(2, $old_article['qte_sortie']);
                $db->bind(3, $old_article['article_id']);
                $db->execute();
            }

            // 7. Supprimer les anciennes lignes
            $db->prepare("DELETE FROM ligne_sorties WHERE sortie_id = :sortie_id");
            $db->bind(':sortie_id', $id);
            $db->execute();

            // 8. Insérer les nouvelles lignes et déduire le stock
            foreach ($article_lines as $line) {
                $article_id = $line['article_id'];
                $qte = $line['quantite'];

                // Récupérer info article ET vérifier stock disponible
                $db->prepare("SELECT code_article, designation, qte_disponible FROM articles WHERE id = :id");
                $db->bind(':id', $article_id);
                $article = $db->fetch();

                if (!$article) {
                    throw new Exception('Article ID ' . $article_id . ' introuvable.');
                }

                // Vérifier stock disponible
                if ($article['qte_disponible'] < $qte) {
                    throw new Exception('Quantité indisponible en stock pour "' . $article['designation'] .
                                      '". Disponible: ' . number_format($article['qte_disponible'], 2, ',', ' ') .
                                      ' - Demandé: ' . number_format($qte, 2, ',', ' '));
                }

                // Insert ligne sortie
                $sql = "INSERT INTO ligne_sorties (sortie_id, article_id, code_article, designation, qte_sortie)
                        VALUES (:sortie_id, :article_id, :code, :designation, :qte)";

                $db->prepare($sql);
                $db->bind(':sortie_id', $id);
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
            }

            $auth->logTrace($auth->getUserId(), 'sorties', 'update', 'sorties', $id, "Modification sortie #$id avec articles");

            $db->commit();

            $_SESSION['success'] = 'Sortie modifiée avec succès.';
            header('Location: ' . BASE_URL . '/pages/sorties/view.php?id=' . $id);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    // Remplir les valeurs du formulaire
    $service_id = $sortie['service_id'] ?? '';
    $employe_id = $sortie['employe_id'] ?? '';
    $date = $sortie['date'] ?? '';
    $notes = $sortie['notes'] ?? '';
}

// Récupérer toutes les données nécessaires pour le formulaire
// Récupérer les services
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();

// Récupérer les employés (pour initialisation)
$employes_demandeur = [];
if ($service_id) {
    $db->prepare("SELECT id, nom, prenom FROM employes WHERE service_id = :service_id AND actif = 1 ORDER BY nom, prenom");
    $db->bind(':service_id', $service_id);
    $employes_demandeur = $db->fetchAll();
}

// Récupérer les articles actuels de la sortie pour pré-remplir le formulaire
$db->prepare("SELECT ls.*, a.designation as article_designation, a.code_article as article_code, a.qte_disponible as stock_disponible 
              FROM ligne_sorties ls 
              JOIN articles a ON ls.article_id = a.id 
              WHERE ls.sortie_id = :sortie_id");
$db->bind(':sortie_id', $id);
$articles_sortie = $db->fetchAll();

// Récupérer les informations du demandeur actuel
$db->prepare("SELECT s.nom as service_nom, e.nom as employe_nom, e.prenom as employe_prenom, e.matricule 
              FROM sorties so 
              LEFT JOIN services s ON so.service_id = s.id 
              LEFT JOIN employes e ON so.employe_id = e.id 
              WHERE so.id = :id");
$db->bind(':id', $id);
$demandeur_actuel = $db->fetch();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-box-arrow-up"></i> Modifier la sortie #<?php echo $sortie['id']; ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/sorties/index.php">Sorties</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-pencil"></i> Informations de la sortie
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/pages/sorties/index.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <button type="submit" form="editSortieForm" class="btn btn-primary btn-sm">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="editSortieForm">
                        <!-- Informations demandeur -->
                        <div class="card mb-3">
                            <div class="card-header"><i class="bi bi-person"></i> Demandeur</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="service_id" class="form-label required">Service</label>
                                        <select class="form-select" id="service_id" name="service_id" required>
                                            <option value="">Sélectionner...</option>
                                            <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['id']; ?>" 
                                                <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($service['nom']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="employe_id" class="form-label required">Employé</label>
                                        <select class="form-select" id="employe_id" name="employe_id" required>
                                            <option value="">Sélectionner...</option>
                                            <?php foreach ($employes_demandeur as $employe): ?>
                                            <option value="<?php echo $employe['id']; ?>" 
                                                <?php echo $employe_id == $employe['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($employe['nom'] . ' ' . $employe['prenom']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="date" class="form-label required">Date de sortie</label>
                                    <input type="date" class="form-control" id="date" name="date" required
                                           value="<?php echo htmlspecialchars($date); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Articles (modifiables) -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-box-seam"></i> Articles de la sortie</span>
                                <button type="button" class="btn btn-sm btn-success" onclick="addArticleLine()">
                                    <i class="bi bi-plus-circle"></i> Ajouter un article
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
                                        <tbody id="articlesBody">
                                            <?php foreach ($articles_sortie as $index => $article): ?>
                                            <tr id="articleLine<?php echo $index + 1; ?>">
                                                <td>
                                                    <input type="hidden" name="designation[]" class="article-designation" value="<?php echo htmlspecialchars($article['designation']); ?>">
                                                    <select class="form-select article-select" name="article_id[]" id="article_<?php echo $index + 1; ?>" required>
                                                        <option value="">Sélectionner...</option>
                                                        <option value="<?php echo $article['article_id']; ?>" selected>
                                                            <?php echo htmlspecialchars($article['article_code'] . ' - ' . $article['article_designation']); ?>
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
        <?php 
        $qte = $article['qte_sortie'];
        $formatted_qte = (intval($qte) == $qte) ? intval($qte) : rtrim(rtrim(number_format($qte, 2, '.', ''), '0'), '.');
        ?>
        <input type="number" class="form-control qte-input" name="quantite[]" 
               min="1" step="1" required 
               value="<?php echo $formatted_qte; ?>">
    </td>
    <td>
        <span class="stock-disponible badge bg-info">
            Stock: <?php echo number_format($article['stock_disponible'], 2, ',', ' '); ?>
        </span>
        <span class="stock-warning text-danger" style="display:none;">
            <i class="bi bi-exclamation-triangle"></i> Stock insuffisant
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(<?php echo $index + 1; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($articles_sortie)): ?>
                                            <tr id="articleLine1">
                                                <td>
                                                    <input type="hidden" name="designation[]" class="article-designation" value="">
                                                    <select class="form-select article-select" name="article_id[]" id="article_1" required>
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
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(1)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Attention</strong>
                                    <p class="mb-0 mt-2 small">
                                        La modification des articles entraînera un ajustement automatique du stock. 
                                        Les anciennes quantités seront restituées au stock et les nouvelles quantités seront déduites.
                                        Vérifiez que le stock est suffisant avant d'enregistrer.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="card mb-3">
                            <div class="card-header"><i class="bi bi-file-text"></i> Informations complémentaires</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo htmlspecialchars($notes); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Informations de la sortie
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $sortie['id']; ?></p>
                    <p><strong>Nombre d'articles:</strong> <?php echo count($articles_sortie); ?></p>
                    <p><strong>Créé par:</strong> <?php echo 'Utilisateur #' . $sortie['user_id']; ?></p>
                    <p><strong>Créé le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($sortie['created_at'])); ?></p>
                    <p><strong>Modifié le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($sortie['updated_at'])); ?></p>
                    <p><strong>Date de sortie:</strong><br><?php echo date('d/m/Y', strtotime($sortie['date'])); ?></p>
                    
                    <?php if (!empty($sortie['fichier'])): ?>
                    <p><strong>Fichier joint:</strong><br>
                        <a href="<?php echo UPLOAD_SORTIES_URL . '/' . $sortie['fichier']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> Voir le fichier
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informations du demandeur actuel -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-person-check"></i> Demandeur actuel
                </div>
                <div class="card-body">
                    <p><strong>Service:</strong><br><?php echo htmlspecialchars($demandeur_actuel['service_nom'] ?? 'Non spécifié'); ?></p>
                    <p><strong>Employé:</strong><br><?php echo htmlspecialchars(($demandeur_actuel['employe_nom'] ?? '') . ' ' . ($demandeur_actuel['employe_prenom'] ?? '')); ?></p>
                    <?php if (!empty($demandeur_actuel['matricule'])): ?>
                    <p><strong>Matricule:</strong><br><?php echo htmlspecialchars($demandeur_actuel['matricule']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Récapitulatif des articles actuels -->
            <?php if (!empty($articles_sortie)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-list-check"></i> Articles actuels
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th>Quantité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles_sortie as $article): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($article['article_code']); ?></td>
                                    <td><?php echo htmlspecialchars($article['article_designation']); ?></td>
                                    <td><?php echo number_format($article['qte_sortie'], 2, ',', ' '); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2"><strong>Total</strong></td>
                                    <td><strong><?php 
                                        $total = array_sum(array_column($articles_sortie, 'qte_sortie'));
                                        echo number_format($total, 2, ',', ' ');
                                    ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// Définir les variables globales pour JavaScript
const BASE_URL = '<?php echo BASE_URL; ?>';
let articleLineCounter = <?php echo count($articles_sortie) ?: 1; ?>;

$(document).ready(function() {
    // Initialiser Select2 pour les champs de base
    $('#service_id, #employe_id').select2({
        placeholder: 'Sélectionner...',
        allowClear: true,
        width: '100%'
    });

    // Initialiser Select2 AJAX pour toutes les lignes d'articles
    $('#articlesBody tr').each(function() {
        const selectId = '#' + $(this).find('select').attr('id');
        initArticleSelect(selectId);
        $(selectId).on('select2:select', function(e) {
            const data = e.params.data;
            const row = $(this).closest('tr');
            row.find('.stock-disponible').text('Stock: ' + formatNumber(data.qte_disponible));
            row.data('stock-disponible', data.qte_disponible);
            row.find('.article-designation').val(data.designation);
        });
    });

    // Charger employés quand service demandeur change
    $('#service_id').on('change', function() {
        let service_id = $(this).val();
        let $employeSelect = $('#employe_id');
        
        $employeSelect.html('<option value="">Sélectionner...</option>').val(null).trigger('change');

        if (service_id) {
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                data: { 
                    service_id: service_id, 
                    search: ''
                },
                dataType: 'json',
                beforeSend: function() {
                    $employeSelect.prop('disabled', true);
                },
                success: function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(employe) {
                            $employeSelect.append(new Option(employe.text, employe.id));
                        });
                    } else {
                        $employeSelect.append(new Option('Aucun employé dans ce service', ''));
                    }
                    $employeSelect.prop('disabled', false);
                    
                    // Pré-sélectionner l'employé actuel
                    $employeSelect.val('<?php echo $employe_id; ?>').trigger('change');
                },
                error: function(xhr, status, error) {
                    console.error('Error loading employes:', error);
                    $employeSelect.append(new Option('Erreur de chargement', ''));
                    $employeSelect.prop('disabled', false);
                }
            });
        }
    });

    // Vérifier le stock en temps réel
    $(document).on('input', '.qte-input', function() {
        const row = $(this).closest('tr');
        const stockText = row.find('.stock-disponible').text();
        const stock = parseFloat(stockText.replace('Stock:', '').trim().replace(',', '.')) || 0;
        const qte = parseFloat($(this).val()) || 0;

        if (qte > stock) {
            row.find('.stock-warning').show();
            $(this).addClass('is-invalid');
        } else {
            row.find('.stock-warning').hide();
            $(this).removeClass('is-invalid');
        }
    });

    // Si le service demandeur est déjà sélectionné, charger ses employés
    <?php if ($service_id): ?>
    $('#service_id').trigger('change');
    <?php endif; ?>
});

function addArticleLine() {
    articleLineCounter++;
    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <input type="hidden" name="designation[]" class="article-designation" value="">
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
        row.find('.stock-disponible').text('Stock: ' + formatNumber(data.qte_disponible));
        row.data('stock-disponible', data.qte_disponible);
        row.find('.article-designation').val(data.designation);
    });
}

function removeArticleLine(lineId) {
    const totalLines = $('#articlesBody tr').length;
    if (totalLines > 1) {
        $('#articleLine' + lineId).remove();
    } else {
        alert('Vous devez avoir au moins un article.');
    }
}

function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    
    // Convertir en nombre
    const number = parseFloat(num);
    
    // Si c'est un nombre entier, retourner sans décimales
    if (Number.isInteger(number)) {
        return number.toString();
    }
    
    // Sinon, formater avec 2 décimales et supprimer les zéros inutiles
    return number.toFixed(2).replace(/(\.0+|0+)$/, '');
}
</script>