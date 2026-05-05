<?php
$page_title = 'Nouveau fournisseur';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('fournisseurs', 'create');

$db = Database::getInstance();

// Initialiser les variables du formulaire avec des valeurs par défaut
$code_frs = '';
$nom_complet = '';
$matricule_fiscal = '';
$adresse = '';
$ville = '';
$pays = '';
$code_postal = '';
$tel1 = '';
$tel2 = '';
$notes = '';
$actif = 1; // Par défaut, le fournisseur est actif

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $code_frs = trim($_POST['code_frs'] ?? '');
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

    // Vérifier l'unicité du code fournisseur (s'il est renseigné)
    if (!empty($code_frs)) {
        $db->prepare("SELECT COUNT(*) as count FROM fournisseurs WHERE code_frs = :code_frs");
        $db->bind(':code_frs', $code_frs);
        $result = $db->fetch();
        if ($result['count'] > 0) {
            $errors[] = 'Ce code fournisseur est déjà utilisé.';
        }
    }

    // Vérifier l'unicité du nom
    $db->prepare("SELECT COUNT(*) as count FROM fournisseurs WHERE nom_complet = :nom");
    $db->bind(':nom', $nom_complet);
    $result = $db->fetch();
    if ($result['count'] > 0) {
        $errors[] = 'Un fournisseur avec ce nom existe déjà.';
    }

    // Vérifier l'unicité du matricule fiscal (s'il est renseigné)
    if (!empty($matricule_fiscal)) {
        $db->prepare("SELECT COUNT(*) as count FROM fournisseurs WHERE matricule_fiscal = :matricule_fiscal");
        $db->bind(':matricule_fiscal', $matricule_fiscal);
        $result = $db->fetch();
        if ($result['count'] > 0) {
            $errors[] = 'Ce matricule fiscal est déjà utilisé par un autre fournisseur.';
        }
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO fournisseurs (code_frs, nom_complet, matricule_fiscal, adresse, ville, pays, code_postal, tel1, tel2, notes, actif)
                    VALUES (:code_frs, :nom_complet, :matricule_fiscal, :adresse, :ville, :pays, :code_postal, :tel1, :tel2, :notes, :actif)";
            $db->prepare($sql);
            $db->bind(':code_frs', $code_frs);
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

            if ($db->execute()) {
                $id = $db->lastInsertId();

                // Log de la trace
                $auth->logTrace($auth->getUserId(), 'fournisseurs', 'create', 'fournisseurs', $id, "Création: $nom_complet (Code: $code_frs)");

                $_SESSION['success'] = 'Fournisseur créé avec succès.';
                header('Location: ' . BASE_URL . '/pages/fournisseurs/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-truck"></i> Nouveau fournisseur</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php">Fournisseurs</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle"></i> Informations du fournisseur
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

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code_frs" class="form-label">Code fournisseur</label>
                                <input type="text" class="form-control" id="code_frs" name="code_frs"
                                       value="<?php echo htmlspecialchars($code_frs); ?>"
                                       placeholder="Ex: FRS001">
                                <div class="form-text">Code unique pour identifier le fournisseur. Laissez vide pour génération automatique.</div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="nom_complet" class="form-label required">Nom complet / Raison sociale</label>
                                <input type="text" class="form-control" id="nom_complet" name="nom_complet" required
                                       value="<?php echo htmlspecialchars($nom_complet); ?>"
                                       placeholder="Ex: EURL TechSupply">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="actif" class="form-label">Statut</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="actif" name="actif" value="1" <?php echo $actif ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="actif">Actif</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="matricule_fiscal" class="form-label">Matricule Fiscal</label>
                            <input type="text" class="form-control" id="matricule_fiscal" name="matricule_fiscal"
                                   value="<?php echo htmlspecialchars($matricule_fiscal); ?>"
                                   placeholder="Ex: 1234567/A/M/000">
                            <div class="form-text">Identifiant unique attribué par l'administration fiscale.</div>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo htmlspecialchars($adresse); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="ville" name="ville" value="<?php echo htmlspecialchars($ville); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="code_postal" class="form-label">Code Postal</label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($code_postal); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="pays" class="form-label">Pays</label>
                                <input type="text" class="form-control" id="pays" name="pays" value="<?php echo htmlspecialchars($pays); ?>" placeholder="France">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tel1" class="form-label">Téléphone principal</label>
                                <input type="tel" class="form-control" id="tel1" name="tel1" value="<?php echo htmlspecialchars($tel1); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tel2" class="form-label">Téléphone secondaire</label>
                                <input type="tel" class="form-control" id="tel2" name="tel2" value="<?php echo htmlspecialchars($tel2); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer le fournisseur
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Information
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        <strong>Code fournisseur :</strong> Ce code est unique dans le système. Il permet d'identifier rapidement le fournisseur. Laissez vide pour une génération automatique.
                    </p>
                    <p class="text-muted small">
                        Remplissez toutes les informations concernant le fournisseur. Le nom complet est le seul champ obligatoire.
                    </p>
                    <p class="text-muted small">
                        <strong>Matricule Fiscal :</strong> Ce numéro est unique dans le système. Il permet d'identifier officiellement le fournisseur auprès de l'administration.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>