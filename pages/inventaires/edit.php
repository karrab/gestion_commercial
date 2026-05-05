<?php
/**
 * Modification d'un inventaire
 */

$page_title = 'Modifier inventaire';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('inventaires', 'update');
$db = Database::getInstance();
$id = intval($_GET['id'] ?? 0);

// Récupérer l'inventaire
$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

if ($inventaire['etat'] !== 'en_cours') {
    $_SESSION['error'] = 'Seuls les inventaires "En cours" peuvent être modifiés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? $inventaire['date'];
    $equipe_id = !empty($_POST['equipe_id']) ? intval($_POST['equipe_id']) : null;
   
    try {
        $sql = "UPDATE inventaires
                SET date = :date,
                    equipe_id = :equipe_id
                WHERE id = :id";
        
        $conn = $db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':date' => $date,
            ':equipe_id' => $equipe_id,
            ':id' => $id
        ]);
        
        $_SESSION['success'] = "Inventaire {$inventaire['reference']} modifié avec succès.";
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur lors de la modification : ' . $e->getMessage();
    }
}

// Récupérer les équipes
$stmt_equipes = $db->getConnection()->query("SELECT id, nom FROM equipes_inventaire ORDER BY nom");
$equipes = $stmt_equipes->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-pencil"></i> Modifier un inventaire</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">Inventaires</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header bg-warning">
                    <i class="bi bi-pencil"></i> Modifier l'inventaire: <?php echo htmlspecialchars($inventaire['reference']); ?>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Vous pouvez modifier la date et l'équipe. La référence ne peut pas être modifiée.
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Référence</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($inventaire['reference']); ?>" disabled>
                            <div class="form-text">La référence est générée automatiquement et ne peut être modifiée</div>
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Date de l'inventaire <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?php echo htmlspecialchars($inventaire['date'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="equipe_id" class="form-label">Équipe d'inventaire</label>
                            <select class="form-select" id="equipe_id" name="equipe_id">
                                <option value="">Aucune équipe</option>
                                <?php foreach ($equipes as $equipe): ?>
                                    <option value="<?php echo $equipe['id']; ?>"
                                            <?php echo ($inventaire['equipe_id'] ?? 0) == $equipe['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($equipe['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?php echo BASE_URL; ?>/pages/inventaires/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-check-circle"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>