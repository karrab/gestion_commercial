<?php
$page_title = 'Nouvel utilisateur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    $errors = [];
    if (empty($login)) $errors[] = 'Le login est obligatoire.';
    if (empty($nom)) $errors[] = 'Le nom est obligatoire.';
    if (empty($email)) $errors[] = 'L\'email est obligatoire.';
    if (empty($password)) $errors[] = 'Le mot de passe est obligatoire.';
    if (strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';

    // Vérifier unicité
    $db->prepare("SELECT COUNT(*) as count FROM users WHERE login = :login");
    $db->bind(':login', $login);
    if ($db->fetch()['count'] > 0) $errors[] = 'Ce login existe déjà.';

    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (login, nom, email, tel, password, role, actif) VALUES (:login, :nom, :email, '', :password, :role, :actif)";
            
            $db->prepare($sql);
            $db->bind(':login', $login);
            $db->bind(':nom', $nom);
            $db->bind(':email', $email);
            $db->bind(':password', $password_hash);
            $db->bind(':role', $role);
            $db->bind(':actif', $actif);

            if ($db->execute()) {
                $_SESSION['success'] = 'Utilisateur créé avec succès.';
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
    <h2><i class="bi bi-person-plus"></i> Nouvel utilisateur</h2>

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
                            <label class="form-label required">Login</label>
                            <input type="text" class="form-control" name="login" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Nom</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Mot de passe</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Rôle</label>
                            <select class="form-select" name="role" required>
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="actif" checked>
                            <label class="form-check-label">Compte actif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-save"></i> Enregistrer</button>
                        <a href="index.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-left"></i> Retour</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
