<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-list-check"></i> Test simple de sélection</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    Test Services
                </div>
                <div class="card-body">
                    <label class="form-label">Service</label>
                    <select class="form-select" id="service_id">
                        <option value="">Chargement...</option>
                    </select>

                    <div id="service_debug" class="mt-3 p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <strong>Debug Log:</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    Test Fournisseurs
                </div>
                <div class="card-body">
                    <label class="form-label">Fournisseur</label>
                    <select class="form-select" id="fournisseur_id">
                        <option value="">Chargement...</option>
                    </select>

                    <div id="fournisseur_debug" class="mt-3 p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <strong>Debug Log:</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning">
                    Test avec service sélectionné
                </div>
                <div class="card-body">
                    <label class="form-label">Service</label>
                    <select class="form-select" id="service_for_employe">
                        <option value="">Chargement...</option>
                    </select>

                    <label class="form-label mt-3">Employé (se charge après sélection service)</label>
                    <select class="form-select" id="employe_id" disabled>
                        <option value="">Sélectionner un service d'abord</option>
                    </select>

                    <div id="employe_debug" class="mt-3 p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <strong>Debug Log:</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function log(elementId, message) {
    const debugDiv = document.getElementById(elementId);
    const time = new Date().toLocaleTimeString();
    debugDiv.innerHTML += `<div><small>[${time}] ${message}</small></div>`;
    debugDiv.scrollTop = debugDiv.scrollHeight;
    console.log(`[${elementId}] ${message}`);
}

// Test 1: Charger services
$(document).ready(function() {
    log('service_debug', '🔄 Début du chargement des services...');

    $.ajax({
        url: BASE_URL + '/api/services.php',
        data: { search: '' },
        dataType: 'json',
        beforeSend: function() {
            log('service_debug', '📤 Envoi de la requête à: ' + BASE_URL + '/api/services.php?search=');
        },
        success: function(data) {
            log('service_debug', '✅ Réponse reçue!');
            log('service_debug', 'Type: ' + typeof data);
            log('service_debug', 'Est un tableau: ' + Array.isArray(data));
            log('service_debug', 'Nombre de résultats: ' + (data ? data.length : 0));

            if (data && data.length > 0) {
                $('#service_id').html('<option value="">Sélectionner un service...</option>');
                $('#service_for_employe').html('<option value="">Sélectionner un service...</option>');

                data.forEach(function(service) {
                    log('service_debug', '➕ Ajout: ' + service.text + ' (ID: ' + service.id + ')');
                    $('#service_id').append(new Option(service.text, service.id));
                    $('#service_for_employe').append(new Option(service.text, service.id));
                });

                log('service_debug', '🎉 Services chargés avec succès!');
            } else {
                log('service_debug', '⚠️ Aucun service trouvé');
                $('#service_id').html('<option value="">Aucun service disponible</option>');
                $('#service_for_employe').html('<option value="">Aucun service disponible</option>');
            }
        },
        error: function(xhr, status, error) {
            log('service_debug', '❌ ERREUR: ' + error);
            log('service_debug', 'Status: ' + status);
            log('service_debug', 'Response: ' + xhr.responseText);
            $('#service_id').html('<option value="">Erreur de chargement</option>');
        }
    });

    // Test 2: Charger fournisseurs
    log('fournisseur_debug', '🔄 Début du chargement des fournisseurs...');

    $.ajax({
        url: BASE_URL + '/api/fournisseurs.php',
        data: { search: '' },
        dataType: 'json',
        beforeSend: function() {
            log('fournisseur_debug', '📤 Envoi de la requête à: ' + BASE_URL + '/api/fournisseurs.php?search=');
        },
        success: function(data) {
            log('fournisseur_debug', '✅ Réponse reçue!');
            log('fournisseur_debug', 'Nombre de résultats: ' + (data ? data.length : 0));

            if (data && data.length > 0) {
                $('#fournisseur_id').html('<option value="">Sélectionner un fournisseur...</option>');

                data.forEach(function(fournisseur) {
                    log('fournisseur_debug', '➕ Ajout: ' + fournisseur.text + ' (ID: ' + fournisseur.id + ')');
                    $('#fournisseur_id').append(new Option(fournisseur.text, fournisseur.id));
                });

                log('fournisseur_debug', '🎉 Fournisseurs chargés avec succès!');
            } else {
                log('fournisseur_debug', '⚠️ Aucun fournisseur trouvé');
                $('#fournisseur_id').html('<option value="">Aucun fournisseur disponible</option>');
            }
        },
        error: function(xhr, status, error) {
            log('fournisseur_debug', '❌ ERREUR: ' + error);
            log('fournisseur_debug', 'Response: ' + xhr.responseText);
        }
    });

    // Test 3: Charger employés quand service change
    $('#service_for_employe').on('change', function() {
        const service_id = $(this).val();
        log('employe_debug', '🔄 Service sélectionné: ' + service_id);

        $('#employe_id').html('<option value="">Chargement...</option>').prop('disabled', !service_id);

        if (service_id) {
            $.ajax({
                url: BASE_URL + '/api/getemployebyservice.php',
                data: { service_id: service_id, search: '' },
                dataType: 'json',
                beforeSend: function() {
                    log('employe_debug', '📤 Requête: ' + BASE_URL + '/api/getemployebyservice.php?service_id=' + service_id);
                },
                success: function(data) {
                    log('employe_debug', '✅ Réponse reçue!');
                    log('employe_debug', 'Nombre d\'employés: ' + (data ? data.length : 0));

                    if (data && data.length > 0) {
                        $('#employe_id').html('<option value="">Sélectionner un employé...</option>');

                        data.forEach(function(employe) {
                            log('employe_debug', '➕ ' + employe.text);
                            $('#employe_id').append(new Option(employe.text, employe.id));
                        });

                        log('employe_debug', '🎉 Employés chargés!');
                    } else {
                        log('employe_debug', '⚠️ Aucun employé dans ce service');
                        $('#employe_id').html('<option value="">Aucun employé</option>');
                    }
                },
                error: function(xhr, status, error) {
                    log('employe_debug', '❌ ERREUR: ' + error);
                    log('employe_debug', 'Response: ' + xhr.responseText);
                }
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
