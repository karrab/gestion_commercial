<?php
$page_title = 'Changer le mot de passe';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$user_id = $auth->getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Récupérer le mot de passe actuel
    $db->prepare("SELECT password FROM users WHERE id = :id");
    $db->bind(':id', $user_id);
    $user = $db->fetch();

    // Vérifier mot de passe actuel
    if (!password_verify($current_password, $user['password'])) {
        $errors[] = 'Le mot de passe actuel est incorrect.';
    }

    // Vérifications
    if (empty($new_password)) {
        $errors[] = 'Le nouveau mot de passe est obligatoire.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }

    if ($new_password !== $confirm_password) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET
                        password = :password,
                        updated_at = NOW()
                    WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':password', $password_hash);
            $db->bind(':id', $user_id);

            if ($db->execute()) {
                $auth->logTrace($user_id, 'profil', 'update', 'users', $user_id, "Changement mot de passe");
                $_SESSION['success'] = 'Mot de passe modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/profil/edit.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-key"></i> Changer le mot de passe</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/profil/edit.php">Mon profil</a></li>
                    <li class="breadcrumb-item active">Changer le mot de passe</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-shield-lock"></i> Nouveau mot de passe
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label required">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="new_password" class="form-label required">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <small class="text-muted">Minimum 6 caractères</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label required">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Changer le mot de passe
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/profil/edit.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-warning mt-3">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Attention :</strong> Après modification, vous devrez vous reconnecter avec votre nouveau mot de passe.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
