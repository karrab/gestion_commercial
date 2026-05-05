<?php
$page_title = 'Paramètres';
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();

// Récupérer les paramètres actuels
$db->prepare("SELECT * FROM parametres WHERE id = 1");
$parametres = $db->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_etablissement = trim($_POST['nom_etablissement'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $tel_fixe = trim($_POST['tel_fixe'] ?? '');
    $tel_mobile = trim($_POST['tel_mobile'] ?? '');
    $fax = trim($_POST['fax'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $site_web = trim($_POST['site_web'] ?? '');
    $logo = $parametres['logo'] ?? '';

    $errors = [];
    if (empty($nom_etablissement)) $errors[] = 'Le nom de l\'établissement est obligatoire.';
    
    // Validation site web (max 30 caractères comme défini dans la table)
    if (strlen($site_web) > 50) {
        $errors[] = 'Le site web ne doit pas dépasser 50 caractères.';
    }

    // Gestion upload logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2000000) {
            $newname = 'logo_' . time() . '.' . $ext;
            $upload_path = __DIR__ . '/../../uploads/logo/';

            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path . $newname)) {
                // Supprimer ancien logo
                if (!empty($logo) && file_exists($upload_path . $logo)) {
                    unlink($upload_path . $logo);
                }
                $logo = $newname;
            } else {
                $errors[] = 'Erreur lors de l\'upload du logo.';
            }
        } else {
            $errors[] = 'Logo non autorisé ou trop volumineux (max 2MB, formats: JPG, PNG, GIF).';
        }
    }

    if (empty($errors)) {
        try {
            if ($parametres) {
                // Update
                $sql = "UPDATE parametres SET
                            nom_etablissement = :nom_etablissement,
                            adresse = :adresse,
                            tel_fixe = :tel_fixe,
                            tel_mobile = :tel_mobile,
                            fax = :fax,
                            email = :email,
                            site_web = :site_web,
                            logo = :logo,
                            updated_at = NOW()
                        WHERE id = 1";
            } else {
                // Insert
                $sql = "INSERT INTO parametres (id, nom_etablissement, adresse, tel_fixe, tel_mobile, fax, email, site_web, logo)
                        VALUES (1, :nom_etablissement, :adresse, :tel_fixe, :tel_mobile, :fax, :email, :site_web, :logo)";
            }

            $db->prepare($sql);
            $db->bind(':nom_etablissement', $nom_etablissement);
            $db->bind(':adresse', $adresse);
            $db->bind(':tel_fixe', $tel_fixe);
            $db->bind(':tel_mobile', $tel_mobile);
            $db->bind(':fax', $fax);
            $db->bind(':email', $email);
            $db->bind(':site_web', $site_web);
            $db->bind(':logo', $logo);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'parametres', 'update', 'parametres', 1, "Modification paramètres");
                $_SESSION['success'] = 'Paramètres mis à jour avec succès.';
                header('Location: ' . BASE_URL . '/pages/parametres/edit.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
} else {
    $nom_etablissement = $parametres['nom_etablissement'] ?? 'Gestion Stock Matériel Informatique';
    $adresse = $parametres['adresse'] ?? '';
    $tel_fixe = $parametres['tel_fixe'] ?? '';
    $tel_mobile = $parametres['tel_mobile'] ?? '';
    $fax = $parametres['fax'] ?? '';
    $email = $parametres['email'] ?? '';
    $site_web = $parametres['site_web'] ?? '';
    $logo = $parametres['logo'] ?? '';
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-gear"></i> Paramètres de l'application</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Paramètres</li>
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
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-building"></i> Informations de l'établissement</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nom_etablissement" class="form-label required">Nom de l'établissement</label>
                            <input type="text" class="form-control" id="nom_etablissement" name="nom_etablissement" required value="<?php echo htmlspecialchars($nom_etablissement); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($adresse); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tel_fixe" class="form-label">Téléphone fixe</label>
                                <input type="tel" class="form-control" id="tel_fixe" name="tel_fixe" value="<?php echo htmlspecialchars($tel_fixe); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="tel_mobile" class="form-label">Téléphone mobile</label>
                                <input type="tel" class="form-control" id="tel_mobile" name="tel_mobile" value="<?php echo htmlspecialchars($tel_mobile); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="fax" class="form-label">Fax</label>
                                <input type="tel" class="form-control" id="fax" name="fax" value="<?php echo htmlspecialchars($fax); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="site_web" class="form-label">Site web</label>
                                <input type="url" class="form-control" id="site_web" name="site_web" maxlength="30" value="<?php echo htmlspecialchars($site_web); ?>">
                                <small class="text-muted">Maximum 30 caractères</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-image"></i> Logo de l'établissement</div>
                    <div class="card-body">
                        <?php if (!empty($logo)): ?>
                            <div class="mb-3">
                                <label class="form-label">Logo actuel :</label>
                                <div>
                                    <img src="<?php echo BASE_URL; ?>/uploads/logo/<?php echo htmlspecialchars($logo); ?>" alt="Logo" style="max-height: 150px;">
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="logo" class="form-label">Nouveau logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF - Taille max: 2MB</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Information</strong>
                            <p class="mb-0 mt-2 small">
                                Ces paramètres seront utilisés sur tous les documents générés (PDF, etc.).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>