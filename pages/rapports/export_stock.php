<?php
// export_stock.php
require_once __DIR__ . '/../includes/header.php';
$auth->requirePermission('rapports', 'export');

// Récupérer les mêmes paramètres que le rapport
$filtre = $_GET['filtre'] ?? 'tous';
$search = $_GET['search'] ?? '';

// Construire la même requête que dans stock.php
$sql = "SELECT * FROM articles WHERE actif = 1";

// Appliquer les mêmes filtres...
// (Code similaire à stock.php)

// Générer un fichier Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="etat_stock_' . date('Y-m-d') . '.xls"');

echo "Code\tDésignation\tStock Initial\tEntrées\tSorties\tRetours\tDisponible\tStock Min\tStock Max\tÉtat\n";

foreach ($articles as $a) {
    // Déterminer l'état
    if ($a['qte_disponible'] <= 0) $etat = 'Rupture';
    elseif ($a['qte_disponible'] <= $a['stock_min']) $etat = 'Faible';
    elseif ($a['qte_disponible'] >= $a['stock_max']) $etat = 'Élevé';
    else $etat = 'Normal';
    
    echo $a['code_article'] . "\t";
    echo $a['designation'] . "\t";
    echo $a['stock_initial'] . "\t";
    echo $a['qte_entree'] . "\t";
    echo $a['qte_sortie'] . "\t";
    echo $a['qte_retour'] . "\t";
    echo $a['qte_disponible'] . "\t";
    echo $a['stock_min'] . "\t";
    echo $a['stock_max'] . "\t";
    echo $etat . "\n";
}
exit;