<?php
/**
 * Endpoint Ajax pour ajouter un article individuel à un inventaire
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
$inventaire_id = isset($_POST['inventaire_id']) ? intval($_POST['inventaire_id']) : 0;
$article_id = isset($_POST['article_id']) ? intval($_POST['article_id']) : 0;
$code_article = isset($_POST['code_article']) ? trim($_POST['code_article']) : '';
$designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
$qte_theorique = isset($_POST['qte_theorique']) ? floatval($_POST['qte_theorique']) : 0;

// Validation
if ($inventaire_id <= 0 || $article_id <= 0 || empty($code_article) || empty($designation)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Vérifier que l'inventaire existe et est en cours
    $sql_check = "SELECT * FROM inventaires WHERE id = :id AND etat = 'en_cours' AND reinitialise = 0";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':id' => $inventaire_id]);
    $inventaire = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$inventaire) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Inventaire non modifiable']);
        exit;
    }
    
    // Vérifier que l'article n'est pas déjà dans l'inventaire
    $sql_check_article = "SELECT id FROM ligne_inventaires WHERE inventaire_id = :inv_id AND article_id = :art_id";
    $stmt_check_article = $conn->prepare($sql_check_article);
    $stmt_check_article->execute([':inv_id' => $inventaire_id, ':art_id' => $article_id]);
    
    if ($stmt_check_article->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Article déjà présent dans l\'inventaire']);
        exit;
    }
    
    // Calculer l'écart initial
    $ecart = -$qte_theorique;
    
    // Insérer la ligne d'inventaire
    $sql_insert = "INSERT INTO ligne_inventaires 
                   (inventaire_id, article_id, code_article, designation, qte_theorique, qte_physique, ecart, created_at)
                   VALUES (:inv_id, :art_id, :code, :design, :qte_theo, 0, :ecart, NOW())";
    
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([
        ':inv_id' => $inventaire_id,
        ':art_id' => $article_id,
        ':code' => $code_article,
        ':design' => $designation,
        ':qte_theo' => $qte_theorique,
        ':ecart' => $ecart
    ]);
    
    $ligne_id = $conn->lastInsertId();
    
    // Enregistrer la trace
    if (method_exists($auth, 'logTrace')) {
        $auth->logTrace(
            $auth->getUserId(),
            'inventaires',
            'add_article',
            'ligne_inventaires',
            $ligne_id,
            "Ajout article: $code_article - $designation"
        );
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Article ajouté avec succès',
        'ligne_id' => $ligne_id,
        'article_id' => $article_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}