<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$log_file = __DIR__ . '/../../logs/qte_physique_debug.log';

// Clear log si demandé
if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    @file_put_contents($log_file, '');
    header('Location: ' . BASE_URL . '/pages/test/view_logs.php');
    exit;
}

// Lire le fichier log
$log_content = '';
$log_exists = file_exists($log_file);

if ($log_exists) {
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", trim($log_content));
    $log_lines = array_reverse($log_lines); // Plus récent en premier
} else {
    $log_lines = [];
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-file-earmark-text"></i> Logs des mises à jour des quantités physiques</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/test/database_check.php">Diagnostic</a></li>
                    <li class="breadcrumb-item active">Logs</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-earmark-text"></i> Contenu du fichier log</span>
                    <div>
                        <button class="btn btn-sm btn-light me-2" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Rafraîchir
                        </button>
                        <a href="?clear=1" class="btn btn-sm btn-danger" onclick="return confirm('Voulez-vous vraiment effacer tous les logs ?')">
                            <i class="bi bi-trash"></i> Effacer
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!$log_exists): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Fichier log introuvable!</strong>
                            <p class="mb-0">Le fichier <code><?php echo $log_file; ?></code> n'existe pas encore.</p>
                            <p class="mb-0 mt-2">Il sera créé automatiquement lors de la première mise à jour d'une quantité physique.</p>
                        </div>

                        <div class="alert alert-info">
                            <h6>Pour tester:</h6>
                            <ol>
                                <li>Créez un nouvel inventaire</li>
                                <li>Ajoutez des articles</li>
                                <li>Dans la page de visualisation, modifiez une quantité physique</li>
                                <li>Revenez ici pour voir les logs</li>
                            </ol>
                        </div>
                    <?php elseif (empty($log_content)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Le fichier log est vide. Aucune mise à jour n'a été effectuée depuis le dernier nettoyage.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-3">
                            <i class="bi bi-check-circle"></i>
                            <strong><?php echo count($log_lines); ?> entrées</strong> dans le fichier log
                            (plus récentes en premier)
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="15%">Date/Heure</th>
                                        <th width="85%">Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($log_lines as $index => $line): ?>
                                        <?php if (empty(trim($line))) continue; ?>
                                        <?php
                                        $parts = explode(' - ', $line, 2);
                                        $datetime = $parts[0] ?? '';
                                        $message = $parts[1] ?? $line;

                                        // Coloration selon le type de message
                                        $row_class = '';
                                        if (stripos($message, 'ERREUR') !== false || stripos($message, 'EXCEPTION') !== false) {
                                            $row_class = 'table-danger';
                                        } elseif (stripos($message, 'SUCCÈS') !== false) {
                                            $row_class = 'table-success';
                                        } elseif (stripos($message, 'POST data') !== false) {
                                            $row_class = 'table-info';
                                        }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td><small><code><?php echo htmlspecialchars($datetime); ?></code></small></td>
                                            <td>
                                                <small>
                                                    <?php
                                                    // Formater les messages JSON
                                                    if (strpos($message, '{') !== false || strpos($message, '[') !== false) {
                                                        echo '<pre class="mb-0" style="font-size: 0.85em;">' . htmlspecialchars($message) . '</pre>';
                                                    } else {
                                                        echo htmlspecialchars($message);
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <h6>Légende des couleurs:</h6>
                            <ul class="small">
                                <li><span class="badge bg-danger">Rouge</span> = Erreurs</li>
                                <li><span class="badge bg-success">Vert</span> = Succès</li>
                                <li><span class="badge bg-info">Bleu</span> = Données POST reçues</li>
                                <li><span class="badge bg-secondary">Gris</span> = Autres messages</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($log_exists && !empty($log_content)): ?>
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <i class="bi bi-code-square"></i> Contenu brut
                    </div>
                    <div class="card-body">
                        <pre style="max-height: 400px; overflow-y: auto; font-size: 0.85em; background-color: #f8f9fa; padding: 15px; border-radius: 5px;"><?php echo htmlspecialchars($log_content); ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
