<?php
$page_title = 'Modifier utilisateur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$db->prepare("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $id);
$user = $db->fetch();

if (!$user) {
    $_SESSION['error'] = 'Utilisateur introuvable.';
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    $errors = [];
    if (empty($nom)) $errors[] = 'Le nom est obligatoire.';
    if (empty($email)) $errors[] = 'L\'email est obligatoire.';

    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET nom = :nom, email = :email, role = :role, actif = :actif";
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password = :password";
            }
            $sql .= ", updated_at = NOW() WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':email', $email);
            $db->bind(':role', $role);
            $db->bind(':actif', $actif);
            if (!empty($password)) $db->bind(':password', $password_hash);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $_SESSION['success'] = 'Utilisateur modifié avec succès.';
                header('Location: index.php');
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
    <h2><i class="bi bi-pencil"></i> Modifier utilisateur</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Login</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['login']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Nom</label>
                            <input type="text" class="form-control" name="nom" required value="<?php echo htmlspecialchars($user['nom']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Email</label>
                            <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="password" minlength="6">
                            <small class="text-muted">Laisser vide pour ne pas modifier</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Rôle</label>
                            <select class="form-select" name="role" required>
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="actif" <?php echo $user['actif'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Compte actif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-save"></i> Enregistrer</button>
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-info w-100 mb-2"><i class="bi bi-eye"></i> Voir</a>
                        <a href="index.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-left"></i> Retour</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
