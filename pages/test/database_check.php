<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Liste des tables à vérifier
$tables = [
    'services' => 'Services',
    'employes' => 'Employés',
    'fournisseurs' => 'Fournisseurs',
    'bureaux' => 'Bureaux',
    'armoires' => 'Armoires',
    'articles' => 'Articles',
    'users' => 'Utilisateurs',
    'roles' => 'Rôles'
];

$data_counts = [];
foreach ($tables as $table => $label) {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $data_counts[$table] = [
        'label' => $label,
        'count' => $result['count']
    ];
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-clipboard-data"></i> Diagnostic des données</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Diagnostic</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-table"></i> Nombre d'enregistrements par table
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Nombre d'enregistrements</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_counts as $table => $info): ?>
                                <tr>
                                    <td><strong><?php echo $info['label']; ?></strong> (<?php echo $table; ?>)</td>
                                    <td>
                                        <span class="badge bg-<?php echo $info['count'] > 0 ? 'success' : 'danger'; ?> fs-6">
                                            <?php echo $info['count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($info['count'] > 0): ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> OK</span>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Vide</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php
            $empty_tables = array_filter($data_counts, function($info) {
                return $info['count'] == 0;
            });

            if (!empty($empty_tables)):
            ?>
                <div class="alert alert-warning mt-3">
                    <h5><i class="bi bi-exclamation-triangle"></i> Tables vides détectées</h5>
                    <p>Les tables suivantes sont vides:</p>
                    <ul>
                        <?php foreach ($empty_tables as $table => $info): ?>
                            <li><strong><?php echo $info['label']; ?></strong> (<?php echo $table; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <p class="mb-0">
                        <strong>Solution:</strong> Exécutez le script SQL de données de test situé dans:
                        <code>database/insert_test_data.sql</code>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-database-fill-add"></i> Action rapide
                </div>
                <div class="card-body">
                    <p class="mb-3">Insérez rapidement les données de test en un clic:</p>
                    <div class="d-grid">
                        <a href="<?php echo BASE_URL; ?>/pages/test/insert_data.php" class="btn btn-success btn-lg">
                            <i class="bi bi-play-fill"></i> Insérer les données de test
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-info-circle"></i> Instructions manuelles
                </div>
                <div class="card-body">
                    <h6>Pour insérer manuellement via SQL:</h6>
                    <ol class="small">
                        <li>Ouvrez phpMyAdmin ou votre outil SQL</li>
                        <li>Sélectionnez la base de données</li>
                        <li>Exécutez le fichier: <code>database/insert_test_data.sql</code></li>
                        <li>Rafraîchissez cette page</li>
                    </ol>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-gear"></i> Test des APIs
                </div>
                <div class="card-body">
                    <p class="small">Testez les APIs pour vérifier qu'elles retournent des données:</p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/pages/test/api_test.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right"></i> Test APIs (ancien)
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/test/api_direct_test.php" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-code-square"></i> Test APIs Direct
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/test/simple_select_test.php" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-list-check"></i> Test Select avec Debug
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-file-earmark-text"></i> Logs & Debug
                </div>
                <div class="card-body">
                    <p class="small">Consultez les logs pour déboguer:</p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/pages/test/view_logs.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-bug"></i> Voir Logs qte_physiques
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/test/test_qte_update.php" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-clipboard-check"></i> Test mise à jour qte_physiques
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/test/direct_qte_test.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-terminal"></i> Test Direct avec Console Debug
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
