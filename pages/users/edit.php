<?php
$page_title = 'Modifier utilisateur';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('users', 'update');
$db = Database::getInstance();

$id = $_GET['id'] ?? 0;

// Récupérer l'utilisateur
$db->prepare("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $id);
$user = $db->fetch();

if (!$user) {
    $_SESSION['error'] = 'Utilisateur introuvable.';
    header('Location: ' . BASE_URL . '/pages/users/index.php');
    exit;
}

// Récupérer la liste des rôles
$db->prepare("SELECT id, nom, description FROM roles ORDER BY nom");
$roles = $db->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);
    $actif = isset($_POST['actif']) ? 1 : 0;

    $errors = [];

    // Validations
    if (empty($nom)) $errors[] = 'Le nom est requis.';
    if (empty($prenom)) $errors[] = 'Le prénom est requis.';
    if (empty($mail)) $errors[] = 'L\'email est requis.';
    elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) $errors[] = 'L\'email n\'est pas valide.';
    if (empty($login)) $errors[] = 'Le login est requis.';
    if ($role_id <= 0) $errors[] = 'Veuillez sélectionner un rôle.';

    // Validation mot de passe (si fourni)
    if (!empty($password)) {
        if (strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
        if ($password !== $password_confirm) $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    // Vérifier unicité login et email (sauf pour l'utilisateur actuel)
    if (empty($errors)) {
        $db->prepare("SELECT id FROM users WHERE login = :login AND id != :id");
        $db->bind(':login', $login);
        $db->bind(':id', $id);
        if ($db->fetch()) {
            $errors[] = 'Ce login existe déjà.';
        }

        $db->prepare("SELECT id FROM users WHERE mail = :mail AND id != :id");
        $db->bind(':mail', $mail);
        $db->bind(':id', $id);
        if ($db->fetch()) {
            $errors[] = 'Cet email existe déjà.';
        }
    }

    if (empty($errors)) {
        try {
            // Préparer la requête SQL
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users
                        SET nom = :nom, prenom = :prenom, mail = :mail, login = :login,
                            password = :password, role_id = :role_id, actif = :actif
                        WHERE id = :id";
            } else {
                $sql = "UPDATE users
                        SET nom = :nom, prenom = :prenom, mail = :mail, login = :login,
                            role_id = :role_id, actif = :actif
                        WHERE id = :id";
            }

            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':prenom', $prenom);
            $db->bind(':mail', $mail);
            $db->bind(':login', $login);
            if (!empty($password)) {
                $db->bind(':password', $password_hash);
            }
            $db->bind(':role_id', $role_id);
            $db->bind(':actif', $actif);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $_SESSION['success'] = 'Utilisateur modifié avec succès !';
                header('Location: ' . BASE_URL . '/pages/users/view.php?id=' . $id);
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification : ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-pencil"></i> Modifier utilisateur</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/users/index.php">Utilisateurs</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/users/view.php?id=<?php echo $user['id']; ?>">Détails</a></li>
                            <li class="breadcrumb-item active">Modifier</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/pages/users/view.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header bg-warning">
                    <i class="bi bi-person-badge"></i> Informations de l'utilisateur
                </div>
                <div class="card-body">
                    <form method="POST" id="userForm">
                        <div class="row">
                            <!-- Nom -->
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">
                                    Nom <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nom" name="nom"
                                       value="<?php echo htmlspecialchars($_POST['nom'] ?? $user['nom']); ?>" required>
                            </div>

                            <!-- Prénom -->
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">
                                    Prénom <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="prenom" name="prenom"
                                       value="<?php echo htmlspecialchars($_POST['prenom'] ?? $user['prenom']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="mail" class="form-label">
                                    <i class="bi bi-envelope"></i> Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="mail" name="mail"
                                       value="<?php echo htmlspecialchars($_POST['mail'] ?? $user['mail']); ?>" required>
                                <small class="text-muted">Doit être unique</small>
                            </div>

                            <!-- Login -->
                            <div class="col-md-6 mb-3">
                                <label for="login" class="form-label">
                                    <i class="bi bi-person"></i> Login <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="login" name="login"
                                       value="<?php echo htmlspecialchars($_POST['login'] ?? $user['login']); ?>" required>
                                <small class="text-muted">Doit être unique</small>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Changement de mot de passe :</strong>
                            Laissez vide pour conserver le mot de passe actuel.
                        </div>

                        <div class="row">
                            <!-- Nouveau mot de passe -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Nouveau mot de passe
                                </label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="text-muted">Minimum 6 caractères (laisser vide pour ne pas changer)</small>
                            </div>

                            <!-- Confirmation nouveau mot de passe -->
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">
                                    <i class="bi bi-lock-fill"></i> Confirmer le nouveau mot de passe
                                </label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Rôle -->
                            <div class="col-md-6 mb-3">
                                <label for="role_id" class="form-label">
                                    <i class="bi bi-shield-check"></i> Rôle <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="role_id" name="role_id" required>
                                    <option value="">Sélectionner un rôle...</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>"
                                                <?php echo (isset($_POST['role_id']) ? $_POST['role_id'] == $role['id'] : $user['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['nom']); ?>
                                            <?php if (!empty($role['description'])): ?>
                                                - <?php echo htmlspecialchars($role['description']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Actif -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label d-block">Statut</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="actif" name="actif"
                                           <?php echo (isset($_POST['actif']) ? (isset($_POST['actif']) ? 'checked' : '') : ($user['actif'] ? 'checked' : '')); ?>>
                                    <label class="form-check-label" for="actif">
                                        <i class="bi bi-check-circle text-success"></i> Compte actif
                                    </label>
                                </div>
                                <small class="text-muted">Un compte inactif ne peut pas se connecter</small>
                            </div>
                        </div>

                        <hr>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/pages/users/view.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Validation en temps réel
    $('#password, #password_confirm').on('keyup', function() {
        const password = $('#password').val();
        const confirm = $('#password_confirm').val();

        if (password.length > 0 && confirm.length > 0) {
            if (password === confirm) {
                $('#password_confirm').removeClass('is-invalid').addClass('is-valid');
            } else {
                $('#password_confirm').removeClass('is-valid').addClass('is-invalid');
            }
        } else {
            $('#password_confirm').removeClass('is-invalid is-valid');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
