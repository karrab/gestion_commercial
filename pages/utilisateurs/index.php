<?php
$page_title = 'Utilisateurs';
require_once __DIR__ . '/../../includes/header.php';

$auth->requireAdmin();
$db = Database::getInstance();

$db->prepare("SELECT u.*, r.nom as role_nom FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.nom");
$users = $db->fetchAll();
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-people"></i> Utilisateurs</h2>
                <a href="<?php echo BASE_URL; ?>/pages/utilisateurs/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouvel utilisateur
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Login</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['login']); ?></td>
                            <td><?php echo htmlspecialchars($user['nom']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td>
                                <?php if ($user['actif']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <?php if ($user['id'] != $auth->getUserId()): ?>
                                    <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet utilisateur ?');"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
