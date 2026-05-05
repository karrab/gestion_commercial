<?php
$page_title = 'Nouveau bureau';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('bureaux', 'create');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_local = trim($_POST['code_local'] ?? '');
    $batiment = trim($_POST['batiment'] ?? '');
    $etage = trim($_POST['etage'] ?? '');
    $service_id = !empty($_POST['service_id']) ? intval($_POST['service_id']) : null;
    $employe_id = !empty($_POST['employe_id']) ? intval($_POST['employe_id']) : null;
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($code_local)) $errors[] = 'Le code local est obligatoire.';

    // Vérifier unicité du code_local
    $db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE code_local = :code");
    $db->bind(':code', $code_local);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Un bureau avec ce code local existe déjà.';
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO bureaux (code_local, batiment, etage, service_id, employe_id, notes)
                    VALUES (:code, :batiment, :etage, :service_id, :employe_id, :notes)";

            $db->prepare($sql);
            $db->bind(':code', $code_local);
            $db->bind(':batiment', $batiment);
            $db->bind(':etage', $etage);
            $db->bind(':service_id', $service_id);
            $db->bind(':employe_id', $employe_id);
            $db->bind(':notes', $notes);

            if ($db->execute()) {
                $id = $db->lastInsertId();
                $auth->logTrace($auth->getUserId(), 'bureaux', 'create', 'bureaux', $id, "Création: $code_local");
                $_SESSION['success'] = 'Bureau créé avec succès.';
                header('Location: ' . BASE_URL . '/pages/bureaux/index.php');
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
            <h2><i class="bi bi-door-open"></i> Nouveau bureau</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php">Bureaux</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
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
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations du bureau</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code_local" class="form-label required">Code local</label>
                                <input type="text" class="form-control" id="code_local" name="code_local" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="batiment" class="form-label">Bâtiment</label>
                                <input type="text" class="form-control" id="batiment" name="batiment">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="etage" class="form-label">Étage</label>
                                <input type="text" class="form-control" id="etage" name="etage">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-people"></i> Affectation</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label">Service</label>
                                <select class="form-select select2" id="service_id" name="service_id">
                                    <option value="">Aucun</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_id" class="form-label">Employé</label>
                                <select class="form-select select2" id="employe_id" name="employe_id">
                                    <option value="">Aucun</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    initServiceSelect('#service_id');

    // Charger employés quand service change
    $('#service_id').on('change', function() {
        let service_id = $(this).val();
        $('#employe_id').empty().append('<option value="">Aucun</option>');
        if (service_id) {
            initEmployeSelect('#employe_id', service_id);
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
