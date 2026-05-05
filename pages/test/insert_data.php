<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$message = '';
$error = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert_data'])) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Lire le fichier SQL
        $sql_file = __DIR__ . '/../../database/insert_test_data.sql';

        if (!file_exists($sql_file)) {
            throw new Exception("Fichier SQL introuvable: $sql_file");
        }

        $sql_content = file_get_contents($sql_file);

        // Diviser en requêtes individuelles (séparées par ;)
        $queries = array_filter(array_map('trim', explode(';', $sql_content)), function($query) {
            return !empty($query) && !preg_match('/^--/', $query);
        });

        $success_count = 0;
        $total_count = count($queries);

        foreach ($queries as $query) {
            // Ignorer les commentaires
            if (preg_match('/^--/', trim($query))) {
                continue;
            }

            try {
                $stmt = $conn->query($query);

                // Si c'est un SELECT, récupérer les résultats
                if (stripos(trim($query), 'SELECT') === 0) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($result)) {
                        $results[] = $result;
                    }
                }

                $success_count++;
            } catch (PDOException $e) {
                // Ignorer les erreurs de clés dupliquées
                if ($e->getCode() != '23000') {
                    throw $e;
                }
            }
        }

        $message = "✅ Données de test insérées avec succès ! ($success_count/$total_count requêtes exécutées)";

    } catch (Exception $e) {
        $error = "❌ Erreur lors de l'insertion des données: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-database-fill-add"></i> Insertion des données de test</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/test/database_check.php">Diagnostic</a></li>
                    <li class="breadcrumb-item active">Insertion données</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-warning">
                    <i class="bi bi-exclamation-triangle"></i> Attention
                </div>
                <div class="card-body">
                    <h5>Cette action va insérer des données de test dans la base de données:</h5>
                    <ul>
                        <li><strong>5 Services</strong> (Direction, RH, IT, Comptabilité, Logistique)</li>
                        <li><strong>3 Fournisseurs</strong></li>
                        <li><strong>4 Employés</strong> (1 par service principal)</li>
                        <li><strong>4 Bureaux</strong> (liés aux employés)</li>
                        <li><strong>4 Armoires</strong></li>
                        <li><strong>3 Équipes d'inventaire</strong></li>
                        <li><strong>4 Catégories</strong> d'articles</li>
                        <li><strong>10 Articles</strong> (PC, souris, papier, etc.)</li>
                    </ul>

                    <hr>

                    <p class="mb-0">
                        <i class="bi bi-info-circle"></i> Les données existantes ne seront <strong>pas supprimées</strong>.
                        Les enregistrements en double seront ignorés grâce à la clause <code>ON DUPLICATE KEY UPDATE</code>.
                    </p>
                </div>
            </div>

            <?php if (!empty($results)): ?>
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-check2-square"></i> Résultats de vérification
                    </div>
                    <div class="card-body">
                        <?php foreach ($results as $result_set): ?>
                            <?php if (!empty($result_set)): ?>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($result_set[0]) as $header): ?>
                                                <th><?php echo htmlspecialchars($header); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($result_set as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                    <td><?php echo htmlspecialchars($value); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-play-fill"></i> Action
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="d-grid gap-2">
                            <button type="submit" name="insert_data" class="btn btn-primary btn-lg">
                                <i class="bi bi-database-fill-add"></i> Insérer les données de test
                            </button>

                            <a href="<?php echo BASE_URL; ?>/pages/test/database_check.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour au diagnostic
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-info-circle"></i> Fichier source
                </div>
                <div class="card-body">
                    <p class="small mb-2">Le script SQL source se trouve à:</p>
                    <code class="d-block bg-light p-2 rounded">
                        database/insert_test_data.sql
                    </code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
