<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('articles', 'view');
$db = Database::getInstance();

// Récupérer les mêmes paramètres de recherche
$search = $_GET['search'] ?? '';
$alerte_stock = $_GET['alerte_stock'] ?? '';
$actif_filter = $_GET['actif'] ?? '';

$sql = "SELECT * FROM articles WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (code_article LIKE :search_code OR designation LIKE :search_designation)";
    $params[':search_code'] = '%' . $search . '%';
    $params[':search_designation'] = '%' . $search . '%';
}

if ($alerte_stock == 'faible') {
    $sql .= " AND qte_disponible < stock_min AND stock_min > 0";
} elseif ($alerte_stock == 'rupture') {
    $sql .= " AND qte_disponible <= 0";
}

if ($actif_filter !== '') {
    $sql .= " AND actif = :actif";
    $params[':actif'] = (int)$actif_filter;
}

$sql .= " ORDER BY designation ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$articles = $stmt->fetchAll();

// Créer le contenu Excel
$output = "Code article\tDésignation\tStock initial\tEntrées\tSorties\tRetours\tDisponible\tStock min\tStock max\tStatut stock\tActif\n";

foreach ($articles as $article) {
    // Déterminer le statut du stock
    $stock_label = 'OK';
    if ($article['qte_disponible'] <= 0) {
        $stock_label = 'Rupture';
    } elseif ($article['stock_min'] > 0 && $article['qte_disponible'] < $article['stock_min']) {
        $stock_label = 'Faible';
    } elseif ($article['stock_max'] > 0 && $article['qte_disponible'] > $article['stock_max']) {
        $stock_label = 'Élevé';
    }
    
    $actif_label = $article['actif'] ? 'Actif' : 'Inactif';
    
    $output .= $article['code_article'] . "\t";
    $output .= $article['designation'] . "\t";
    $output .= number_format($article['stock_initial'], 2, ',', ' ') . "\t";
    $output .= number_format($article['qte_entree'], 2, ',', ' ') . "\t";
    $output .= number_format($article['qte_sortie'], 2, ',', ' ') . "\t";
    $output .= number_format($article['qte_retour'], 2, ',', ' ') . "\t";
    $output .= number_format($article['qte_disponible'], 2, ',', ' ') . "\t";
    $output .= number_format($article['stock_min'], 2, ',', ' ') . "\t";
    $output .= number_format($article['stock_max'], 2, ',', ' ') . "\t";
    $output .= $stock_label . "\t";
    $output .= $actif_label . "\n";
}

// En-têtes pour téléchargement Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="articles_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Convertir UTF-8 vers ISO pour Excel
echo mb_convert_encoding($output, 'UTF-16LE', 'UTF-8');
exit;
