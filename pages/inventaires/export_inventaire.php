<?php
/**
 * Exportation de la liste des articles d'un inventaire
 * Supports : CSV, Excel (HTML), PDF (via DomPDF)
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('inventaires', 'view');

$db = Database::getInstance();

// Récupérer les paramètres
$inventaire_id = intval($_POST['inventaire_id'] ?? 0);
$format = $_POST['format'] ?? 'excel';
$filtre = $_POST['filtre'] ?? 'tous';
$colonnes = $_POST['colonnes'] ?? [];

// Vérifier l'inventaire
$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $inventaire_id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

// Récupérer les lignes d'inventaire avec filtre
$sql = "SELECT * FROM ligne_inventaires WHERE inventaire_id = :id";
$params = [':id' => $inventaire_id];

// Appliquer le filtre
switch ($filtre) {
    case 'avec_ecart':
        $sql .= " AND ecart != 0";
        break;
    case 'positifs':
        $sql .= " AND ecart > 0";
        break;
    case 'negatifs':
        $sql .= " AND ecart < 0";
        break;
    case 'conformes':
        $sql .= " AND ecart = 0";
        break;
}

$sql .= " ORDER BY code_article";

$db->prepare($sql);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$lignes = $db->fetchAll();

// Définir le nom du fichier
$filename = 'inventaire_' . $inventaire['reference'] . '_' . date('Y-m-d_H-i');

if ($format === 'csv') {
    // Format CSV
    $filename .= '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    $csv_headers = [];
    if (in_array('code', $colonnes)) $csv_headers[] = 'Code article';
    if (in_array('designation', $colonnes)) $csv_headers[] = 'Désignation';
    if (in_array('qte_theorique', $colonnes)) $csv_headers[] = 'Qté théorique';
    if (in_array('comptage1', $colonnes)) $csv_headers[] = 'Comptage 1';
    if (in_array('comptage2', $colonnes)) $csv_headers[] = 'Comptage 2';
    if (in_array('comptage3', $colonnes)) $csv_headers[] = 'Comptage 3';
    if (in_array('moyenne', $colonnes)) $csv_headers[] = 'Moyenne';
    if (in_array('ecart', $colonnes)) $csv_headers[] = 'Écart';
    if (in_array('observation', $colonnes)) $csv_headers[] = 'Observation';
    
    // Ajouter les informations de l'inventaire
    fputcsv($output, ['Inventaire: ' . $inventaire['reference']]);
    fputcsv($output, ['Date: ' . date('d/m/Y', strtotime($inventaire['date_debut']))]);
    fputcsv($output, ['Exporté le: ' . date('d/m/Y H:i')]);
    fputcsv($output, []); // Ligne vide
    fputcsv($output, $csv_headers);
    
    // Données
    foreach ($lignes as $ligne) {
        $row = [];
        if (in_array('code', $colonnes)) $row[] = $ligne['code_article'];
        if (in_array('designation', $colonnes)) $row[] = $ligne['designation'];
        if (in_array('qte_theorique', $colonnes)) $row[] = $ligne['qte_theorique'];
        if (in_array('comptage1', $colonnes)) $row[] = '';
        if (in_array('comptage2', $colonnes)) $row[] = '';
        if (in_array('comptage3', $colonnes)) $row[] = '';
        if (in_array('moyenne', $colonnes)) $row[] = '';
        if (in_array('ecart', $colonnes)) $row[] = $ligne['ecart'];
        if (in_array('observation', $colonnes)) $row[] = '';
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'excel') {
    // Format Excel (HTML table pour simuler Excel)
    $filename .= '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #f2f2f2; font-weight: bold; text-align: center; padding: 8px; border: 1px solid #ddd; }
            td { padding: 6px; border: 1px solid #ddd; }
            .title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
            .info { font-size: 12px; color: #666; margin-bottom: 5px; }
        </style>
    </head>
    <body>';
    
    echo '<div class="title">Inventaire: ' . htmlspecialchars($inventaire['reference']) . '</div>';
    echo '<div class="info">Date: ' . date('d/m/Y', strtotime($inventaire['date_debut'])) . '</div>';
    echo '<div class="info">Exporté le: ' . date('d/m/Y H:i') . '</div>';
    echo '<br>';
    
    echo '<table border="1">';
    echo '<tr>';
    if (in_array('code', $colonnes)) echo '<th>Code article</th>';
    if (in_array('designation', $colonnes)) echo '<th>Désignation</th>';
    if (in_array('qte_theorique', $colonnes)) echo '<th>Qté théorique</th>';
    if (in_array('comptage1', $colonnes)) echo '<th>Comptage 1</th>';
    if (in_array('comptage2', $colonnes)) echo '<th>Comptage 2</th>';
    if (in_array('comptage3', $colonnes)) echo '<th>Comptage 3</th>';
    if (in_array('moyenne', $colonnes)) echo '<th>Moyenne</th>';
    if (in_array('ecart', $colonnes)) echo '<th>Écart</th>';
    if (in_array('observation', $colonnes)) echo '<th>Observation</th>';
    echo '</tr>';
    
    foreach ($lignes as $ligne) {
        echo '<tr>';
        if (in_array('code', $colonnes)) echo '<td>' . htmlspecialchars($ligne['code_article']) . '</td>';
        if (in_array('designation', $colonnes)) echo '<td>' . htmlspecialchars($ligne['designation']) . '</td>';
        if (in_array('qte_theorique', $colonnes)) echo '<td>' . $ligne['qte_theorique'] . '</td>';
        if (in_array('comptage1', $colonnes)) echo '<td></td>';
        if (in_array('comptage2', $colonnes)) echo '<td></td>';
        if (in_array('comptage3', $colonnes)) echo '<td></td>';
        if (in_array('moyenne', $colonnes)) echo '<td></td>';
        if (in_array('ecart', $colonnes)) echo '<td>' . $ligne['ecart'] . '</td>';
        if (in_array('observation', $colonnes)) echo '<td></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
    
} elseif ($format === 'pdf') {
    // Format PDF avec DomPDF
    try {
        // Inclure l'autoload de Composer
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        // Vérifier si DomPDF est disponible
        if (!class_exists('Dompdf\Dompdf')) {
            throw new Exception('DomPDF n\'est pas installé. Installez-le avec: composer require dompdf/dompdf');
        }
        
        // Créer le contenu HTML
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Inventaire ' . htmlspecialchars($inventaire['reference']) . '</title>
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { font-size: 18px; color: #333; margin-bottom: 5px; }
                .header .info { font-size: 11px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th { background-color: #f8f9fa; color: #333; font-weight: bold; padding: 8px; border: 1px solid #dee2e6; text-align: left; }
                td { padding: 6px; border: 1px solid #dee2e6; }
                .footer { margin-top: 30px; font-size: 10px; color: #777; text-align: center; }
                .page-break { page-break-after: always; }
                .total-row { font-weight: bold; background-color: #e9ecef; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .small { font-size: 10px; }
                .alert { background-color: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 4px; font-size: 11px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Inventaire: ' . htmlspecialchars($inventaire['reference']) . '</h1>
                <div class="info">
                    Date de l\'inventaire: ' . date('d/m/Y', strtotime($inventaire['date_debut'])) . '<br>
                    Exporté le: ' . date('d/m/Y H:i') . '<br>
                    Nombre d\'articles: ' . count($lignes) . '
                </div>
            </div>
            
            <div class="alert">
                <strong>Instructions:</strong> Remplissez les colonnes "Comptage 1", "Comptage 2" et "Comptage 3" avec les quantités réellement comptées. La colonne "Observation" peut être utilisée pour noter des remarques.
            </div>
            
            <table>
                <thead>
                    <tr>';
        
        if (in_array('code', $colonnes)) $html .= '<th width="15%">Code article</th>';
        if (in_array('designation', $colonnes)) $html .= '<th width="30%">Désignation</th>';
        if (in_array('qte_theorique', $colonnes)) $html .= '<th width="10%" class="text-right">Qté théorique</th>';
        if (in_array('comptage1', $colonnes)) $html .= '<th width="10%" class="text-center">Comptage 1</th>';
        if (in_array('comptage2', $colonnes)) $html .= '<th width="10%" class="text-center">Comptage 2</th>';
        if (in_array('comptage3', $colonnes)) $html .= '<th width="10%" class="text-center">Comptage 3</th>';
        if (in_array('moyenne', $colonnes)) $html .= '<th width="10%" class="text-right">Moyenne</th>';
        if (in_array('ecart', $colonnes)) $html .= '<th width="10%" class="text-right">Écart</th>';
        if (in_array('observation', $colonnes)) $html .= '<th width="15%">Observation</th>';
        
        $html .= '
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($lignes as $ligne) {
            $html .= '<tr>';
            if (in_array('code', $colonnes)) $html .= '<td>' . htmlspecialchars($ligne['code_article']) . '</td>';
            if (in_array('designation', $colonnes)) $html .= '<td>' . htmlspecialchars($ligne['designation']) . '</td>';
            if (in_array('qte_theorique', $colonnes)) $html .= '<td class="text-right">' . number_format($ligne['qte_theorique'], 2, ',', ' ') . '</td>';
            if (in_array('comptage1', $colonnes)) $html .= '<td class="text-center"></td>';
            if (in_array('comptage2', $colonnes)) $html .= '<td class="text-center"></td>';
            if (in_array('comptage3', $colonnes)) $html .= '<td class="text-center"></td>';
            if (in_array('moyenne', $colonnes)) $html .= '<td class="text-right"></td>';
            if (in_array('ecart', $colonnes)) {
                $ecart = floatval($ligne['ecart']);
                $ecart_class = $ecart < 0 ? 'color: red;' : ($ecart > 0 ? 'color: green;' : '');
                $html .= '<td class="text-right" style="' . $ecart_class . '">' . ($ecart > 0 ? '+' : '') . number_format($ecart, 2, ',', ' ') . '</td>';
            }
            if (in_array('observation', $colonnes)) $html .= '<td></td>';
            $html .= '</tr>';
        }
        
        // Ajouter les lignes vides pour les comptages manuels
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <hr>
                <p class="small">Document généré par le système de gestion de stock</p>
                <p class="small">Page 1/1</p>
            </div>
        </body>
        </html>';
        
        // Créer l'instance DomPDF
        $dompdf = new \Dompdf\Dompdf();
        
        // Options DomPDF
        $options = $dompdf->getOptions();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf->setOptions($options);
        
        // Charger le HTML
        $dompdf->loadHtml($html);
        
        // Définir le format de papier (A4 paysage pour plus de largeur)
        $dompdf->setPaper('A4', 'landscape');
        
        // Rendre le PDF
        $dompdf->render();
        
        // Nom du fichier
        $filename .= '.pdf';
        
        // Envoyer le PDF au navigateur
        $dompdf->stream($filename, [
            'Attachment' => true,
            'compress' => true
        ]);
        
        exit;
        
    } catch (Exception $e) {
        // En cas d'erreur, rediriger avec un message
        $_SESSION['error'] = 'Erreur lors de la génération du PDF: ' . $e->getMessage();
        header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
        exit;
    }
} else {
    // Format non supporté
    $_SESSION['error'] = 'Format d\'export non supporté. Utilisez Excel, CSV ou PDF.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
    exit;
}