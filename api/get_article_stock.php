<?php
/**
 * API pour récupérer le stock d'un article
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$article_id = intval($_GET['article_id'] ?? 0);

if (empty($article_id)) {
    echo json_encode(['success' => false, 'message' => 'ID article manquant']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $db->prepare("SELECT qte_disponible FROM articles WHERE id = :id AND actif = 1");
    $db->bind(':id', $article_id);
    $article = $db->fetch();
    
    if (!$article) {
        echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'stock' => floatval($article['qte_disponible'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
