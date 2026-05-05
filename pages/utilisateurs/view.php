<?php
$page_title = 'Détails utilisateur';
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
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <h2><i class="bi bi-person"></i> Détails utilisateur</h2>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Informations</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Login</th>
                            <td><?php echo htmlspecialchars($user['login']); ?></td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td><?php echo htmlspecialchars($user['nom']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td><?php echo htmlspecialchars($user['tel'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Rôle</th>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($user['role']); ?></span></td>
                        </tr>
                        <tr>
                            <th>Statut</th>
                            <td>
                                <?php if ($user['actif']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Créé le</th>
                            <td><?php echo date('d/m/Y à H:i', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière connexion</th>
                            <td><?php echo $user['last_login'] ? date('d/m/Y à H:i', strtotime($user['last_login'])) : 'Jamais'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Actions</div>
                <div class="card-body">
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning w-100 mb-2"><i class="bi bi-pencil"></i> Modifier</a>
                    <?php if ($user['id'] != $auth->getUserId()): ?>
                        <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-danger w-100 mb-2" onclick="return confirm('Supprimer cet utilisateur ?');"><i class="bi bi-trash"></i> Supprimer</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-left"></i> Retour</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
