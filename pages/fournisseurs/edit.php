<?php
$page_title = 'Modifier fournisseur';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('fournisseurs', 'update');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du fournisseur
$db->prepare("SELECT * FROM fournisseurs WHERE id = :id");
$db->bind(':id', $id);
$fournisseur = $db->fetch();

if (!$fournisseur) {
    $_SESSION['error'] = 'Fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/fournisseurs/index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $matricule_fiscal = trim($_POST['matricule_fiscal'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $pays = trim($_POST['pays'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $tel1 = trim($_POST['tel1'] ?? '');
    $tel2 = trim($_POST['tel2'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    // Validation
    $errors = [];
    if (empty($nom_complet)) {
        $errors[] = 'Le nom complet est obligatoire.';
    }

    // Vérifier l'unicité du nom (sauf pour le fournisseur actuel)
    $db->prepare("SELECT COUNT(*) as count FROM fournisseurs WHERE nom_complet = :nom AND id != :id");
    $db->bind(':nom', $nom_complet);
    $db->bind(':id', $id);
    $result = $db->fetch();
    if ($result['count'] > 0) {
        $errors[] = 'Un fournisseur avec ce nom existe déjà.';
    }

    // Vérifier l'unicité du matricule fiscal (s'il est renseigné et différent de l'original)
    if (!empty($matricule_fiscal)) {
        $db->prepare("SELECT COUNT(*) as count FROM fournisseurs WHERE matricule_fiscal = :matricule_fiscal AND id != :id");
        $db->bind(':matricule_fiscal', $matricule_fiscal);
        $db->bind(':id', $id);
        $result = $db->fetch();
        if ($result['count'] > 0) {
            $errors[] = 'Ce matricule fiscal est déjà utilisé par un autre fournisseur.';
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE fournisseurs 
                    SET nom_complet = :nom_complet, 
                        matricule_fiscal = :matricule_fiscal, 
                        adresse = :adresse, 
                        ville = :ville, 
                        pays = :pays, 
                        code_postal = :code_postal, 
                        tel1 = :tel1, 
                        tel2 = :tel2, 
                        notes = :notes, 
                        actif = :actif,
                        updated_at = NOW() 
                    WHERE id = :id";
            
            $db->prepare($sql);
            $db->bind(':nom_complet', $nom_complet);
            $db->bind(':matricule_fiscal', $matricule_fiscal);
            $db->bind(':adresse', $adresse);
            $db->bind(':ville', $ville);
            $db->bind(':pays', $pays);
            $db->bind(':code_postal', $code_postal);
            $db->bind(':tel1', $tel1);
            $db->bind(':tel2', $tel2);
            $db->bind(':notes', $notes);
            $db->bind(':actif', $actif, PDO::PARAM_INT);
            $db->bind(':id', $id, PDO::PARAM_INT);

            if ($db->execute()) {
                // Log de la trace
                $auth->logTrace($auth->getUserId(), 'fournisseurs', 'update', 'fournisseurs', $id, "Modification: $nom_complet");

                $_SESSION['success'] = 'Fournisseur modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/fournisseurs/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    // Pré-remplissage du formulaire avec les données existantes
    $nom_complet = $fournisseur['nom_complet'];
    $matricule_fiscal = $fournisseur['matricule_fiscal'] ?? '';
    $adresse = $fournisseur['adresse'] ?? '';
    $ville = $fournisseur['ville'] ?? '';
    $pays = $fournisseur['pays'] ?? '';
    $code_postal = $fournisseur['code_postal'] ?? '';
    $tel1 = $fournisseur['tel1'] ?? '';
    $tel2 = $fournisseur['tel2'] ?? '';
    $notes = $fournisseur['notes'] ?? '';
    $actif = $fournisseur['actif'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-truck"></i> Modifier le fournisseur</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php">Fournisseurs</a></li>
                    <li class="breadcrumb-item active">Modifier : <?php echo htmlspecialchars($nom_complet); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil"></i> Informations du fournisseur
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Erreurs de validation :</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nom_complet" class="form-label required">Nom complet / Raison sociale</label>
                                <input type="text" class="form-control" id="nom_complet" name="nom_complet" required
                                       value="<?php echo htmlspecialchars($nom_complet); ?>"
                                       placeholder="Ex: EURL TechSupply">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="actif" class="form-label">Statut</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="actif" name="actif" value="1" <?php echo $actif ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="actif">
                                        <?php echo $actif ? 'Actif' : 'Inactif'; ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="matricule_fiscal" class="form-label">Matricule Fiscal</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" class="form-control" id="matricule_fiscal" name="matricule_fiscal"
                                       value="<?php echo htmlspecialchars($matricule_fiscal); ?>"
                                       placeholder="Ex: 1234567/A/M/000">
                            </div>
                            <div class="form-text">Identifiant unique attribué par l'administration fiscale. Laissez vide si non applicable.</div>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="2"
                                      placeholder="Numéro et nom de rue"><?php echo htmlspecialchars($adresse); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="ville" name="ville" 
                                       value="<?php echo htmlspecialchars($ville); ?>"
                                       placeholder="Ville">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="code_postal" class="form-label">Code Postal</label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                       value="<?php echo htmlspecialchars($code_postal); ?>"
                                       placeholder="Code postal">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="pays" class="form-label">Pays</label>
                                <input type="text" class="form-control" id="pays" name="pays" 
                                       value="<?php echo htmlspecialchars($pays); ?>" 
                                       placeholder="Ex: France">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tel1" class="form-label">Téléphone principal</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" id="tel1" name="tel1" 
                                           value="<?php echo htmlspecialchars($tel1); ?>"
                                           placeholder="Ex: 01 23 45 67 89">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tel2" class="form-label">Téléphone secondaire</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" id="tel2" name="tel2" 
                                           value="<?php echo htmlspecialchars($tel2); ?>"
                                           placeholder="Ex: 06 12 34 56 78">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Informations complémentaires..."><?php echo htmlspecialchars($notes); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour à la liste
                            </a>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/view.php?id=<?php echo $id; ?>" 
                                   class="btn btn-info me-2">
                                    <i class="bi bi-eye"></i> Voir les détails
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Informations sur le fournisseur -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Informations système
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <small class="text-muted d-block">ID</small>
                            <strong><code>#<?php echo $fournisseur['id']; ?></code></strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted d-block">Créé le</small>
                            <strong><?php echo date('d/m/Y à H:i', strtotime($fournisseur['created_at'])); ?></strong>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted d-block">Dernière modification</small>
                            <strong><?php echo date('d/m/Y à H:i', strtotime($fournisseur['updated_at'])); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart"></i> Statistiques
                </div>
                <div class="card-body">
                    <?php
                    // Compter les entrées liées à ce fournisseur
                    $db->prepare("SELECT COUNT(*) as count FROM entrees WHERE fournisseur_id = :id");
                    $db->bind(':id', $id);
                    $nb_entrees = $db->fetch()['count'];

                    // Compter les retours fournisseur liés
                    $db->prepare("SELECT COUNT(*) as count FROM retour_fournisseur WHERE fournisseur_id = :id");
                    $db->bind(':id', $id);
                    $nb_retours = $db->fetch()['count'];
                    ?>
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h5 class="text-primary"><?php echo $nb_entrees; ?></h5>
                            <small class="text-muted">Entrée(s)</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5 class="text-warning"><?php echo $nb_retours; ?></h5>
                            <small class="text-muted">Retour(s)</small>
                        </div>
                    </div>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/pages/entrees/index.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Voir les entrées
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php?fournisseur_id=<?php echo $id; ?>" 
                           class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-arrow-return-left"></i> Voir les retours
                        </a>
                    </div>
                </div>
            </div>

            <!-- Aide -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-question-circle"></i> Aide
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Les champs marqués d'un <span class="text-danger">*</span> sont obligatoires.
                    </p>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-upc-scan me-1"></i>
                        Le matricule fiscal doit être unique dans le système.
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="bi bi-toggle-on me-1"></i>
                        Un fournisseur inactif n'apparaîtra pas dans les listes de sélection, mais son historique sera conservé.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>