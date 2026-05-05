<?php
/**
 * API pour récupérer les articles du dernier retour d'un fournisseur
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$fournisseur_id = intval($_GET['fournisseur_id'] ?? 0);
$exclude_id = intval($_GET['exclude_id'] ?? 0);

if (empty($fournisseur_id)) {
    echo json_encode(['success' => false, 'message' => 'ID fournisseur manquant']);
    exit;
}

try {
    $db = Database::getInstance();

    // Récupérer le dernier retour de ce fournisseur
    $sql = "SELECT id, date FROM retour_fournisseur 
            WHERE fournisseur_id = :fournisseur_id";
    
    if ($exclude_id > 0) {
        $sql .= " AND id != :exclude_id";
    }
    
    $sql .= " ORDER BY date DESC, id DESC LIMIT 1";
    
    $db->prepare($sql);
    $db->bind(':fournisseur_id', $fournisseur_id);
    
    if ($exclude_id > 0) {
        $db->bind(':exclude_id', $exclude_id);
    }
    
    $db->execute();
    $lastRetour = $db->fetch();

    if (!$lastRetour) {
        echo json_encode([
            'success' => true,
            'retour' => null,
            'articles' => [],
            'message' => 'Aucun retour trouvé pour ce fournisseur'
        ]);
        exit;
    }

    // Récupérer les articles de ce retour avec le stock actuel
    // NOTE: Vérifiez si votre table s'appelle bien 'ligne_retour_fournisseur'
    $db->prepare("SELECT lrf.article_id, lrf.code_article, lrf.designation,
                         lrf.qte, a.qte_disponible as stock_actuel
                  FROM ligne_retour_fournisseur lrf
                  LEFT JOIN articles a ON lrf.article_id = a.id
                  WHERE lrf.retour_fournisseur_id = :retour_id
                  ORDER BY lrf.id");
    $db->bind(':retour_id', $lastRetour['id']);
    $db->execute();
    $articles = $db->fetchAll();

    echo json_encode([
        'success' => true,
        'retour' => $lastRetour,
        'articles' => $articles
    ]);

} catch (Exception $e) {
    error_log("Erreur last_retour_fournisseur: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}