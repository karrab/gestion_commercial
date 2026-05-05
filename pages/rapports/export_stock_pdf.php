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
$filtre = $_GET['filtre'] ?? 'tous';
$search = trim($_GET['search'] ?? '');

// ===========================================
// RÉCUPÉRATION DES DONNÉES
// ===========================================
$articles = [];
$stats = [
    'total_articles' => 0,
    'rupture' => 0,
    'faible' => 0,
    'normal' => 0,
    'eleve' => 0,
    'total_stock' => 0
];

try {
    // 1. Récupérer TOUS les articles actifs
    $sql_base = "SELECT * FROM articles WHERE actif = 1";
    $stmt_base = $db->query($sql_base);
    $all_articles = $stmt_base->fetchAll();
    
    // 2. Filtrer localement en PHP
    $filtered_articles = [];
    
    if (!empty($search)) {
        $search_lower = strtolower($search);
        foreach ($all_articles as $article) {
            $code_lower = strtolower($article['code_article']);
            $designation_lower = strtolower($article['designation']);
            
            if (strpos($code_lower, $search_lower) !== false || 
                strpos($designation_lower, $search_lower) !== false) {
                $filtered_articles[] = $article;
            }
        }
    } else {
        $filtered_articles = $all_articles;
    }
    
    // 3. Appliquer le filtre d'état
    $filtered_by_state = [];
    foreach ($filtered_articles as $article) {
        $qte = $article['qte_disponible'];
        $min = $article['stock_min'];
        $max = $article['stock_max'];
        
        $include = false;
        
        if ($filtre == 'tous') {
            $include = true;
        } elseif ($filtre == 'rupture' && $qte <= 0) {
            $include = true;
        } elseif ($filtre == 'faible' && $qte > 0 && $qte <= $min) {
            $include = true;
        } elseif ($filtre == 'normal' && $qte > $min && $qte < $max) {
            $include = true;
        } elseif ($filtre == 'eleve' && $qte >= $max) {
            $include = true;
        }
        
        if ($include) {
            // Calculer l'état
            if ($qte <= 0) {
                $article['etat_stock'] = 'rupture';
            } elseif ($qte > 0 && $qte <= $min) {
                $article['etat_stock'] = 'faible';
            } elseif ($qte >= $max) {
                $article['etat_stock'] = 'eleve';
            } else {
                $article['etat_stock'] = 'normal';
            }
            
            $filtered_by_state[] = $article;
        }
    }
    
    // 4. Trier les articles
    usort($filtered_by_state, function($a, $b) {
        $order = ['rupture' => 1, 'faible' => 2, 'normal' => 3, 'eleve' => 4];
        $order_a = $order[$a['etat_stock']] ?? 5;
        $order_b = $order[$b['etat_stock']] ?? 5;
        
        if ($order_a !== $order_b) {
            return $order_a - $order_b;
        }
        
        return $a['qte_disponible'] <=> $b['qte_disponible'];
    });
    
    $articles = $filtered_by_state;
    
    // 5. Calculer les statistiques
    $stats['total_articles'] = count($filtered_by_state);
    $stats['total_stock'] = array_sum(array_column($filtered_by_state, 'qte_disponible'));
    
    foreach ($filtered_by_state as $article) {
        $qte = $article['qte_disponible'];
        $min = $article['stock_min'];
        $max = $article['stock_max'];
        
        if ($qte <= 0) {
            $stats['rupture']++;
        } elseif ($qte > 0 && $qte <= $min) {
            $stats['faible']++;
        } elseif ($qte > $min && $qte < $max) {
            $stats['normal']++;
        } elseif ($qte >= $max) {
            $stats['eleve']++;
        }
    }
    
} catch (Exception $e) {
    error_log("Erreur export PDF: " . $e->getMessage());
}

// Nom du filtre pour le titre
$filtre_texte = [
    'tous' => 'Tous les articles',
    'rupture' => 'En rupture de stock',
    'faible' => 'Stock faible',
    'normal' => 'Stock normal',
    'eleve' => 'Stock élevé'
];

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
        <title>Rapport État du Stock</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .header h1 { color: #2c3e50; margin: 0; }
            .info { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
            .stats { display: flex; justify-content: space-around; margin-bottom: 20px; }
            .stat-box { text-align: center; padding: 15px; border-radius: 5px; }
            .stat-box.total { background: #e3f2fd; }
            .stat-box.rupture { background: #ffebee; color: #d32f2f; }
            .stat-box.faible { background: #fff3e0; color: #f57c00; }
            .stat-box.normal { background: #e8f5e9; color: #388e3c; }
            .stat-box.eleve { background: #e1f5fe; color: #0288d1; }
            .stat-label { font-size: 11px; margin-bottom: 5px; }
            .stat-value { font-size: 24px; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10px; }
            th { background-color: #4CAF50; color: white; padding: 8px; text-align: left; border: 1px solid #ddd; }
            td { padding: 6px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .total-row { font-weight: bold; background-color: #e8f5e9 !important; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; }
            .badge-rupture { background: #ffebee; color: #d32f2f; }
            .badge-faible { background: #fff3e0; color: #f57c00; }
            .badge-normal { background: #e8f5e9; color: #388e3c; }
            .badge-eleve { background: #e1f5fe; color: #0288d1; }
            .footer { margin-top: 30px; font-size: 10px; color: #7f8c8d; text-align: center; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>RAPPORT - ÉTAT DU STOCK</h1>
        </div>
        
        <div class="info">
            <p><strong>Filtre appliqué:</strong> ' . ($filtre_texte[$filtre] ?? 'Tous') . '</p>';
    if (!empty($search)) {
        echo '<p><strong>Recherche:</strong> "' . htmlspecialchars($search) . '"</p>';
    }
    echo '<p><strong>Généré le:</strong> ' . date('d/m/Y H:i') . '</p>
        </div>
        
        <div class="stats">
            <div class="stat-box total">
                <div class="stat-label">Total Articles</div>
                <div class="stat-value">' . number_format($stats['total_articles'], 0, ',', ' ') . '</div>
            </div>
            <div class="stat-box rupture">
                <div class="stat-label">Rupture</div>
                <div class="stat-value">' . number_format($stats['rupture'], 0, ',', ' ') . '</div>
            </div>
            <div class="stat-box faible">
                <div class="stat-label">Faible</div>
                <div class="stat-value">' . number_format($stats['faible'], 0, ',', ' ') . '</div>
            </div>
            <div class="stat-box normal">
                <div class="stat-label">Normal</div>
                <div class="stat-value">' . number_format($stats['normal'], 0, ',', ' ') . '</div>
            </div>
            <div class="stat-box eleve">
                <div class="stat-label">Élevé</div>
                <div class="stat-value">' . number_format($stats['eleve'], 0, ',', ' ') . '</div>
            </div>
        </div>';
    
    if (empty($articles)) {
        echo '<div style="text-align: center; padding: 40px; color: #999;"><p>Aucun article à afficher</p></div>';
    } else {
        echo '<table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Désignation</th>
                        <th class="text-center">Initial</th>
                        <th class="text-center">Entrées</th>
                        <th class="text-center">Sorties</th>
                        <th class="text-center">Retours</th>
                        <th class="text-center">Disponible</th>
                        <th class="text-center">Min</th>
                        <th class="text-center">Max</th>
                        <th class="text-center">État</th>
                    </tr>
                </thead>
                <tbody>';
        
        $total_initial = 0;
        $total_entrees = 0;
        $total_sorties = 0;
        $total_retours = 0;
        $total_disponible = 0;
        
        foreach($articles as $a) {
            $badge_class = 'badge-normal';
            $badge_text = 'Normal';
            
            if ($a['etat_stock'] == 'rupture') {
                $badge_class = 'badge-rupture';
                $badge_text = 'Rupture';
            } elseif ($a['etat_stock'] == 'faible') {
                $badge_class = 'badge-faible';
                $badge_text = 'Faible';
            } elseif ($a['etat_stock'] == 'eleve') {
                $badge_class = 'badge-eleve';
                $badge_text = 'Élevé';
            }
            
            echo '<tr>
                    <td>' . htmlspecialchars($a['code_article']) . '</td>
                    <td>' . htmlspecialchars($a['designation']) . '</td>
                    <td class="text-center">' . number_format($a['stock_initial'], 0, ',', ' ') . '</td>
                    <td class="text-center">' . number_format($a['qte_entree'], 0, ',', ' ') . '</td>
                    <td class="text-center">' . number_format($a['qte_sortie'], 0, ',', ' ') . '</td>
                    <td class="text-center">' . number_format($a['qte_retour'], 0, ',', ' ') . '</td>
                    <td class="text-center"><strong>' . number_format($a['qte_disponible'], 0, ',', ' ') . '</strong></td>
                    <td class="text-center">' . ($a['stock_min'] > 0 ? number_format($a['stock_min'], 0, ',', ' ') : '-') . '</td>
                    <td class="text-center">' . ($a['stock_max'] > 0 ? number_format($a['stock_max'], 0, ',', ' ') : '-') . '</td>
                    <td class="text-center"><span class="badge ' . $badge_class . '">' . $badge_text . '</span></td>
                </tr>';
            
            $total_initial += $a['stock_initial'];
            $total_entrees += $a['qte_entree'];
            $total_sorties += $a['qte_sortie'];
            $total_retours += $a['qte_retour'];
            $total_disponible += $a['qte_disponible'];
        }
        
        echo '</tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>TOTAUX:</strong></td>
                        <td class="text-center"><strong>' . number_format($total_initial, 0, ',', ' ') . '</strong></td>
                        <td class="text-center"><strong>' . number_format($total_entrees, 0, ',', ' ') . '</strong></td>
                        <td class="text-center"><strong>' . number_format($total_sorties, 0, ',', ' ') . '</strong></td>
                        <td class="text-center"><strong>' . number_format($total_retours, 0, ',', ' ') . '</strong></td>
                        <td class="text-center"><strong>' . number_format($total_disponible, 0, ',', ' ') . '</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>';
    }
    
    echo '<div class="footer">
            <p>Document généré automatiquement par le système de gestion</p>
            <p><em>Pour une meilleure présentation en PDF, installez TCPDF: composer require tecnickcom/tcpdf</em></p>
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

// Classe TCPDF personnalisée avec en-tête et pied de page
class MYPDF extends TCPDF {
    private $filtre_text;
    private $search_text;
    
    public function setFilterText($text) {
        $this->filtre_text = $text;
    }
    
    public function setSearchText($text) {
        $this->search_text = $text;
    }
    
    // En-tête personnalisé
    public function Header() {
        // Logo (si disponible)
        $logo_path = realpath(__DIR__ . '/../../') . '/assets/images/logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 30, 0, 'PNG');
        }
        
        // Titre principal
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 15, 'RAPPORT - ÉTAT DU STOCK', 0, 1, 'C');
        
        // Ligne de séparation
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(59, 89, 152);
        $this->Line(15, $this->GetY(), $this->GetPageWidth() - 15, $this->GetY());
        $this->Ln(3);
        
        // Informations du rapport
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(0, 0, 0);
        
        if (!empty($this->filtre_text)) {
            $this->Cell(0, 6, 'Filtre appliqué: ' . $this->filtre_text, 0, 1);
        }
        
        if (!empty($this->search_text)) {
            $this->Cell(0, 6, 'Recherche: "' . $this->search_text . '"', 0, 1);
        }
        
        $this->Cell(0, 6, 'Généré le: ' . date('d/m/Y à H:i'), 0, 1);
        $this->Ln(3);
    }
    
    // Pied de page personnalisé
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(127, 140, 141);
        
        // Numéro de page
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
        $this->Ln(4);
        
        // Texte du pied de page
        $this->Cell(0, 5, 'Document généré automatiquement par le système de gestion', 0, 0, 'C');
    }
}

// Créer un nouveau PDF avec la classe personnalisée
$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Information du document
$pdf->SetCreator('Système de Gestion');
$pdf->SetAuthor('Système de Gestion');
$pdf->SetTitle('Rapport État du Stock');
$pdf->SetSubject('Rapport État du Stock');

// Configurer les textes pour l'en-tête
$pdf->setFilterText($filtre_texte[$filtre] ?? 'Tous');
if (!empty($search)) {
    $pdf->setSearchText($search);
}

// Activer les en-têtes et pieds de page automatiques
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Marges (avec espace pour l'en-tête)
$pdf->SetMargins(10, 50, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 20);

// Ajouter une page
$pdf->AddPage();

// Statistiques
$pdf->SetFillColor(227, 242, 253);
$pdf->SetFont('helvetica', 'B', 9);
$w_stat = ($pdf->GetPageWidth() - 20) / 6;

$pdf->Cell($w_stat, 8, 'Total Articles', 1, 0, 'C', true);
$pdf->SetFillColor(255, 235, 238);
$pdf->Cell($w_stat, 8, 'Rupture', 1, 0, 'C', true);
$pdf->SetFillColor(255, 243, 224);
$pdf->Cell($w_stat, 8, 'Faible', 1, 0, 'C', true);
$pdf->SetFillColor(232, 245, 233);
$pdf->Cell($w_stat, 8, 'Normal', 1, 0, 'C', true);
$pdf->SetFillColor(225, 245, 254);
$pdf->Cell($w_stat, 8, 'Élevé', 1, 0, 'C', true);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell($w_stat, 8, 'Stock Total', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(255, 255, 255);
$pdf->Cell($w_stat, 8, number_format($stats['total_articles'], 0, ',', ' '), 1, 0, 'C', true);
$pdf->SetTextColor(211, 47, 47);
$pdf->Cell($w_stat, 8, number_format($stats['rupture'], 0, ',', ' '), 1, 0, 'C', true);
$pdf->SetTextColor(245, 124, 0);
$pdf->Cell($w_stat, 8, number_format($stats['faible'], 0, ',', ' '), 1, 0, 'C', true);
$pdf->SetTextColor(56, 142, 60);
$pdf->Cell($w_stat, 8, number_format($stats['normal'], 0, ',', ' '), 1, 0, 'C', true);
$pdf->SetTextColor(2, 136, 209);
$pdf->Cell($w_stat, 8, number_format($stats['eleve'], 0, ',', ' '), 1, 0, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell($w_stat, 8, number_format($stats['total_stock'], 0, ',', ' '), 1, 1, 'C', true);

$pdf->Ln(5);

// Tableau des articles
$pdf->SetFillColor(59, 89, 152);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(59, 89, 152);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('helvetica', 'B', 8);

// En-tête du tableau
$header = array('Code', 'Désignation', 'Initial', 'Entrées', 'Sorties', 'Retours', 'Disponible', 'Min', 'Max', 'État');
$w = array(22, 85, 18, 18, 18, 18, 20, 15, 15, 20);

for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Données
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 7);

$fill = false;
$total_initial = 0;
$total_entrees = 0;
$total_sorties = 0;
$total_retours = 0;
$total_disponible = 0;

foreach($articles as $a) {
    // Déterminer la couleur de l'état
    $etat_text = 'Normal';
    if ($a['etat_stock'] == 'rupture') {
        $etat_text = 'Rupture';
    } elseif ($a['etat_stock'] == 'faible') {
        $etat_text = 'Faible';
    } elseif ($a['etat_stock'] == 'eleve') {
        $etat_text = 'Élevé';
    }
    
    $pdf->Cell($w[0], 6, $a['code_article'], 'LR', 0, 'C', $fill);
    $pdf->Cell($w[1], 6, substr($a['designation'], 0, 45), 'LR', 0, 'L', $fill);
    $pdf->Cell($w[2], 6, number_format($a['stock_initial'], 0, ',', ' '), 'LR', 0, 'C', $fill);
    $pdf->Cell($w[3], 6, number_format($a['qte_entree'], 0, ',', ' '), 'LR', 0, 'C', $fill);
    $pdf->Cell($w[4], 6, number_format($a['qte_sortie'], 0, ',', ' '), 'LR', 0, 'C', $fill);
    $pdf->Cell($w[5], 6, number_format($a['qte_retour'], 0, ',', ' '), 'LR', 0, 'C', $fill);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell($w[6], 6, number_format($a['qte_disponible'], 0, ',', ' '), 'LR', 0, 'C', $fill);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell($w[7], 6, $a['stock_min'] > 0 ? number_format($a['stock_min'], 0, ',', ' ') : '-', 'LR', 0, 'C', $fill);
    $pdf->Cell($w[8], 6, $a['stock_max'] > 0 ? number_format($a['stock_max'], 0, ',', ' ') : '-', 'LR', 0, 'C', $fill);
    $pdf->Cell($w[9], 6, $etat_text, 'LR', 0, 'C', $fill);
    $pdf->Ln();
    
    $total_initial += $a['stock_initial'];
    $total_entrees += $a['qte_entree'];
    $total_sorties += $a['qte_sortie'];
    $total_retours += $a['qte_retour'];
    $total_disponible += $a['qte_disponible'];
    
    $fill = !$fill;
}

// Fermeture du tableau
$pdf->Cell(array_sum($w), 0, '', 'T');
$pdf->Ln(5);

// Totaux
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell($w[0] + $w[1], 8, 'TOTAUX:', 1, 0, 'R', true);
$pdf->Cell($w[2], 8, number_format($total_initial, 0, ',', ' '), 1, 0, 'C', true);
$pdf->Cell($w[3], 8, number_format($total_entrees, 0, ',', ' '), 1, 0, 'C', true);
$pdf->Cell($w[4], 8, number_format($total_sorties, 0, ',', ' '), 1, 0, 'C', true);
$pdf->Cell($w[5], 8, number_format($total_retours, 0, ',', ' '), 1, 0, 'C', true);
$pdf->Cell($w[6], 8, number_format($total_disponible, 0, ',', ' '), 1, 0, 'C', true);
$pdf->Cell($w[7] + $w[8] + $w[9], 8, '', 1, 1, 'C', true);

// Nom du fichier PDF
$filename = 'rapport_stock_' . date('Ymd_His') . '.pdf';

// Sortie du PDF (affichage dans le navigateur)
$pdf->Output($filename, 'I');
exit;