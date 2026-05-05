<?php
$page_title = 'Modifier bureau';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('bureaux', 'update');
$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$sql = "SELECT b.*,
               s.nom as service_nom,
               e.nom as employe_nom, e.prenom as employe_prenom, e.matricule
        FROM bureaux b
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN employes e ON b.employe_id = e.id
        WHERE b.id = :id";

$db->prepare($sql);
$db->bind(':id', $id);
$bureau = $db->fetch();

if (!$bureau) {
    $_SESSION['error'] = 'Bureau introuvable.';
    header('Location: ' . BASE_URL . '/pages/bureaux/index.php');
    exit;
}

// Récupérer tous les services
$db->prepare("SELECT id, nom FROM services ORDER BY nom");
$services = $db->fetchAll();

// Récupérer tous les employés
$db->prepare("SELECT id, nom, prenom, matricule, service_id FROM employes WHERE actif = 1 ORDER BY nom, prenom");
$employes = $db->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_local = trim($_POST['code_local'] ?? '');
    $batiment = trim($_POST['batiment'] ?? '');
    $etage = trim($_POST['etage'] ?? '');
    $service_id = !empty($_POST['service_id']) ? intval($_POST['service_id']) : null;
    $employe_id = !empty($_POST['employe_id']) ? intval($_POST['employe_id']) : null;
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($code_local)) $errors[] = 'Le code local est obligatoire.';

    // Vérifier unicité
    $db->prepare("SELECT COUNT(*) as count FROM bureaux WHERE code_local = :code AND id != :id");
    $db->bind(':code', $code_local);
    $db->bind(':id', $id);
    if ($db->fetch()['count'] > 0) {
        $errors[] = 'Un bureau avec ce code local existe déjà.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE bureaux SET
                        code_local = :code,
                        batiment = :batiment,
                        etage = :etage,
                        service_id = :service_id,
                        employe_id = :employe_id,
                        notes = :notes,
                        updated_at = NOW()
                    WHERE id = :id";

            $db->prepare($sql);
            $db->bind(':code', $code_local);
            $db->bind(':batiment', $batiment);
            $db->bind(':etage', $etage);
            $db->bind(':service_id', $service_id);
            $db->bind(':employe_id', $employe_id);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'bureaux', 'update', 'bureaux', $id, "Modification: $code_local");
                $_SESSION['success'] = 'Bureau modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/bureaux/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }
} else {
    $code_local = $bureau['code_local'];
    $batiment = $bureau['batiment'];
    $etage = $bureau['etage'];
    $service_id = $bureau['service_id'];
    $employe_id = $bureau['employe_id'];
    $notes = $bureau['notes'];
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-door-open"></i> Modifier le bureau</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php">Bureaux</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
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
                                <input type="text" class="form-control" id="code_local" name="code_local" required value="<?php echo htmlspecialchars($code_local ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="batiment" class="form-label">Bâtiment</label>
                                <input type="text" class="form-control" id="batiment" name="batiment" value="<?php echo htmlspecialchars($batiment ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="etage" class="form-label">Étage</label>
                                <input type="text" class="form-control" id="etage" name="etage" value="<?php echo htmlspecialchars($etage ?? ''); ?>">
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
                                <select class="form-select" id="service_id" name="service_id">
                                    <option value="">Aucun</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="employe_id" class="form-label">Employé</label>
                                <select class="form-select" id="employe_id" name="employe_id">
                                    <option value="">Aucun</option>
                                    <?php foreach ($employes as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo $employe_id == $emp['id'] ? 'selected' : ''; ?> data-service="<?php echo $emp['service_id']; ?>">
                                            <?php echo htmlspecialchars($emp['nom'] . ' ' . $emp['prenom'] . ' (' . $emp['matricule'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/bureaux/view.php?id=<?php echo $id; ?>" class="btn btn-info">
                                <i class="bi bi-eye"></i> Voir
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/bureaux/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-clock-history"></i> Informations</div>
                    <div class="card-body">
                        <p><strong>ID:</strong> <?php echo $bureau['id']; ?></p>
                        <p><strong>Créé le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($bureau['created_at'])); ?></p>
                        <p class="mb-0"><strong>Modifié le:</strong><br><?php echo date('d/m/Y à H:i', strtotime($bureau['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Filtrer les employés en fonction du service sélectionné
    function filterEmployees() {
        var selectedService = $('#service_id').val();
        $('#employe_id option').each(function() {
            var serviceId = $(this).data('service');
            if (serviceId === undefined || serviceId === selectedService || selectedService === '') {
                $(this).show();
            } else {
                $(this).hide();
                // Si l'option est sélectionnée mais ne correspond pas au service, la désélectionner
                if ($(this).prop('selected')) {
                    $(this).prop('selected', false);
                    $('#employe_id').val('');
                }
            }
        });
    }

    // Initialiser le filtre
    filterEmployees();

    // Mettre à jour le filtre quand le service change
    $('#service_id').on('change', function() {
        filterEmployees();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
