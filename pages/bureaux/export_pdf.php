<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// En-têtes de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once __DIR__ . '/../../includes/header.php';

// Vérifier si la connexion est sécurisée (HTTPS) en production
if (($_SERVER['SERVER_PORT'] != 443) && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')) {
    // En production, rediriger vers HTTPS
    // header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    // exit;
}

try {
    $auth = new Auth();
    $auth->requirePermission('bureaux', 'view');
    
    $db = Database::getInstance();
    
    // Validation des paramètres d'export
    $title = filter_input(INPUT_GET, 'title', FILTER_SANITIZE_STRING) ?: 'Liste des bureaux';
    $orientation = filter_input(INPUT_GET, 'orientation', FILTER_SANITIZE_STRING) ?: 'portrait';
    $format = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_STRING) ?: 'A4';
    $include_header = filter_input(INPUT_GET, 'include_header', FILTER_VALIDATE_BOOLEAN, ['options' => ['default' => true]]);
    $include_date = filter_input(INPUT_GET, 'include_date', FILTER_VALIDATE_BOOLEAN, ['options' => ['default' => true]]);
    
    // Validation des filtres
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?: '';
    $service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT) ?: '';
    
    // Construire la requête (sans pagination pour tout exporter)
    $sql = "SELECT b.*,
                   s.nom as service_nom,
                   e.nom as employe_nom, e.prenom as employe_prenom
            FROM bureaux b
            LEFT JOIN services s ON b.service_id = s.id
            LEFT JOIN employes e ON b.employe_id = e.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (b.code_local LIKE :search OR b.batiment LIKE :search OR b.etage LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($service_id)) {
        $sql .= " AND b.service_id = :service_id";
        $params[':service_id'] = $service_id;
    }
    
    $sql .= " ORDER BY b.code_local ASC";
    
    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $bureaux = $stmt->fetchAll();
    
    // Vérifier plusieurs chemins possibles pour TCPDF
    $tcpdf_paths = [
        __DIR__ . '/../../../vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/../../../vendor/tcpdf/tcpdf.php',
        __DIR__ . '/../../../tcpdf/tcpdf.php',
        __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/../../vendor/tcpdf/tcpdf.php'
    ];
    
    $tcpdf_loaded = false;
    foreach ($tcpdf_paths as $tcpdf_path) {
        if (file_exists($tcpdf_path)) {
            require_once $tcpdf_path;
            $tcpdf_loaded = true;
            break;
        }
    }
    
    if (!$tcpdf_loaded) {
        throw new Exception("TCPDF n'est pas installé. Veuillez installer TCPDF via Composer: 'composer require tecnickcom/tcpdf'");
    }
    
    // Créer un nouveau document PDF
    $pdf = new TCPDF($orientation === 'landscape' ? 'L' : 'P', 'mm', $format, true, 'UTF-8', false);
    
    // Définir les informations du document
    $pdf->SetCreator('Application Gestion');
    $pdf->SetAuthor('Application Gestion');
    $pdf->SetTitle($title);
    $pdf->SetSubject('Export des bureaux');
    
    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Ajouter une page
    $pdf->AddPage();
    
    // Titre du document
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Informations supplémentaires
    if ($include_date) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Export généré le : ' . date('d/m/Y à H:i'), 0, 1);
    }
    
    // Résumé des filtres appliqués
    $filter_info = '';
    if (!empty($search)) {
        $filter_info .= 'Recherche : ' . $search . ' | ';
    }
    if (!empty($service_id)) {
        // Récupérer le nom du service
        try {
            $stmt_service = $db->getConnection()->prepare("SELECT nom FROM services WHERE id = :id");
            $stmt_service->bindValue(':id', $service_id, PDO::PARAM_INT);
            $stmt_service->execute();
            $service = $stmt_service->fetch();
            if ($service) {
                $filter_info .= 'Service : ' . $service['nom'] . ' | ';
            }
        } catch (Exception $e) {
            $filter_info .= 'Service : ID ' . $service_id . ' | ';
        }
    }
    if (!empty($filter_info)) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, substr($filter_info, 0, -3), 0, 1);
    }
    
    $pdf->Ln(5);
    
    // Tableau des données
    if ($include_header) {
        // En-têtes du tableau
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        
        $headers = ['Code local', 'Bâtiment', 'Étage', 'Service', 'Employé'];
        
        // Définir les largeurs de colonnes selon l'orientation
        if ($orientation === 'landscape') {
            $col_widths = [50, 50, 40, 60, 60];
        } else {
            $col_widths = [40, 40, 30, 50, 50];
        }
        
        for ($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($col_widths[$i], 10, $headers[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
    } else {
        // Définir les largeurs même si pas d'en-tête
        if ($orientation === 'landscape') {
            $col_widths = [50, 50, 40, 60, 60];
        } else {
            $col_widths = [40, 40, 30, 50, 50];
        }
    }
    
    // Données du tableau
    $pdf->SetFont('helvetica', '', 9);
    $fill = false;
    $counter = 0;
    
    if (count($bureaux) > 0) {
        foreach ($bureaux as $bureau) {
            $counter++;
            
            // Alternance des couleurs de fond
            if ($fill) {
                $pdf->SetFillColor(245, 245, 245);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            
            // Code local
            $code_local = !empty($bureau['code_local']) ? $bureau['code_local'] : '-';
            $pdf->Cell($col_widths[0], 8, $code_local, 'LR', 0, 'C', $fill);
            
            // Bâtiment
            $batiment = !empty($bureau['batiment']) ? $bureau['batiment'] : '-';
            $pdf->Cell($col_widths[1], 8, $batiment, 'LR', 0, 'C', $fill);
            
            // Étage
            $etage = !empty($bureau['etage']) ? $bureau['etage'] : '-';
            $pdf->Cell($col_widths[2], 8, $etage, 'LR', 0, 'C', $fill);
            
            // Service
            $service_nom = !empty($bureau['service_nom']) ? $bureau['service_nom'] : '-';
            $pdf->Cell($col_widths[3], 8, $service_nom, 'LR', 0, 'C', $fill);
            
            // Employé
            $employe_nom = '';
            if (!empty($bureau['employe_nom'])) {
                $employe_nom = $bureau['employe_nom'];
                if (!empty($bureau['employe_prenom'])) {
                    $employe_nom .= ' ' . $bureau['employe_prenom'];
                }
            } else {
                $employe_nom = '-';
            }
            $pdf->Cell($col_widths[4], 8, $employe_nom, 'LR', 1, 'C', $fill);
            
            $fill = !$fill;
            
            // Vérifier si on a dépassé la fin de la page
            if ($pdf->GetY() > 260) {
                $pdf->AddPage();
                if ($include_header) {
                    // Réafficher les en-têtes
                    $pdf->SetFont('helvetica', 'B', 10);
                    for ($i = 0; $i < count($headers); $i++) {
                        $pdf->Cell($col_widths[$i], 10, $headers[$i], 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    $pdf->SetFont('helvetica', '', 9);
                }
            }
        }
        
        // Fermer le tableau
        $pdf->Cell(array_sum($col_widths), 0, '', 'T');
    } else {
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 20, 'Aucun bureau trouvé', 0, 1, 'C');
    }
    
    // Statistiques
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->Cell(0, 10, 'Total des bureaux exportés : ' . $counter, 0, 1);
    
    // Pied de page
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Nom du fichier PDF
    $filename = 'bureaux_export_' . date('Ymd_His') . '.pdf';
    
    // Sortir le PDF
    $pdf->Output($filename, 'I');
    
} catch (Exception $e) {
    // En cas d'erreur, afficher un message clair
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Erreur lors de l\'export PDF</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; }
            pre { background: #f8f9fa; padding: 10px; border-left: 3px solid #007bff; }
        </style>
    </head>
    <body>
        <h1>Erreur lors de la génération du PDF</h1>
        <div class="error">
            <strong>Message d\'erreur :</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>
        <h3>Détails techniques :</h3>
        <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
        <p>Veuillez contacter l\'administrateur si le problème persiste.</p>
    </body>
    </html>';
    exit;
}