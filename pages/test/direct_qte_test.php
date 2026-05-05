<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Récupérer un inventaire en cours avec ses lignes
$stmt = $conn->query("SELECT * FROM inventaires WHERE etat = 'en_cours' ORDER BY id DESC LIMIT 1");
$inventaire = $stmt->fetch(PDO::FETCH_ASSOC);

$lignes = [];
if ($inventaire) {
    $stmt_lignes = $conn->prepare("SELECT li.*, a.designation
                                   FROM ligne_inventaires li
                                   INNER JOIN articles a ON li.code_article = a.code_article
                                   WHERE li.inventaire_id = :inv_id
                                   LIMIT 5");
    $stmt_lignes->execute([':inv_id' => $inventaire['id']]);
    $lignes = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);
}

// Test des permissions
$has_permission = $auth->hasPermission('inventaires', 'update');

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-clipboard-check"></i> Test Direct mise à jour qte_physique</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/test/database_check.php">Diagnostic</a></li>
                    <li class="breadcrumb-item active">Test Direct qte_physique</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-info-circle"></i> Informations Système
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">BASE_URL (PHP):</th>
                            <td><code><?php echo BASE_URL; ?></code></td>
                        </tr>
                        <tr>
                            <th>BASE_URL (JS):</th>
                            <td><code id="js-base-url">Chargement...</code></td>
                        </tr>
                        <tr>
                            <th>Permission inventaires.update:</th>
                            <td>
                                <?php if ($has_permission): ?>
                                    <span class="badge bg-success">✓ OUI</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ NON</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Inventaire en cours:</th>
                            <td>
                                <?php if ($inventaire): ?>
                                    <span class="badge bg-success">✓ Trouvé (ID: <?php echo $inventaire['id']; ?>, Ref: <?php echo $inventaire['reference']; ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">⚠ Aucun inventaire en cours</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Fichier log:</th>
                            <td>
                                <?php
                                $log_file = __DIR__ . '/../../logs/qte_physique_debug.log';
                                if (file_exists($log_file)):
                                ?>
                                    <span class="badge bg-success">✓ Existe</span>
                                    <a href="<?php echo BASE_URL; ?>/pages/test/view_logs.php" class="btn btn-sm btn-outline-primary ms-2">Voir logs</a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sera créé au premier update</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if ($inventaire && !empty($lignes)): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-pencil-square"></i> Test de mise à jour
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Modifiez une quantité physique et appuyez sur Entrée ou changez de champ pour tester la mise à jour.</p>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID Ligne</th>
                                    <th>Article</th>
                                    <th>Qté Théorique</th>
                                    <th>Qté Physique</th>
                                    <th>Écart</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lignes as $ligne): ?>
                                    <tr data-ligne-id="<?php echo $ligne['id']; ?>">
                                        <td><code><?php echo $ligne['id']; ?></code></td>
                                        <td><?php echo htmlspecialchars($ligne['designation']); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary"><?php echo number_format($ligne['qte_theorique'], 2, ',', ' '); ?></span>
                                        </td>
                                        <td class="text-end">
                                            <input type="number"
                                                   class="form-control form-control-sm text-end qte-physique-test"
                                                   data-ligne-id="<?php echo $ligne['id']; ?>"
                                                   data-qte-theorique="<?php echo $ligne['qte_theorique']; ?>"
                                                   value="<?php echo $ligne['qte_physique']; ?>"
                                                   step="0.01" min="0">
                                        </td>
                                        <td class="text-end ecart-cell">
                                            <?php
                                            $ecart = $ligne['ecart'];
                                            $ecart_class = $ecart < 0 ? 'text-danger fw-bold' : ($ecart > 0 ? 'text-success fw-bold' : 'text-muted');
                                            ?>
                                            <span class="<?php echo $ecart_class; ?>">
                                                <?php echo ($ecart > 0 ? '+' : '') . number_format($ecart, 2, ',', ' '); ?>
                                            </span>
                                        </td>
                                        <td class="text-center status-cell">
                                            <i class="bi bi-circle text-muted"></i>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="alert alert-info mt-3" id="test-info" style="display:none;">
                            <strong>Dernière mise à jour:</strong>
                            <pre class="mb-0 mt-2" id="last-response" style="font-size: 0.85em; max-height: 200px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h5><i class="bi bi-exclamation-triangle"></i> Aucun inventaire en cours</h5>
                    <p>Pour tester la mise à jour des quantités physiques:</p>
                    <ol>
                        <li>Créez un nouvel inventaire avec état "en_cours"</li>
                        <li>Ajoutez des articles à cet inventaire</li>
                        <li>Revenez sur cette page pour tester</li>
                    </ol>
                    <hr>
                    <a href="<?php echo BASE_URL; ?>/pages/inventaires/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Créer un inventaire
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-terminal"></i> Console Debug
                </div>
                <div class="card-body">
                    <div id="debug-console" style="font-family: monospace; font-size: 0.85em; max-height: 600px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px;">
                        <div class="text-success">> Console initialisée...</div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="$('#debug-console').html('<div class=\'text-success\'>> Console effacée...</div>')">
                        <i class="bi bi-trash"></i> Effacer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Afficher BASE_URL JavaScript
    $('#js-base-url').text(BASE_URL);
    logDebug('BASE_URL défini: ' + BASE_URL, 'info');
    logDebug('Page prête. Attendant modifications...', 'info');

    // Test de mise à jour
    $('.qte-physique-test').on('change', function() {
        const input = $(this);
        const ligneId = input.data('ligne-id');
        const qte = input.val();
        const qteTheorique = parseFloat(input.data('qte-theorique'));
        const row = input.closest('tr');

        logDebug('═══════════════════════════════════════', 'info');
        logDebug('DÉBUT TEST - Ligne ID: ' + ligneId, 'warning');
        logDebug('Qté saisie: ' + qte, 'info');

        // Visual feedback
        input.prop('disabled', true);
        input.addClass('border-warning bg-warning bg-opacity-10');
        row.find('.status-cell').html('<div class="spinner-border spinner-border-sm text-primary" role="status"></div>');

        const ajaxUrl = BASE_URL + '/pages/inventaires/update_qte_physique.php';
        logDebug('URL Ajax: ' + ajaxUrl, 'info');

        const postData = {
            ligne_id: ligneId,
            qte_physique: qte
        };
        logDebug('Données POST: ' + JSON.stringify(postData), 'info');

        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: postData,
            beforeSend: function() {
                logDebug('Envoi requête Ajax...', 'info');
            },
            success: function(response) {
                logDebug('✓ Réponse reçue:', 'success');
                logDebug(JSON.stringify(response, null, 2), 'success');

                $('#test-info').show();
                $('#last-response').text(JSON.stringify(response, null, 2));

                if (response.success) {
                    // Success feedback
                    input.removeClass('border-warning bg-warning bg-opacity-10 is-invalid');
                    input.addClass('is-valid border-success');

                    // Update écart
                    const ecart = parseFloat(response.ecart);
                    let ecartText = ecart.toFixed(2).replace('.', ',');
                    if (ecart > 0) ecartText = '+' + ecartText;

                    const ecartClass = ecart < 0 ? 'text-danger fw-bold' :
                                      (ecart > 0 ? 'text-success fw-bold' : 'text-muted');

                    row.find('.ecart-cell').html('<span class="' + ecartClass + '">' + ecartText + '</span>');

                    // Update icon
                    const icon = ecart < 0 ? '<i class="bi bi-arrow-down-circle text-danger"></i>' :
                                (ecart > 0 ? '<i class="bi bi-arrow-up-circle text-success"></i>' :
                                '<i class="bi bi-check-circle text-success"></i>');
                    row.find('.status-cell').html(icon);

                    logDebug('✓ SUCCÈS - Ligne mise à jour!', 'success');
                    logDebug('Écart calculé: ' + ecart, 'success');

                    setTimeout(() => {
                        input.removeClass('is-valid border-success');
                        input.prop('disabled', false);
                    }, 1500);
                } else {
                    // Error from server
                    input.removeClass('border-warning bg-warning bg-opacity-10 is-valid');
                    input.addClass('is-invalid border-danger');
                    row.find('.status-cell').html('<i class="bi bi-x-circle text-danger"></i>');
                    input.prop('disabled', false);

                    logDebug('✗ ERREUR serveur:', 'error');
                    logDebug(response.error || 'Erreur inconnue', 'error');
                    if (response.debug) {
                        logDebug('Debug: ' + response.debug, 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                logDebug('✗ ERREUR AJAX:', 'error');
                logDebug('Status: ' + status, 'error');
                logDebug('Error: ' + error, 'error');
                logDebug('HTTP Code: ' + xhr.status, 'error');
                logDebug('Response Text: ' + xhr.responseText.substring(0, 500), 'error');

                input.removeClass('border-warning bg-warning bg-opacity-10 is-valid');
                input.addClass('is-invalid border-danger');
                row.find('.status-cell').html('<i class="bi bi-x-circle text-danger"></i>');
                input.prop('disabled', false);

                $('#test-info').show();
                $('#last-response').text('ERREUR AJAX:\n' +
                                        'Status: ' + status + '\n' +
                                        'Error: ' + error + '\n' +
                                        'HTTP: ' + xhr.status + '\n\n' +
                                        xhr.responseText);
            },
            complete: function() {
                logDebug('Requête terminée.', 'info');
                logDebug('═══════════════════════════════════════', 'info');
            }
        });
    });

    // Enter key to trigger change
    $('.qte-physique-test').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).trigger('change');
        }
    });
});

function logDebug(message, type = 'info') {
    const colors = {
        'info': '#4fc3f7',
        'success': '#66bb6a',
        'warning': '#ffb74d',
        'error': '#ef5350'
    };

    const color = colors[type] || colors['info'];
    const time = new Date().toLocaleTimeString('fr-FR');

    const logEntry = '<div style="color: ' + color + '; margin: 2px 0;">' +
                    '<span style="color: #888;">[' + time + ']</span> ' +
                    message +
                    '</div>';

    $('#debug-console').append(logEntry);

    // Auto-scroll to bottom
    const console = document.getElementById('debug-console');
    console.scrollTop = console.scrollHeight;
}
</script>
