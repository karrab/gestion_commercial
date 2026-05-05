<?php
/**
 * Endpoint Ajax pour supprimer un article d'un inventaire
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('inventaires', 'update');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données
$ligne_id = isset($_POST['ligne_id']) ? intval($_POST['ligne_id']) : 0;
$inventaire_id = isset($_POST['inventaire_id']) ? intval($_POST['inventaire_id']) : 0;

// Validation
if ($ligne_id <= 0 || $inventaire_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Vérifier que la ligne existe et que l'inventaire est en cours
    $sql_check = "SELECT li.*, i.etat, i.reinitialise 
                  FROM ligne_inventaires li
                  INNER JOIN inventaires i ON li.inventaire_id = i.id
                  WHERE li.id = :ligne_id AND li.inventaire_id = :inv_id";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':ligne_id' => $ligne_id, ':inv_id' => $inventaire_id]);
    $ligne = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$ligne) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ligne introuvable']);
        exit;
    }
    
    if ($ligne['etat'] !== 'en_cours' || $ligne['reinitialise'] == 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Inventaire non modifiable']);
        exit;
    }
    
    // Supprimer la ligne
    $sql_delete = "DELETE FROM ligne_inventaires WHERE id = :ligne_id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->execute([':ligne_id' => $ligne_id]);
    
    // Enregistrer la trace
    if (method_exists($auth, 'logTrace')) {
        $auth->logTrace(
            $auth->getUserId(),
            'inventaires',
            'delete_article',
            'ligne_inventaires',
            $ligne_id,
            "Suppression article: {$ligne['code_article']} - {$ligne['designation']}"
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Article supprimé avec succès',
        'article_id' => $ligne['article_id']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}