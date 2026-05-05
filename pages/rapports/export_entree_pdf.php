<?php
// Ne pas inclure le header.php car il envoie du HTML
// Au lieu de cela, initialiser manuellement ce dont on a besoin

// Activer le buffering de sortie pour capturer toute sortie non désirée
ob_start();

// Chemin de base
$base_dir = realpath(__DIR__ . '/../../');

// Inclure la configuration et l'autoloader
require_once $base_dir . '/config/config.php';
require_once $base_dir . '/vendor/autoload.php';

// Initialiser la session si nécessaire
session_start();

// Initialiser la base de données
require_once $base_dir . '/classes/Database.php';
$db = Database::getInstance();

// Initialiser l'authentification
require_once $base_dir . '/classes/Auth.php';
$auth = new Auth();

// Vérifier la permission sans utiliser requirePermission qui pourrait générer du HTML
if (!$auth->hasPermission('rapports', 'view')) {
    // Nettoyer le buffer et rediriger
    ob_end_clean();
    header('HTTP/1.0 403 Forbidden');
    echo 'Accès interdit';
    exit;
}

// Récupérer les paramètres
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$fournisseur_id = $_GET['fournisseur_id'] ?? '';

// Récupérer les données
$sql = "SELECT e.*, f.nom_complet as fournisseur,
               (SELECT COUNT(*) FROM ligne_entrees WHERE entree_id = e.id) as nb_articles,
               (SELECT SUM(qte_entree) FROM ligne_entrees WHERE entree_id = e.id) as qte_totale
        FROM entrees e
        INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
        WHERE e.date BETWEEN :date_debut AND :date_fin";

$params = [':date_debut' => $date_debut, ':date_fin' => $date_fin];

if (!empty($fournisseur_id)) {
    $sql .= " AND e.fournisseur_id = :fournisseur_id";
    $params[':fournisseur_id'] = $fournisseur_id;
}

$sql .= " ORDER BY e.date DESC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$entrees = $stmt->fetchAll();

// Calculer le total
$total_qte = 0;
foreach ($entrees as $e) {
    $total_qte += $e['qte_totale'];
}

// Nom du fournisseur pour le titre
$fournisseur_nom = "Tous les fournisseurs";
if (!empty($fournisseur_id)) {
    $db->prepare("SELECT nom_complet FROM fournisseurs WHERE id = :id");
    $db->bind(':id', $fournisseur_id);
    $fournisseur = $db->fetch();
    if ($fournisseur) {
        $fournisseur_nom = $fournisseur['nom_complet'];
    }
}

// Nettoyer le buffer de sortie avant de générer le PDF
ob_end_clean();

// Inclure TCPDF
try {
    // Essayer différents chemins pour TCPDF
    $tcpdf_paths = [
        $base_dir . '/vendor/tecnickcom/tcpdf/tcpdf.php',
        $base_dir . '/vendor/tcpdf/tcpdf.php',
        $base_dir . '/tcpdf/tcpdf.php'
    ];
    
    $tcpdf_loaded = false;
    foreach ($tcpdf_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $tcpdf_loaded = true;
            break;
        }
    }
    
    if (!$tcpdf_loaded) {
        throw new Exception('TCPDF non trouvé. Installez-le avec: composer require tecnickcom/tcpdf');
    }
} catch (Exception $e) {
    // Fallback: générer un HTML pour impression
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Rapport des Entrées</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .header h1 { color: #2c3e50; margin: 0; }
            .info { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #4CAF50; color: white; padding: 12px; text-align: left; border: 1px solid #ddd; }
            td { padding: 10px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .total { font-weight: bold; background-color: #e8f5e9 !important; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .footer { margin-top: 30px; font-size: 12px; color: #7f8c8d; text-align: center; }
            .error { color: #dc3545; padding: 20px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>RAPPORT DES ENTREES</h1>
        </div>
        
        <div class="info">
            <p><strong>Période:</strong> ' . date('d/m/Y', strtotime($date_debut)) . ' - ' . date('d/m/Y', strtotime($date_fin)) . '</p>
            <p><strong>Fournisseur:</strong> ' . htmlspecialchars($fournisseur_nom) . '</p>
            <p><strong>Généré le:</strong> ' . date('d/m/Y H:i') . '</p>
        </div>';
    
    if (empty($entrees)) {
        echo '<div class="error"><p>Aucune entrée trouvée pour la période sélectionnée.</p></div>';
    } else {
        echo '<table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Fournisseur</th>
                        <th class="text-center">Articles</th>
                        <th class="text-right">Quantité</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach($entrees as $e) {
            echo '<tr>
                    <td>' . $e['id'] . '</td>
                    <td>' . date('d/m/Y', strtotime($e['date'])) . '</td>
                    <td>' . htmlspecialchars($e['fournisseur']) . '</td>
                    <td class="text-center">' . $e['nb_articles'] . '</td>
                    <td class="text-right">' . number_format($e['qte_totale'], 2, ',', ' ') . '</td>
                </tr>';
        }
        
        echo '</tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="3" class="text-right"><strong>TOTAL GÉNÉRAL:</strong></td>
                        <td class="text-center"><strong>' . count($entrees) . '</strong></td>
                        <td class="text-right"><strong>' . number_format($total_qte, 2, ',', ' ') . '</strong></td>
                    </tr>
                </tfoot>
            </table>';
    }
    
    echo '<div class="footer">
            <p>Document généré automatiquement par le système de gestion</p>
            <p><em>Pour une meilleure présentation, installez TCPDF: composer require tecnickcom/tcpdf</em></p>
        </div>
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 1000);
            };
        </script>
    </body>
    </html>';
    exit;
}

// Créer un nouveau PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Information du document
$pdf->SetCreator('Système de Gestion');
$pdf->SetAuthor('Système de Gestion');
$pdf->SetTitle('Rapport des entrées');
$pdf->SetSubject('Rapport des entrées');

// Désactiver les en-têtes et pieds de page automatiques
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Marges
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Ajouter une page
$pdf->AddPage();

// Logo (si disponible)
$logo_path = $base_dir . '/assets/images/logo.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 15, 10, 30, 0, 'PNG');
}

// Titre
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, 'RAPPORT DES ENTREES', 0, 1, 'C');
$pdf->Ln(5);

// Ligne de séparation
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), $pdf->GetPageWidth() - 15, $pdf->GetY());
$pdf->Ln(8);

// Informations
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'Période: ' . date('d/m/Y', strtotime($date_debut)) . ' - ' . date('d/m/Y', strtotime($date_fin)), 0, 1);
$pdf->Cell(0, 8, 'Fournisseur: ' . $fournisseur_nom, 0, 1);
$pdf->Cell(0, 8, 'Généré le: ' . date('d/m/Y à H:i'), 0, 1);
$pdf->Ln(5);

// Tableau des entrées
$pdf->SetFillColor(59, 89, 152); // Couleur d'en-tête
$pdf->SetTextColor(255);
$pdf->SetDrawColor(59, 89, 152);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('helvetica', 'B', 11);

// En-tête du tableau
$header = array('N°', 'Date', 'Fournisseur', 'Articles', 'Quantité');
$w = array(15, 25, 110, 25, 35);

for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 10, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Données
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 10);

$fill = false;
foreach($entrees as $e) {
    $pdf->Cell($w[0], 8, $e['id'], 'LR', 0, 'C', $fill);
    $pdf->Cell($w[1], 8, date('d/m/Y', strtotime($e['date'])), 'LR', 0, 'C', $fill);
    $pdf->Cell($w[2], 8, substr($e['fournisseur'], 0, 40), 'LR', 0, 'L', $fill);
    $pdf->Cell($w[3], 8, $e['nb_articles'], 'LR', 0, 'C', $fill);
    $pdf->Cell($w[4], 8, number_format($e['qte_totale'], 2, ',', ' '), 'LR', 0, 'R', $fill);
    $pdf->Ln();
    $fill = !$fill;
}

// Fermeture du tableau
$pdf->Cell(array_sum($w), 0, '', 'T');
$pdf->Ln(10);

// Totaux
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(array_sum($w) - $w[4], 10, 'TOTAL GÉNÉRAL:', 1, 0, 'R', true);
$pdf->Cell($w[4], 10, number_format($total_qte, 2, ',', ' '), 1, 1, 'R', true);

$pdf->Ln(8);

// Résumé
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'Nombre total d\'entrées: ' . count($entrees), 0, 1);
$pdf->Cell(0, 8, 'Quantité totale: ' . number_format($total_qte, 2, ',', ' '), 0, 1);

// Pied de page
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages(), 0, 0, 'C');
$pdf->Ln(5);
$pdf->Cell(0, 10, 'Document généré automatiquement par le système de gestion', 0, 0, 'C');

// Nom du fichier PDF
$filename = 'rapport_entrees_' . date('Ymd_His') . '.pdf';

// Sortie du PDF (téléchargement)
$pdf->Output($filename, 'D');
exit;