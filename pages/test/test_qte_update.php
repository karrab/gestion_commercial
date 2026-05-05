<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();

// Vérifier s'il y a des inventaires
$db->prepare("SELECT id, reference, etat FROM inventaires ORDER BY id DESC LIMIT 5");
$inventaires = $db->fetchAll();

// Vérifier s'il y a des lignes d'inventaire
$db->prepare("SELECT li.*, i.reference, i.etat
              FROM ligne_inventaires li
              INNER JOIN inventaires i ON li.inventaire_id = i.id
              ORDER BY li.id DESC LIMIT 10");
$lignes = $db->fetchAll();

$test_result = null;
$test_error = null;

// Si test POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_update'])) {
    $ligne_id = intval($_POST['ligne_id']);
    $new_qte = floatval($_POST['new_qte']);

    try {
        // Récupérer la ligne avant mise à jour
        $db->prepare("SELECT * FROM ligne_inventaires WHERE id = :id");
        $db->bind(':id', $ligne_id);
        $ligne_before = $db->fetch();

        if (!$ligne_before) {
            throw new Exception("Ligne introuvable (ID: $ligne_id)");
        }

        // Calculer écart
        $ecart = $new_qte - floatval($ligne_before['qte_theorique']);

        // Mettre à jour
        $sql = "UPDATE ligne_inventaires SET qte_physique = :qte, ecart = :ecart WHERE id = :id";
        $db->prepare($sql);
        $db->bind(':qte', $new_qte);
        $db->bind(':ecart', $ecart);
        $db->bind(':id', $ligne_id);
        $db->execute();

        $rows = $db->rowCount();

        // Récupérer après mise à jour
        $db->prepare("SELECT * FROM ligne_inventaires WHERE id = :id");
        $db->bind(':id', $ligne_id);
        $ligne_after = $db->fetch();

        $test_result = [
            'success' => true,
            'rows_affected' => $rows,
            'before' => $ligne_before,
            'after' => $ligne_after
        ];

    } catch (Exception $e) {
        $test_error = $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-clipboard-check"></i> Diagnostic: Test mise à jour qte_physique</h2>
        </div>
    </div>

    <?php if ($test_result): ?>
        <div class="alert alert-success">
            <h5>✅ Test réussi!</h5>
            <p><strong>Lignes affectées:</strong> <?php echo $test_result['rows_affected']; ?></p>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>Avant:</h6>
                    <pre class="bg-light p-3"><?php echo json_encode($test_result['before'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                </div>
                <div class="col-md-6">
                    <h6>Après:</h6>
                    <pre class="bg-light p-3"><?php echo json_encode($test_result['after'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($test_error): ?>
        <div class="alert alert-danger">
            <h5>❌ Erreur lors du test</h5>
            <p><?php echo htmlspecialchars($test_error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Test 1: Vérifier structure BD -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            Test 1: Structure de la table ligne_inventaires
        </div>
        <div class="card-body">
            <?php
            $db->prepare("DESCRIBE ligne_inventaires");
            $columns = $db->fetchAll();
            ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Champ</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Défaut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($columns as $col): ?>
                        <tr class="<?php echo in_array($col['Field'], ['qte_physique', 'ecart']) ? 'table-success' : ''; ?>">
                            <td><strong><?php echo $col['Field']; ?></strong></td>
                            <td><?php echo $col['Type']; ?></td>
                            <td><?php echo $col['Null']; ?></td>
                            <td><?php echo $col['Default'] ?? 'NULL'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="alert alert-info mb-0">
                <?php
                $has_qte_physique = false;
                $has_ecart = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === 'qte_physique') $has_qte_physique = true;
                    if ($col['Field'] === 'ecart') $has_ecart = true;
                }
                ?>
                <?php if ($has_qte_physique && $has_ecart): ?>
                    ✅ Les champs <code>qte_physique</code> et <code>ecart</code> existent
                <?php else: ?>
                    ❌ Champs manquants:
                    <?php if (!$has_qte_physique) echo '<code>qte_physique</code> '; ?>
                    <?php if (!$has_ecart) echo '<code>ecart</code>'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Test 2: Permissions -->
    <div class="card mb-3">
        <div class="card-header bg-warning">
            Test 2: Permissions utilisateur
        </div>
        <div class="card-body">
            <p><strong>Utilisateur connecté:</strong> <?php echo htmlspecialchars($auth->getUser()['login'] ?? 'N/A'); ?></p>
            <p><strong>A la permission 'inventaires.update':</strong>
                <?php if ($auth->hasPermission('inventaires', 'update')): ?>
                    <span class="badge bg-success">✅ OUI</span>
                <?php else: ?>
                    <span class="badge bg-danger">❌ NON</span>
                    <div class="alert alert-danger mt-2">
                        <strong>Problème identifié!</strong> L'utilisateur n'a pas la permission requise.
                    </div>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Test 3: Données existantes -->
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            Test 3: Inventaires et lignes disponibles
        </div>
        <div class="card-body">
            <h6>Inventaires récents:</h6>
            <?php if (empty($inventaires)): ?>
                <div class="alert alert-warning">Aucun inventaire trouvé. Créez-en un d'abord.</div>
            <?php else: ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Référence</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventaires as $inv): ?>
                            <tr>
                                <td><?php echo $inv['id']; ?></td>
                                <td><?php echo htmlspecialchars($inv['reference']); ?></td>
                                <td><span class="badge bg-<?php echo $inv['etat'] === 'en_cours' ? 'warning' : 'secondary'; ?>"><?php echo $inv['etat']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h6 class="mt-3">Lignes d'inventaire récentes:</h6>
            <?php if (empty($lignes)): ?>
                <div class="alert alert-warning">Aucune ligne d'inventaire trouvée.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Inventaire</th>
                                <th>Article</th>
                                <th>Qté théorique</th>
                                <th>Qté physique</th>
                                <th>Écart</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $ligne): ?>
                                <tr class="<?php echo $ligne['etat'] === 'en_cours' ? '' : 'table-secondary'; ?>">
                                    <td><?php echo $ligne['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ligne['reference']); ?></td>
                                    <td><?php echo htmlspecialchars($ligne['code_article']); ?></td>
                                    <td><?php echo $ligne['qte_theorique']; ?></td>
                                    <td><strong><?php echo $ligne['qte_physique']; ?></strong></td>
                                    <td><?php echo $ligne['ecart']; ?></td>
                                    <td>
                                        <?php if ($ligne['etat'] === 'en_cours'): ?>
                                            <button class="btn btn-sm btn-primary"
                                                    onclick="testUpdate(<?php echo $ligne['id']; ?>, <?php echo $ligne['qte_theorique']; ?>)">
                                                Tester
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Clôturé</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Test 4: Test manuel -->
    <div class="card">
        <div class="card-header bg-success text-white">
            Test 4: Mise à jour manuelle
        </div>
        <div class="card-body">
            <form method="POST" id="testForm">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">ID de la ligne:</label>
                        <input type="number" class="form-control" name="ligne_id" id="ligne_id" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nouvelle quantité physique:</label>
                        <input type="number" step="0.01" class="form-control" name="new_qte" id="new_qte" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="test_update" class="btn btn-success w-100">
                            <i class="bi bi-play-fill"></i> Tester la mise à jour
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function testUpdate(ligneId, qteTheorique) {
    document.getElementById('ligne_id').value = ligneId;
    document.getElementById('new_qte').value = qteTheorique + 5; // Ajouter 5 pour voir la différence
    document.getElementById('new_qte').focus();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
