<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';

$auth = Auth::getInstance();
$auth->requirePermission('users', 'delete');

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

// Empêcher la suppression de soi-même
if ($user['id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = 'Vous ne pouvez pas supprimer votre propre compte.';
    header('Location: ' . BASE_URL . '/pages/users/index.php');
    exit;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->prepare("DELETE FROM users WHERE id = :id");
        $db->bind(':id', $id);

        if ($db->execute()) {
            $_SESSION['success'] = 'Utilisateur supprimé avec succès !';
            header('Location: ' . BASE_URL . '/pages/users/index.php');
            exit;
        } else {
            $_SESSION['error'] = 'Erreur lors de la suppression.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    }

    header('Location: ' . BASE_URL . '/pages/users/index.php');
    exit;
}

$page_title = 'Supprimer utilisateur';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-exclamation-triangle text-danger"></i> Confirmation de suppression</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/users/index.php">Utilisateurs</a></li>
                    <li class="breadcrumb-item active">Supprimer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-trash"></i> Supprimer l'utilisateur
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>ATTENTION :</strong> Cette action est irréversible !
                    </div>

                    <p>Vous êtes sur le point de supprimer l'utilisateur suivant :</p>

                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-person-circle"></i>
                                <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                            </h5>
                            <p class="card-text mb-1">
                                <i class="bi bi-person"></i> Login: <strong><?php echo htmlspecialchars($user['login']); ?></strong>
                            </p>
                            <p class="card-text mb-0">
                                <i class="bi bi-envelope"></i> Email: <strong><?php echo htmlspecialchars($user['mail']); ?></strong>
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Conséquences :</strong>
                        <ul class="mb-0">
                            <li>L'utilisateur sera définitivement supprimé</li>
                            <li>L'historique de ses actions restera dans le système</li>
                            <li>Cette action ne peut pas être annulée</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/users/view.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Confirmer la suppression
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
