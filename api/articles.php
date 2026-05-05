<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Version simplifiée pour debug
try {
    $db = Database::getInstance();
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT id, code_article, designation, qte_disponible, stock_min, stock_max
            FROM articles
            WHERE actif = 1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (code_article LIKE ? OR designation LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY code_article ASC LIMIT 20";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    $results = [];
    foreach ($items as $item) {
        $results[] = [
            'id' => $item['id'],
            'text' => $item['code_article'] . ' - ' . $item['designation'],
            'code_article' => $item['code_article'],
            'designation' => $item['designation'],
            'qte_disponible' => (float)$item['qte_disponible'],
            'stock_min' => (float)$item['stock_min'],
            'stock_max' => (float)$item['stock_max']
        ];
    }
    
    echo json_encode($results);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}