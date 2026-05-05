<?php
$page_title = 'Mon profil';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$user_id = $auth->getUserId();

$db->prepare("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $user_id);
$user = $db->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tel = trim($_POST['tel'] ?? '');

    $errors = [];
    if (empty($nom)) $errors[] = 'Le nom est obligatoire.';
    if (empty($email)) $errors[] = 'L\'email est obligatoire.';

    // Vérifier unicité email
    $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = :email AND id != :id");
    $db->bind(':email', $email);
    $db->bind(':id', $user_id);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Cet email est déjà utilisé par un autre utilisateur.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET
                        nom = :nom,
                        email = :email,
                        tel = :tel,
                        updated_at = NOW()
                    WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':email', $email);
            $db->bind(':tel', $tel);
            $db->bind(':id', $user_id);

            if ($db->execute()) {
                $_SESSION['user_nom'] = $nom; // Mettre à jour la session
                $auth->logTrace($user_id, 'profil', 'update', 'users', $user_id, "Modification profil");
                $_SESSION['success'] = 'Profil mis à jour avec succès.';
                header('Location: ' . BASE_URL . '/pages/profil/edit.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
} else {
    $nom = $user['nom'];
    $email = $user['email'];
    $tel = $user['tel'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-person-circle"></i> Mon profil</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Mon profil</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person"></i> Informations personnelles</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nom" class="form-label required">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required value="<?php echo htmlspecialchars($nom); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="tel" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="tel" name="tel" value="<?php echo htmlspecialchars($tel ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-shield-lock"></i> Sécurité</div>
                    <div class="card-body">
                        <p>Pour modifier votre mot de passe, utilisez le lien ci-dessous :</p>
                        <a href="<?php echo BASE_URL; ?>/pages/profil/change_password.php" class="btn btn-warning">
                            <i class="bi bi-key"></i> Changer le mot de passe
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
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
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations compte</div>
                    <div class="card-body">
                        <p><strong>Login:</strong><br><?php echo htmlspecialchars($user['login']); ?></p>
                        <p><strong>Rôle:</strong><br><?php echo htmlspecialchars($user['role']); ?></p>
                        <p><strong>Compte créé:</strong><br><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                        <p class="mb-0"><strong>Dernière connexion:</strong><br>
                            <?php echo $user['last_login'] ? date('d/m/Y à H:i', strtotime($user['last_login'])) : 'Jamais'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
