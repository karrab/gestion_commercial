<?php
/**
 * API pour récupérer les articles de la dernière entrée d'un fournisseur
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$fournisseur_id = intval($_GET['fournisseur_id'] ?? 0);

if (empty($fournisseur_id)) {
    echo json_encode(['success' => false, 'message' => 'ID fournisseur manquant']);
    exit;
}

try {
    $db = Database::getInstance();

    // Récupérer la dernière entrée de ce fournisseur
    $db->prepare("SELECT id, date FROM entrees
                  WHERE fournisseur_id = :fournisseur_id
                  ORDER BY date DESC, id DESC
                  LIMIT 1");
    $db->bind(':fournisseur_id', $fournisseur_id);
    $db->execute();
    $lastEntree = $db->fetch();

    if (!$lastEntree) {
        echo json_encode([
            'success' => true,
            'entree' => null,
            'articles' => [],
            'message' => 'Aucune entrée trouvée pour ce fournisseur'
        ]);
        exit;
    }

    // Récupérer les articles de cette entrée avec le stock actuel
    $db->prepare("SELECT le.article_id, le.code_article, le.designation,
                         le.qte_entree as qte_entree, a.qte_disponible as stock_actuel
                  FROM ligne_entrees le
                  INNER JOIN articles a ON le.article_id = a.id
                  WHERE le.entree_id = :entree_id
                  ORDER BY le.id");
    $db->bind(':entree_id', $lastEntree['id']);
    $db->execute();
    $articles = $db->fetchAll();

    echo json_encode([
        'success' => true,
        'entree' => $lastEntree,
        'articles' => $articles
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}