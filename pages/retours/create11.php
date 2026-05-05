<?php
$page_title = 'Nouveau retour';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('retours', 'create');
$db = Database::getInstance();

// Récupérer les services et employés pour les select
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();

$db->prepare("SELECT id, nom, prenom, service_id FROM employes WHERE actif = 1 ORDER BY nom, prenom");
$employes = $db->fetchAll();

// Grouper les employés par service
$employesParService = [];
foreach ($employes as $employe) {
    $employesParService[$employe['service_id']][] = $employe;
}

// Récupérer les articles
$db->prepare("SELECT id, code_article, designation FROM articles WHERE actif = 1 ORDER BY designation");
$articles = $db->fetchAll();

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
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $filename = $_FILES['fichier']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= 5242880) {
            $newname = uniqid() . '_' . time() . '.' . $ext;
            $upload_path = __DIR__ . '/../../../uploads/retours';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_path . '/' . $newname)) {
                $fichier = $newname;
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
            if (!empty($fichier) && file_exists(__DIR__ . '/../../../uploads/retours/' . $fichier)) {
                unlink(__DIR__ . '/../../../uploads/retours/' . $fichier);
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
                                <select class="form-select" id="service_id" name="service_id" required onchange="loadEmployesByService()">
                                    <option value="">Sélectionner un service...</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>">
                                            <?php echo htmlspecialchars($service['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_id" class="form-label required">Employé</label>
                                <select class="form-select" id="employe_id" name="employe_id" required>
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
            </div>
        </div>
    </form>
</div>

<script>
let articleLineCounter = 0;
const employesParService = <?php echo json_encode($employesParService); ?>;

$(document).ready(function() {
    addArticleLine();
});

function loadEmployesByService() {
    const serviceId = $('#service_id').val();
    const employeSelect = $('#employe_id');
    
    if (!serviceId) {
        employeSelect.html('<option value="">Sélectionnez d\'abord un service</option>');
        return;
    }
    
    employeSelect.html('<option value="">Sélectionner un employé...</option>');
    
    if (employesParService[serviceId]) {
        employesParService[serviceId].forEach(function(employe) {
            employeSelect.append(new Option(
                employe.nom + ' ' + employe.prenom,
                employe.id
            ));
        });
    } else {
        employeSelect.html('<option value="">Aucun employé trouvé pour ce service</option>');
    }
}

function addArticleLine() {
    articleLineCounter++;
    const row = `
        <tr id="articleLine${articleLineCounter}">
            <td>
                <select class="form-select" name="article_id[]" required>
                    <option value="">Sélectionner...</option>
                    <?php foreach ($articles as $article): ?>
                    <option value="<?php echo $article['id']; ?>">
                        <?php echo htmlspecialchars($article['code_article'] . ' - ' . $article['designation']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="number" class="form-control" name="quantite[]" min="0.01" step="0.01" required>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeArticleLine(${articleLineCounter})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#articlesBody').append(row);
}

function removeArticleLine(lineId) {
    if ($('#articlesBody tr').length > 1) {
        $(`#articleLine${lineId}`).remove();
    } else {
        alert('Vous devez avoir au moins un article.');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>