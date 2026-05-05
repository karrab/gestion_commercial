<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT id, code_article, designation, qte_disponible 
        FROM articles 
        WHERE actif = 1";

$params = [];
$paramTypes = [];

if (!empty($search)) {
    $sql .= " AND (code_article LIKE :search OR designation LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY designation LIMIT 20";

try {
    $db->prepare($sql);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    
    $articles = $db->fetchAll();
    
    // Formater les résultats pour Select2
    $formattedResults = [];
    foreach ($articles as $article) {
        $formattedResults[] = [
            'id' => $article['id'],
            'text' => $article['designation'] . ' (' . $article['code_article'] . ')',
            'code_article' => $article['code_article'],
            'designation' => $article['designation'],
            'qte_disponible' => $article['qte_disponible']
        ];
    }
    
    echo json_encode($formattedResults);
} catch (Exception $e) {
    error_log("Erreur dans getarticles.php: " . $e->getMessage());
    echo json_encode([]);
}
?>