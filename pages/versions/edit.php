<?php
$page_title = 'Modifier version';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('versions', 'update');
$db = Database::getInstance();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . '/pages/versions/index.php');
    exit;
}

$db->prepare("SELECT * FROM versions WHERE id = :id");
$db->bind(':id', $id);
$version = $db->fetch();
if (!$version) {
    $_SESSION['error'] = 'Version introuvable.';
    header('Location: ' . BASE_URL . '/pages/versions/index.php');
    exit;
}

$errors = [];
$data = $version;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['num_version']   = trim($_POST['num_version'] ?? '');
    $data['date']          = trim($_POST['date'] ?? '');
    $data['direction']     = trim($_POST['direction'] ?? '');
    $data['developpe_par'] = trim($_POST['developpe_par'] ?? '');
    $data['notes']         = trim($_POST['notes'] ?? '');

    if (empty($data['num_version'])) $errors[] = 'Le numéro de version est obligatoire.';
    if (empty($data['date']))        $errors[] = 'La date est obligatoire.';

    if (empty($errors)) {
        $db->prepare("UPDATE versions SET num_version = :num_version, date = :date,
                      direction = :direction, developpe_par = :developpe_par, notes = :notes
                      WHERE id = :id");
        $db->bind(':num_version',   $data['num_version']);
        $db->bind(':date',          $data['date']);
        $db->bind(':direction',     $data['direction'] ?: null);
        $db->bind(':developpe_par', $data['developpe_par'] ?: null);
        $db->bind(':notes',         $data['notes'] ?: null);
        $db->bind(':id',            $id);
        $db->execute();

        $auth->logTrace($auth->getUserId(), 'versions', 'update', 'versions', $id, 'Modification version ' . $data['num_version']);

        $_SESSION['success'] = 'Version modifiée avec succès.';
        header('Location: ' . BASE_URL . '/pages/versions/index.php');
        exit;
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-tag"></i> Modifier la version</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/versions/index.php">Versions</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($data['num_version']); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-pencil"></i> Modifier la version <?php echo htmlspecialchars($data['num_version']); ?></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="num_version" class="form-label">Numéro de version <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="num_version" name="num_version"
                                       value="<?php echo htmlspecialchars($data['num_version']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date"
                                       value="<?php echo htmlspecialchars($data['date']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="direction" class="form-label">Direction</label>
                                <input type="text" class="form-control" id="direction" name="direction"
                                       value="<?php echo htmlspecialchars($data['direction'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="developpe_par" class="form-label">Développé par</label>
                                <input type="text" class="form-control" id="developpe_par" name="developpe_par"
                                       value="<?php echo htmlspecialchars($data['developpe_par'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="5"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/versions/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
