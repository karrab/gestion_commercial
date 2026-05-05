<?php
$page_title = 'Historique des mouvements';
require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('mouvements', 'view');
$db = Database::getInstance();
$historique = new HistoriqueArticle();

// Récupération des paramètres de filtrage
$code_article = $_GET['code_article'] ?? '';
$designation = $_GET['designation'] ?? '';
$operation = $_GET['operation'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$article_id = $_GET['article_id'] ?? '';

// Construire les filtres
$filters = [];
if (!empty($code_article)) $filters['code_article'] = $code_article;
if (!empty($designation)) $filters['designation'] = $designation;
if (!empty($operation)) $filters['operation'] = $operation;
if (!empty($date_debut)) $filters['date_debut'] = $date_debut;
if (!empty($date_fin)) $filters['date_fin'] = $date_fin;
if (!empty($article_id)) $filters['article_id'] = $article_id;

// Récupérer TOUS les mouvements (sans pagination pour DataTables)
$mouvements = $historique->getAllWithoutPagination($filters);

// Récupérer les statistiques
$stats = $historique->getStatistiques($date_debut ?: null, $date_fin ?: null);

// Fonction pour formater les nombres sans virgule et zéros inutiles
function formatNumber($number) {
    // Convertir en float pour vérifier si c'est un entier
    $floatVal = floatval($number);
    
    // Si c'est un entier (sans partie décimale)
    if ($floatVal == intval($floatVal)) {
        return number_format($floatVal, 0, '', ' ');
    } else {
        // Pour les nombres décimaux, on garde les décimales mais sans zéros inutiles
        return rtrim(rtrim(number_format($floatVal, 2, ',', ' '), '0'), ',');
    }
}

// Vérifier si c'est une demande d'export PDF
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    // Nettoyer tout buffer de sortie
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Essayer TCPDF d'abord, puis DomPDF si disponible
    $tcpdf_path = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    $dompdf_path = __DIR__ . '/../../vendor/autoload.php';
    
    $pdf_engine = null;
    
    // Essayer TCPDF
    if (file_exists($tcpdf_path)) {
        require_once $tcpdf_path;
        $pdf_engine = 'tcpdf';
    } 
    // Essayer DomPDF
    elseif (file_exists($dompdf_path)) {
        require_once $dompdf_path;
        $pdf_engine = 'dompdf';
    }
    else {
        // Aucun moteur PDF trouvé - rediriger avec erreur
        header('Location: ' . str_replace('export=pdf', '', $_SERVER['REQUEST_URI']) . '&error=no_pdf_engine');
        exit;
    }
    
    // Récupérer les paramètres de l'établissement
    $parametres = $db->query("SELECT * FROM parametres LIMIT 1")->fetch();
    $nom_etablissement = $parametres['nom_etablissement'] ?? 'Stock Matériel';
    $logo_path = !empty($parametres['logo']) ? __DIR__ . '/../../uploads/' . $parametres['logo'] : '';
    $adresse = $parametres['adresse'] ?? '';
    $tel_fixe = $parametres['tel_fixe'] ?? '';
    $email = $parametres['email'] ?? '';
    
    // GÉNÉRATION DU PDF AVEC TCPDF
    if ($pdf_engine === 'tcpdf') {
        // Créer une nouvelle instance de TCPDF en orientation paysage
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuration du document
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Système de Gestion de Stock');
        $pdf->SetTitle('Historique des mouvements de stock');
        $pdf->SetSubject('Rapport des mouvements');
        
        // Supprimer l'en-tête et pied de page par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Marges
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Police par défaut
        $pdf->SetFont('helvetica', '', 9);
        
        // ------ EN-TÊTE ------
        // Logo
        if (!empty($logo_path) && file_exists($logo_path)) {
            $pdf->Image($logo_path, 10, 10, 30, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Titre principal - centré
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetY(15);
        $pdf->Cell(0, 10, $nom_etablissement, 0, 1, 'C');
        
        // Sous-titre
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'Historique des mouvements de stock', 0, 1, 'C');
        
        // Adresse et contacts
        $pdf->SetFont('helvetica', '', 9);
        if (!empty($adresse)) {
            $pdf->Cell(0, 5, $adresse, 0, 1, 'C');
        }
        
        $contact_info = '';
        if (!empty($tel_fixe)) {
            $contact_info .= 'Tél: ' . $tel_fixe;
        }
        if (!empty($email)) {
            if (!empty($contact_info)) $contact_info .= ' | ';
            $contact_info .= 'Email: ' . $email;
        }
        if (!empty($contact_info)) {
            $pdf->Cell(0, 5, $contact_info, 0, 1, 'C');
        }
        
        // Date de génération
        $pdf->Cell(0, 5, 'Généré le: ' . date('d/m/Y H:i'), 0, 1, 'C');
        
        // Ligne de séparation
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
        $pdf->SetY($pdf->GetY() + 5);
        
        // ------ FILTRES APPLIQUÉS ------
        $filtres_text = [];
        if (!empty($date_debut) && !empty($date_fin)) {
            $filtres_text[] = 'Période: ' . date('d/m/Y', strtotime($date_debut)) . ' au ' . date('d/m/Y', strtotime($date_fin));
        }
        if (!empty($code_article)) {
            $filtres_text[] = 'Code article: ' . $code_article;
        }
        if (!empty($designation)) {
            $filtres_text[] = 'Désignation: ' . $designation;
        }
        if (!empty($operation)) {
            $operations = [
                'entree' => 'Entrée',
                'sortie' => 'Sortie',
                'retour' => 'Retour Employé',
                'retour_fournisseur' => 'Retour Fournisseur'
            ];
            $filtres_text[] = 'Opération: ' . ($operations[$operation] ?? $operation);
        }
        
        if (!empty($filtres_text)) {
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 5, 'Filtres appliqués:', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(0, 5, implode(' | ', $filtres_text), 0, 'L');
            $pdf->Ln(3);
        }
        
        // ------ STATISTIQUES ------
        if (!empty($stats)) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Résumé des mouvements', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 5, 'Total des mouvements: ' . count($mouvements), 0, 1, 'L');
            
            foreach ($stats as $stat) {
                $operation_text = '';
                $color = array(0, 0, 0); // Noir par défaut
                
                if ($stat['operation'] === 'entree') {
                    $operation_text = 'Entrées: ';
                    $color = array(40, 167, 69); // Vert
                } elseif ($stat['operation'] === 'sortie') {
                    $operation_text = 'Sorties: ';
                    $color = array(220, 53, 69); // Rouge
                } elseif ($stat['operation'] === 'retour') {
                    $operation_text = 'Retours Employé: ';
                    $color = array(23, 162, 184); // Bleu
                } elseif ($stat['operation'] === 'retour_fournisseur') {
                    $operation_text = 'Retours Fournisseur: ';
                    $color = array(255, 193, 7); // Jaune
                }
                
                $pdf->SetTextColor($color[0], $color[1], $color[2]);
                $pdf->Cell(0, 5, $operation_text . formatNumber($stat['nombre_mouvements']) . 
                          ' mouvements (' . formatNumber($stat['quantite_totale']) . ' unités)', 0, 1, 'L');
            }
            
            $pdf->SetTextColor(0, 0, 0); // Remettre en noir
            $pdf->Ln(5);
        }
        
        // ------ TABLEAU DES MOUVEMENTS ------
        // En-tête du tableau
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.1);
        
        // Largeurs des colonnes
        $w = array(20, 20, 40, 15, 15, 15, 18, 18, 15, 15, 25, 35);
        
        // En-tête
        $header = array('Date', 'Code', 'Désignation', 'Opération', 'Qté_A', 'Qté', 
                       'Stock Avant', 'Stock Après', 'Min', 'Max', 'Utilisateur', 'Commentaire');
        
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Données
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetFillColor(255, 255, 255);
        $fill = false;
        
        if (empty($mouvements)) {
            $pdf->Cell(array_sum($w), 10, 'Aucun mouvement trouvé', 1, 1, 'C');
        } else {
            foreach ($mouvements as $mouvement) {
                // Vérifier si on doit ajouter une nouvelle page
                if ($pdf->GetY() > 180) {
                    $pdf->AddPage();
                    // Réafficher l'en-tête
                    $pdf->SetFont('helvetica', 'B', 8);
                    for($i = 0; $i < count($header); $i++) {
                        $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    $pdf->SetFont('helvetica', '', 7);
                }
                
                // Date
                $pdf->Cell($w[0], 6, date('d/m/Y H:i', strtotime($mouvement['date_operation'])), 'LR', 0, 'C', $fill);
                
                // Code article
                $pdf->Cell($w[1], 6, $mouvement['code_article'], 'LR', 0, 'L', $fill);
                
                // Désignation (peut être tronquée)
                $designation = substr($mouvement['designation'], 0, 30) . (strlen($mouvement['designation']) > 30 ? '...' : '');
                $pdf->Cell($w[2], 6, $designation, 'LR', 0, 'L', $fill);
                
                // Opération avec couleur
                $operation_text = '';
                $operation_color = array(0, 0, 0);
                
                if ($mouvement['operation'] === 'entree') {
                    $operation_text = 'ENT';
                    $operation_color = array(40, 167, 69);
                } elseif ($mouvement['operation'] === 'sortie') {
                    $operation_text = 'SOR';
                    $operation_color = array(220, 53, 69);
                } elseif ($mouvement['operation'] === 'retour') {
                    $operation_text = 'RET';
                    $operation_color = array(23, 162, 184);
                } elseif ($mouvement['operation'] === 'retour_fournisseur') {
                    $operation_text = 'RF';
                    $operation_color = array(255, 193, 7);
                }
                
                $pdf->SetTextColor($operation_color[0], $operation_color[1], $operation_color[2]);
                $pdf->Cell($w[3], 6, $operation_text, 'LR', 0, 'C', $fill);
                $pdf->SetTextColor(0, 0, 0);
                
                // Quantité_A
                $qte_value = formatNumber($mouvement['qte']);
                $pdf->Cell($w[4], 6, $qte_value, 'LR', 0, 'R', $fill);
                
                // Quantité avec signe et couleur
                $qte_signee = '';
                $qte_color = array(0, 0, 0);
                
                if ($mouvement['operation'] === 'entree' || $mouvement['operation'] === 'retour') {
                    $qte_signee = '+' . $qte_value;
                    $qte_color = $mouvement['operation'] === 'entree' ? 
                        array(40, 167, 69) : array(23, 162, 184);
                } elseif ($mouvement['operation'] === 'sortie' || $mouvement['operation'] === 'retour_fournisseur') {
                    $qte_signee = '-' . $qte_value;
                    $qte_color = $mouvement['operation'] === 'sortie' ? 
                        array(220, 53, 69) : array(255, 193, 7);
                }
                
                $pdf->SetTextColor($qte_color[0], $qte_color[1], $qte_color[2]);
                $pdf->Cell($w[5], 6, $qte_signee, 'LR', 0, 'R', $fill);
                $pdf->SetTextColor(0, 0, 0);
                
                // Stock Avant
                $pdf->Cell($w[6], 6, formatNumber($mouvement['stock_avant_operation']), 'LR', 0, 'R', $fill);
                
                // Stock Après
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->Cell($w[7], 6, formatNumber($mouvement['stock_apres_operation']), 'LR', 0, 'R', $fill);
                $pdf->SetFont('helvetica', '', 7);
                
                // Stock Min (avec mise en évidence si dépassement)
                $stock_min = formatNumber($mouvement['stock_min']);
                if ($mouvement['stock_apres_operation'] <= $mouvement['stock_min']) {
                    $pdf->SetTextColor(255, 193, 7); // Jaune
                    $pdf->Cell($w[8], 6, $stock_min, 'LR', 0, 'R', $fill);
                    $pdf->SetTextColor(0, 0, 0);
                } else {
                    $pdf->Cell($w[8], 6, $stock_min, 'LR', 0, 'R', $fill);
                }
                
                // Stock Max (avec mise en évidence si dépassement)
                $stock_max = formatNumber($mouvement['stock_max']);
                if ($mouvement['stock_apres_operation'] >= $mouvement['stock_max']) {
                    $pdf->SetTextColor(220, 53, 69); // Rouge
                    $pdf->Cell($w[9], 6, $stock_max, 'LR', 0, 'R', $fill);
                    $pdf->SetTextColor(0, 0, 0);
                } else {
                    $pdf->Cell($w[9], 6, $stock_max, 'LR', 0, 'R', $fill);
                }
                
                // Utilisateur
                $utilisateur = ($mouvement['user_nom'] ?? '') . ' ' . ($mouvement['user_prenom'] ?? '');
                $utilisateur = substr($utilisateur, 0, 15);
                $pdf->Cell($w[10], 6, $utilisateur, 'LR', 0, 'L', $fill);
                
                // Commentaire
                $commentaire = !empty($mouvement['commentaire']) ? 
                    substr($mouvement['commentaire'], 0, 30) . (strlen($mouvement['commentaire']) > 30 ? '...' : '') : '-';
                $pdf->Cell($w[11], 6, $commentaire, 'LR', 0, 'L', $fill);
                
                $pdf->Ln();
                
                // Fermer la ligne
                $pdf->Cell(array_sum($w), 0, '', 'T');
                
                $fill = !$fill;
            }
        }
        
        // ------ PIED DE PAGE ------
        $pdf->SetY(-20);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
        
        // Date et heure
        $pdf->Cell(0, 5, 'Généré le ' . date('d/m/Y à H:i:s'), 0, 0, 'L');
        
        // Total des mouvements
        $pdf->Cell(0, 5, 'Total: ' . count($mouvements) . ' mouvements', 0, 0, 'R');
        
        $pdf->Ln(5);
        
        // Numéro de page
        $pdf->Cell(0, 5, 'Page ' . $pdf->getAliasNumPage() . ' sur ' . $pdf->getAliasNbPages(), 0, 0, 'C');
        
        // ------ GÉNÉRER LE PDF ------
        $filename = 'historique_mouvements_' . date('Ymd_His') . '.pdf';
        
        // Générer le PDF et le télécharger
        $pdf->Output($filename, 'I');
        exit;
        
    } 
    // GÉNÉRATION DU PDF AVEC DOMPDF
    elseif ($pdf_engine === 'dompdf') {
        try {
            $dompdf = new Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'landscape');
            
            // Options pour améliorer la compatibilité
            $options = $dompdf->getOptions();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            
            $dompdf->setOptions($options);
            
            // Préparer le HTML
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Historique des mouvements de stock</title>
                <style>
                    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; margin: 0; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .title { font-size: 16pt; font-weight: bold; margin-bottom: 5px; }
                    .subtitle { font-size: 12pt; margin-bottom: 10px; color: #555; }
                    .info { font-size: 9pt; margin-bottom: 3px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 8pt; }
                    th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 5px; text-align: center; font-weight: bold; }
                    td { border: 1px solid #dee2e6; padding: 4px; }
                    .text-end { text-align: right; }
                    .text-center { text-align: center; }
                    .footer { margin-top: 30px; font-size: 8pt; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="title">' . htmlspecialchars($nom_etablissement) . '</div>
                    <div class="subtitle">Historique des mouvements de stock</div>
                    <div class="info">Généré le: ' . date('d/m/Y H:i') . '</div>
                </div>';
                
            if (!empty($filtres_text)) {
                $html .= '<div style="margin: 10px 0; font-size: 9pt;">
                    <strong>Filtres appliqués:</strong> ' . implode(' | ', $filtres_text) . '
                </div>';
            }
            
            $html .= '<table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Code</th>
                        <th>Désignation</th>
                        <th>Opération</th>
                        <th class="text-end">Qté_A</th>
                        <th class="text-end">Qté</th>
                        <th class="text-end">Stock Avant</th>
                        <th class="text-end">Stock Après</th>
                        <th class="text-end">Min</th>
                        <th class="text-end">Max</th>
                        <th>Utilisateur</th>
                        <th>Commentaire</th>
                    </tr>
                </thead>
                <tbody>';
            
            if (empty($mouvements)) {
                $html .= '<tr><td colspan="12" class="text-center">Aucun mouvement trouvé</td></tr>';
            } else {
                foreach ($mouvements as $mouvement) {
                    $operation_text = '';
                    if ($mouvement['operation'] === 'entree') $operation_text = 'ENT';
                    elseif ($mouvement['operation'] === 'sortie') $operation_text = 'SOR';
                    elseif ($mouvement['operation'] === 'retour') $operation_text = 'RET';
                    elseif ($mouvement['operation'] === 'retour_fournisseur') $operation_text = 'RF';
                    
                    $qte = formatNumber($mouvement['qte']);
                    $qte_signe = ($mouvement['operation'] === 'entree' || $mouvement['operation'] === 'retour') ? '+' . $qte : '-' . $qte;
                    
                    $html .= '<tr>
                        <td>' . date('d/m/Y H:i', strtotime($mouvement['date_operation'])) . '</td>
                        <td>' . htmlspecialchars($mouvement['code_article']) . '</td>
                        <td>' . htmlspecialchars($mouvement['designation']) . '</td>
                        <td class="text-center">' . $operation_text . '</td>
                        <td class="text-end">' . $qte . '</td>
                        <td class="text-end">' . $qte_signe . '</td>
                        <td class="text-end">' . formatNumber($mouvement['stock_avant_operation']) . '</td>
                        <td class="text-end">' . formatNumber($mouvement['stock_apres_operation']) . '</td>
                        <td class="text-end">' . formatNumber($mouvement['stock_min']) . '</td>
                        <td class="text-end">' . formatNumber($mouvement['stock_max']) . '</td>
                        <td>' . htmlspecialchars(($mouvement['user_nom'] ?? '') . ' ' . ($mouvement['user_prenom'] ?? '')) . '</td>
                        <td>' . (!empty($mouvement['commentaire']) ? htmlspecialchars($mouvement['commentaire']) : '-') . '</td>
                    </tr>';
                }
            }
            
            $html .= '</tbody></table>
                <div class="footer">
                    Document généré le ' . date('d/m/Y à H:i:s') . ' | 
                    Total des mouvements: ' . count($mouvements) . '
                </div>
            </body>
            </html>';
            
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            
            // Télécharger le PDF
            $filename = 'historique_mouvements_' . date('Ymd_His') . '.pdf';
            $dompdf->stream($filename, ['Attachment' => false]);
            exit;
            
        } catch (Exception $e) {
            header('Location: ' . str_replace('export=pdf', '', $_SERVER['REQUEST_URI']) . '&error=dompdf_error');
            exit;
        }
    }
    
    exit;
}

// Si on arrive ici, c'est que ce n'est pas une requête PDF, on continue avec l'affichage normal
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] == 'no_pdf_engine'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> Aucun moteur PDF trouvé. Veuillez installer TCPDF ou DomPDF.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($_GET['error'] == 'dompdf_error'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> Erreur lors de la génération du PDF avec DomPDF.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-clock-history"></i> Historique des mouvements de stock</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Historique mouvements</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Statistiques -->
    <?php if (!empty($stats)): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Statistiques des mouvements
                    <?php if (!empty($date_debut) || !empty($date_fin)): ?>
                        <small class="text-muted">
                            (<?php echo !empty($date_debut) ? 'Du ' . date('d/m/Y', strtotime($date_debut)) : ''; ?>
                            <?php echo !empty($date_fin) ? ' au ' . date('d/m/Y', strtotime($date_fin)) : ''; ?>)
                        </small>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($stats as $stat): ?>
                            <div class="col-md-3">
                                <div class="card mb-3
                                    <?php
                                        echo $stat['operation'] === 'entree' ? 'border-success' :
                                            ($stat['operation'] === 'sortie' ? 'border-danger' :
                                            ($stat['operation'] === 'retour' ? 'border-info' : 'border-warning'));
                                    ?>">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">
                                            <?php
                                                if ($stat['operation'] === 'entree') {
                                                    echo '<i class="bi bi-box-arrow-in-down text-success"></i> Entrées';
                                                } elseif ($stat['operation'] === 'sortie') {
                                                    echo '<i class="bi bi-box-arrow-up text-danger"></i> Sorties';
                                                } elseif ($stat['operation'] === 'retour') {
                                                    echo '<i class="bi bi-arrow-counterclockwise text-info"></i> Retours Employé';
                                                } elseif ($stat['operation'] === 'retour_fournisseur') {
                                                    echo '<i class="bi bi-box-arrow-left text-warning"></i> Retours Fournisseur';
                                                }
                                            ?>
                                        </h5>
                                        <p class="mb-1"><strong><?php echo formatNumber($stat['nombre_mouvements']); ?></strong> mouvements</p>
                                        <p class="mb-0"><strong><?php echo formatNumber($stat['quantite_totale']); ?></strong> unités</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-funnel"></i> Filtres de recherche
        </div>
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="code_article" class="form-label">Code article</label>
                        <input type="text" class="form-control" id="code_article" name="code_article"
                               value="<?php echo htmlspecialchars($code_article); ?>" placeholder="Rechercher par code">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="designation" class="form-label">Désignation</label>
                        <input type="text" class="form-control" id="designation" name="designation"
                               value="<?php echo htmlspecialchars($designation); ?>" placeholder="Rechercher par désignation">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="operation" class="form-label">Opération</label>
                        <select class="form-select" id="operation" name="operation">
                            <option value="">Toutes</option>
                            <option value="entree" <?php echo $operation === 'entree' ? 'selected' : ''; ?>>Entrée</option>
                            <option value="sortie" <?php echo $operation === 'sortie' ? 'selected' : ''; ?>>Sortie</option>
                            <option value="retour" <?php echo $operation === 'retour' ? 'selected' : ''; ?>>Retour Employé</option>
                            <option value="retour_fournisseur" <?php echo $operation === 'retour_fournisseur' ? 'selected' : ''; ?>>Retour Fournisseur</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut"
                               value="<?php echo htmlspecialchars($date_debut); ?>">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin"
                               value="<?php echo htmlspecialchars($date_fin); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                        <a href="<?php echo BASE_URL; ?>/pages/mouvements/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des mouvements -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-list"></i> Liste des mouvements
                <span class="badge bg-secondary ms-2"><?php echo formatNumber(count($mouvements)); ?> résultat(s)</span>
            </div>
            <div>
                <button id="btnExportPdf"
                        data-base-url="export_pdf.php?<?php echo htmlspecialchars(http_build_query($_GET)); ?>"
                        class="btn btn-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> Exporter en PDF
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="mouvementsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Code Article</th>
                            <th>Désignation</th>
                            <th>Opération</th>
                            <th class="text-end">Quantité_A</th>
                            <th class="text-end">Quantité</th>
                            <th class="text-end">Stock Avant</th>
                            <th class="text-end">Stock Après</th>
                            <th class="text-end">Stock Min</th>
                            <th class="text-end">Stock Max</th>
                            <th>Utilisateur</th>
                            <th>Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mouvements)): ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <i class="bi bi-inbox"></i> Aucun mouvement trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mouvements as $mouvement): ?>
                                <tr>
                                    <td data-order="<?php echo strtotime($mouvement['date_operation']); ?>">
                                        <small><?php echo date('d/m/Y H:i', strtotime($mouvement['date_operation'])); ?></small>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($mouvement['code_article']); ?></code>
                                    </td>
                                    <td><?php echo htmlspecialchars($mouvement['designation']); ?></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'entree' => '<span class="badge bg-success"><i class="bi bi-box-arrow-in-down"></i> Entrée</span>',
                                            'sortie' => '<span class="badge bg-danger"><i class="bi bi-box-arrow-up"></i> Sortie</span>',
                                            'retour' => '<span class="badge bg-info"><i class="bi bi-arrow-counterclockwise"></i> Retour Employé</span>',
                                            'retour_fournisseur' => '<span class="badge bg-warning text-dark"><i class="bi bi-box-arrow-left"></i> Retour Fournisseur</span>'
                                        ];
                                        echo $badges[$mouvement['operation']] ?? $mouvement['operation'];
                                        ?>
                                    </td>
                                    <td class="text-end" data-order="<?php echo $mouvement['qte']; ?>">
                                        <?php
                                        // Colonne Quantité_A : afficher le type de quantité selon l'opération
                                        $qte_value = formatNumber($mouvement['qte']);
                                        if ($mouvement['operation'] === 'entree') {
                                            echo '<span class="text-success"><strong>Qté Entrée:</strong> ' . $qte_value . '</span>';
                                        } elseif ($mouvement['operation'] === 'sortie') {
                                            echo '<span class="text-danger"><strong>Qté Sortie:</strong> ' . $qte_value . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour') {
                                            echo '<span class="text-info"><strong>Qté Retour Employé:</strong> ' . $qte_value . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour_fournisseur') {
                                            echo '<span class="text-warning"><strong>Qté Retour Fournisseur:</strong> ' . $qte_value . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end" data-order="<?php echo $mouvement['qte']; ?>">
                                        <?php
                                        // Afficher le bon badge selon l'opération
                                        $qte = formatNumber($mouvement['qte']);
                                        if ($mouvement['operation'] === 'entree') {
                                            echo '<span class="badge bg-success">+ ' . $qte . '</span>';
                                        } elseif ($mouvement['operation'] === 'sortie') {
                                            echo '<span class="badge bg-danger">- ' . $qte . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour') {
                                            echo '<span class="badge bg-info">+ ' . $qte . '</span>';
                                        } elseif ($mouvement['operation'] === 'retour_fournisseur') {
                                            echo '<span class="badge bg-warning text-dark">- ' . $qte . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end"><?php echo formatNumber($mouvement['stock_avant_operation']); ?></td>
                                    <td class="text-end">
                                        <strong><?php echo formatNumber($mouvement['stock_apres_operation']); ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($mouvement['stock_apres_operation'] <= $mouvement['stock_min']): ?>
                                            <span class="badge bg-warning text-dark"><?php echo formatNumber($mouvement['stock_min']); ?></span>
                                        <?php else: ?>
                                            <?php echo formatNumber($mouvement['stock_min']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($mouvement['stock_apres_operation'] >= $mouvement['stock_max']): ?>
                                            <span class="badge bg-danger"><?php echo formatNumber($mouvement['stock_max']); ?></span>
                                        <?php else: ?>
                                            <?php echo formatNumber($mouvement['stock_max']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($mouvement['user_nom'] ?? '') . ' ' . htmlspecialchars($mouvement['user_prenom'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($mouvement['commentaire'])): ?>
                                            <small><?php echo htmlspecialchars($mouvement['commentaire']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// Attendre que jQuery et DataTables soient chargés
(function() {
    function initDataTable() {
        if (typeof jQuery === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            setTimeout(initDataTable, 100);
            return;
        }

        $(document).ready(function() {
            // Détruire toute instance DataTables existante
            if ($.fn.DataTable.isDataTable('#mouvementsTable')) {
                $('#mouvementsTable').DataTable().destroy();
            }

            // Initialiser DataTables avec tri par colonnes
            var table = $('#mouvementsTable').DataTable({
                language: {
                    url: '<?php echo BASE_URL; ?>/assets/js/fr-FR.json'
                },
                pageLength: 50,
                lengthMenu: [[10, 50, 100, 200, 500, -1], [10, 50, 100, 200, 500, "Tous"]],
                order: [[0, 'desc']], // Tri par date décroissant par défaut
                orderClasses: false, // Améliore les performances
                processing: true,
                deferRender: true,
                columnDefs: [
                    {
                        targets: '_all',
                        orderable: true
                    }
                ],
                stateSave: true,
                stateDuration: 60 * 60 * 24 * 7, // 7 jours
                initComplete: function() {
                    console.log('DataTables initialisé avec succès');
                }
            });

            // Bouton export PDF : passe le terme de recherche DataTables au fichier export
            $('#btnExportPdf').on('click', function () {
                var baseUrl = $(this).data('base-url');
                var dtSearch = table.search(); // terme de recherche DataTables courant
                var url = baseUrl;
                if (dtSearch) {
                    url += (url.indexOf('?') !== -1 ? '&' : '?') + 'dt_search=' + encodeURIComponent(dtSearch);
                }
                window.open(url, '_blank');
            });
        });
    }

    // Lancer l'initialisation
    initDataTable();
})();
</script>

</body>
</html>