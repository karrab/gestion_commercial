<?php
$page_title = 'Modifier employé';
require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('employes', 'update');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de l'employé
$db->prepare("SELECT * FROM employes WHERE id = :id");
$db->bind(':id', $id);
$employe = $db->fetch();

if (!$employe) {
    $_SESSION['error'] = 'Employé introuvable.';
    header('Location: ' . BASE_URL . '/pages/employes/index.php');
    exit;
}

// Récupérer le nom du service actuel pour l'affichage dans la sidebar
$stmt_svc = $db->getConnection()->prepare("SELECT nom FROM services WHERE id = :id");
$stmt_svc->execute([':id' => $employe['service_id'] ?? 0]);
$service_actuel = $stmt_svc->fetchColumn() ?: 'Non défini';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $mail = trim($_POST['mail'] ?? '');
    $tel1 = trim($_POST['tel1'] ?? '');
    $tel2 = trim($_POST['tel2'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    $errors = [];

    // Validation
    if (empty($matricule)) $errors[] = 'Le matricule est obligatoire.';
    if (empty($nom)) $errors[] = 'Le nom est obligatoire.';
    if (empty($prenom)) $errors[] = 'Le prénom est obligatoire.';
    if (empty($service_id)) $errors[] = 'Le service est obligatoire.';

    // Vérifier l'unicité du matricule (sauf pour l'employé actuel)
    if (!empty($matricule) && $matricule != $employe['matricule']) {
        $db->prepare("SELECT COUNT(*) as count FROM employes WHERE matricule = :matricule AND id != :id");
        $db->bind(':matricule', $matricule);
        $db->bind(':id', $id);
        $result = $db->fetch();
        if ($result['count'] > 0) {
            $errors[] = 'Un employé avec ce matricule existe déjà.';
        }
    }

    // Validation email
    if (!empty($mail) && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'adresse email n\'est pas valide.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE employes SET 
                    matricule = :matricule,
                    nom = :nom,
                    prenom = :prenom,
                    service_id = :service_id,
                    mail = :mail,
                    tel1 = :tel1,
                    tel2 = :tel2,
                    notes = :notes,
                    actif = :actif,
                    updated_at = NOW()
                    WHERE id = :id";
            
            $db->prepare($sql);
            $db->bind(':matricule', $matricule);
            $db->bind(':nom', $nom);
            $db->bind(':prenom', $prenom);
            $db->bind(':service_id', $service_id);
            $db->bind(':mail', $mail);
            $db->bind(':tel1', $tel1);
            $db->bind(':tel2', $tel2);
            $db->bind(':notes', $notes);
            $db->bind(':actif', $actif);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), 'employes', 'update', 'employes', $id, "Modification: $nom $prenom");

                $_SESSION['success'] = 'Employé modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/employes/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    // Pré-remplir avec les données existantes
    $matricule = $employe['matricule'] ?? '';
    $nom = $employe['nom'] ?? '';
    $prenom = $employe['prenom'] ?? '';
    $service_id = $employe['service_id'] ?? 0;
    $mail = $employe['mail'] ?? '';
    $tel1 = $employe['tel1'] ?? '';
    $tel2 = $employe['tel2'] ?? '';
    $notes = $employe['notes'] ?? '';
    $actif = $employe['actif'] ?? 0;
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-people"></i> Modifier l'employé</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/employes/index.php">Employés</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $id; ?>">Détails</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil"></i> Informations de l'employé
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="matricule" class="form-label required">Matricule</label>
                                <input type="text" class="form-control" id="matricule" name="matricule" required
                                       value="<?php echo htmlspecialchars($matricule); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label required">Service</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Sélectionner un service...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label required">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required
                                       value="<?php echo htmlspecialchars($nom); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label required">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required
                                       value="<?php echo htmlspecialchars($prenom); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="mail" name="mail"
                                       value="<?php echo htmlspecialchars($mail); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tel1" class="form-label">Téléphone 1</label>
                                <input type="tel" class="form-control" id="tel1" name="tel1"
                                       value="<?php echo htmlspecialchars($tel1); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tel2" class="form-label">Téléphone 2</label>
                                <input type="tel" class="form-control" id="tel2" name="tel2"
                                       value="<?php echo htmlspecialchars($tel2); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check pt-4">
                                    <input type="checkbox" class="form-check-input" id="actif" name="actif" value="1"
                                           <?php echo $actif ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="actif">Actif</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo htmlspecialchars($notes); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Informations système
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo htmlspecialchars($employe['id'] ?? ''); ?></p>
                    <p><strong>Matricule actuel:</strong><br><?php echo htmlspecialchars($employe['matricule'] ?? ''); ?></p>
                    <p><strong>Service actuel:</strong><br>
                        <?php echo htmlspecialchars($service_actuel); ?>
                    </p>
                    <p><strong>Créé le:</strong><br>
                        <?php 
                        if (!empty($employe['created_at'])) {
                            echo date('d/m/Y à H:i', strtotime($employe['created_at']));
                        } else {
                            echo 'Non défini';
                        }
                        ?>
                    </p>
                    <p><strong>Modifié le:</strong><br>
                        <?php 
                        if (!empty($employe['updated_at'])) {
                            echo date('d/m/Y à H:i', strtotime($employe['updated_at']));
                        } else {
                            echo 'Non défini';
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle"></i> Précautions
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-info-circle"></i>
                        <strong>Attention :</strong> La modification du matricule peut affecter les sorties et retours associés.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

$(document).ready(function() {
    initServiceSelect('#service_id');

    <?php if (!empty($service_id)): ?>
    // Pré-sélectionner le service actuel
    $.ajax({
        url: BASE_URL + '/api/services.php',
        data: { id: <?php echo intval($service_id); ?> },
        dataType: 'json'
    }).done(function(data) {
        if (data && data.length > 0) {
            var option = new Option(data[0].text, data[0].id, true, true);
            $('#service_id').append(option).trigger('change');
        }
    });
    <?php endif; ?>
});
</script>