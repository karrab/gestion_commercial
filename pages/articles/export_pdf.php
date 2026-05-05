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

// Créer le PDF avec une librairie simple (TCPDF si disponible, sinon HTML simple)
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Liste des articles</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 9pt;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .bg-success { background-color: #d4edda; color: #155724; }
        .bg-warning { background-color: #fff3cd; color: #856404; }
        .bg-danger { background-color: #f8d7da; color: #721c24; }
        .bg-info { background-color: #d1ecf1; color: #0c5460; }
        .bg-secondary { background-color: #e2e3e5; color: #383d41; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Liste des articles</div>
        <div class="subtitle">
            Généré le <?php echo date('d/m/Y à H:i'); ?>
            <?php if (!empty($search)): ?>
                <br>Recherche : "<?php echo htmlspecialchars($search); ?>"
            <?php endif; ?>
            <?php if (!empty($alerte_stock)): ?>
                <br>Filtre stock : <?php echo $alerte_stock == 'faible' ? 'Stock faible' : 'En rupture'; ?>
            <?php endif; ?>
            <?php if ($actif_filter !== ''): ?>
                <br>Statut : <?php echo $actif_filter == '1' ? 'Actifs' : 'Inactifs'; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (count($articles) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Désignation</th>
                    <th class="text-right">Stock initial</th>
                    <th class="text-right">Entrées</th>
                    <th class="text-right">Sorties</th>
                    <th class="text-right">Retours</th>
                    <th class="text-right">Disponible</th>
                    <th class="text-right">Min</th>
                    <th class="text-right">Max</th>
                    <th class="text-center">Statut Stock</th>
                    <th class="text-center">Actif</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <?php
                    $stock_label = 'OK';
                    $stock_class = 'bg-success';
                    
                    if ($article['qte_disponible'] <= 0) {
                        $stock_label = 'Rupture';
                        $stock_class = 'bg-danger';
                    } elseif ($article['stock_min'] > 0 && $article['qte_disponible'] < $article['stock_min']) {
                        $stock_label = 'Faible';
                        $stock_class = 'bg-warning';
                    } elseif ($article['stock_max'] > 0 && $article['qte_disponible'] > $article['stock_max']) {
                        $stock_label = 'Élevé';
                        $stock_class = 'bg-info';
                    }
                    
                    $actif_class = $article['actif'] ? 'bg-success' : 'bg-secondary';
                    $actif_label = $article['actif'] ? 'Actif' : 'Inactif';
                    ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($article['code_article']); ?></code></td>
                        <td><?php echo htmlspecialchars($article['designation']); ?></td>
                        <td class="text-right"><?php echo number_format($article['stock_initial'], 2, ',', ' '); ?></td>
                        <td class="text-right"><?php echo number_format($article['qte_entree'], 2, ',', ' '); ?></td>
                        <td class="text-right"><?php echo number_format($article['qte_sortie'], 2, ',', ' '); ?></td>
                        <td class="text-right"><?php echo number_format($article['qte_retour'], 2, ',', ' '); ?></td>
                        <td class="text-right"><strong><?php echo number_format($article['qte_disponible'], 2, ',', ' '); ?></strong></td>
                        <td class="text-right"><?php echo number_format($article['stock_min'], 2, ',', ' '); ?></td>
                        <td class="text-right"><?php echo number_format($article['stock_max'], 2, ',', ' '); ?></td>
                        <td class="text-center"><span class="badge <?php echo $stock_class; ?>"><?php echo $stock_label; ?></span></td>
                        <td class="text-center"><span class="badge <?php echo $actif_class; ?>"><?php echo $actif_label; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            Total : <?php echo count($articles); ?> article(s) | Système de Gestion de Stock
        </div>
    <?php else: ?>
        <p style="text-align: center; margin-top: 50px; color: #666;">
            Aucun article trouvé
        </p>
    <?php endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();

// Utiliser TCPDF si disponible, sinon générer un HTML simple
if (class_exists('TCPDF')) {
    require_once(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php');
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Système de Gestion de Stock');
    $pdf->SetAuthor('Système de Gestion de Stock');
    $pdf->SetTitle('Liste des articles');
    $pdf->SetSubject('Liste des articles');
    
    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Output('articles_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
} else {
    // Fallback : générer un HTML avec option d'impression PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="articles_' . date('Y-m-d_H-i-s') . '.pdf"');
    
    // Convertir HTML en PDF avec des outils système ou afficher le HTML
    echo $html;
}
exit;