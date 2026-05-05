<?php
/**
 * Recalcul des écarts pour toutes les lignes d'inventaire
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('inventaires', 'update');

$db = Database::getInstance();
$id = intval($_GET['id'] ?? 0);

$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

if ($inventaire['etat'] !== 'en_cours') {
    $_SESSION['error'] = 'Les écarts ne peuvent être recalculés que pour les inventaires "En cours".';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

// Vérifier si l'inventaire a été réinitialisé
if ($inventaire['reinitialise'] == 1) {
    $_SESSION['error'] = 'Les écarts ne peuvent être recalculés pour un inventaire réinitialisé.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
    exit;
}

try {
    $conn = $db->getConnection();
    
    // Recalculer les écarts (écart = qte_physique - qte_theorique)
    $sql = "UPDATE ligne_inventaires
            SET ecart = qte_physique - qte_theorique
            WHERE inventaire_id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    $nb_lignes = $stmt->rowCount();
    
    // Compter les écarts
    $stmt_count = $conn->prepare("
        SELECT 
            SUM(CASE WHEN ecart > 0 THEN 1 ELSE 0 END) as excedents,
            SUM(CASE WHEN ecart < 0 THEN 1 ELSE 0 END) as manquants,
            SUM(CASE WHEN ecart = 0 THEN 1 ELSE 0 END) as conformes
        FROM ligne_inventaires
        WHERE inventaire_id = :id
    ");
    $stmt_count->execute([':id' => $id]);
    $stats = $stmt_count->fetch(PDO::FETCH_ASSOC);
    
    $_SESSION['success'] = "Écarts calculés avec succès ! " .
                          $stats['excedents'] . " excédent(s), " .
                          $stats['manquants'] . " manquant(s), " .
                          $stats['conformes'] . " conforme(s).";
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors du calcul des écarts : ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $id);
exit;