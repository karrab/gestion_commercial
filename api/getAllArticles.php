<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

$sql = "SELECT id, code_article, designation, qte_disponible 
        FROM articles 
        WHERE actif = 1 
        AND qte_disponible > 0
        ORDER BY code_article, designation";

$db->prepare($sql);
$articles = $db->fetchAll();

// Formater pour Select2
$formatted = [];
foreach ($articles as $article) {
    $text = $article['code_article'] . ' - ' . $article['designation'];
    if ($article['qte_disponible'] > 0) {
        $text .= ' (Stock: ' . number_format($article['qte_disponible'], 0, ',', ' ') . ')';
    }
    
    $formatted[] = [
        'id' => $article['id'],
        'text' => $text,
        'code_article' => $article['code_article'],
        'designation' => $article['designation'],
        'qte_disponible' => $article['qte_disponible']
    ];
}

header('Content-Type: application/json');
echo json_encode($formatted);
?>