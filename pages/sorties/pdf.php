<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('sorties', 'view');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de la sortie
$db->prepare("SELECT s.*, 
                     serv.nom as service_nom,
                     emp.nom as employe_nom, 
                     emp.prenom as employe_prenom,
                     emp.matricule as employe_matricule,
                     
                     u.nom as user_nom, 
                     u.prenom as user_prenom 
              FROM sorties s 
              LEFT JOIN services serv ON s.service_id = serv.id
              LEFT JOIN employes emp ON s.employe_id = emp.id
              
              LEFT JOIN users u ON s.user_id = u.id
              WHERE s.id = ?");
$db->bind(1, $id);
$sortie = $db->fetch();

if (!$sortie) {
    $_SESSION['error'] = 'Sortie introuvable.';
    header('Location: ' . BASE_URL . '/pages/sorties/index.php');
    exit;
}

// Récupération des lignes de sortie
$db->prepare("SELECT ls.*
              FROM ligne_sorties ls 
              LEFT JOIN articles a ON ls.article_id = a.id
              WHERE ls.sortie_id = ?");
$db->bind(1, $id);
$lignes = $db->fetchAll();

// Récupération des paramètres
$db->prepare("SELECT * FROM parametres LIMIT 1");
$parametres = $db->fetch();

// Vérifier si DomPDF est disponible
if (!class_exists('Dompdf\Dompdf')) {
    // Essayer différents chemins
    $paths = [
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../libs/dompdf/autoload.inc.php',
    ];
    
    $found = false;
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['error'] = 'DompDF n\'est pas installé. Veuillez installer DomPDF via Composer: composer require dompdf/dompdf';
        header('Location: ' . BASE_URL . '/pages/sorties/view.php?id=' . $id);
        exit;
    }
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Gestion du logo
$logoHtml = '';
$logoPath = __DIR__ . '/../../assets/images/' . ($parametres['logo'] ?? '');
if (!empty($parametres['logo']) && file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoMime = mime_content_type($logoPath);
    $logoHtml = '<img src="data:' . $logoMime . ';base64,' . $logoData . '" style="max-height: 40px; max-width: 100px;" alt="Logo">';
} else {
    $logoHtml = '<div style="height: 40px; width: 100px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-weight: bold; font-size: 10px;">
                    LOGO
                </div>';
}

// Calculer les totaux
$total_qte = 0;
foreach ($lignes as $ligne) {
    $total_qte += floatval($ligne['qte_sortie']);
}

// Création du HTML pour le PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bon de Sortie N° ' . $sortie['id'] . '</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: left;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-content {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .logo-container {
            flex-shrink: 0;
        }
        .company-info {
            flex: 1;
        }
        .company-info h1 {
            margin: 0 0 5px 0;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #130505;
        }
        .company-details {
            font-size: 10px;
            color: #0e0404;
            line-height: 1.3;
        }
        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .document-number {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .info-section {
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            margin-bottom: 3px;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #333;
        }
        .info-value {
            color: #000;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        .info-table td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        .info-table .section-title {
            background: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 11px;
        }
        .articles-table thead th {
            background: #2c3e50;
            color: white;
            padding: 8px;
            text-align: center;
            border: 1px solid #2c3e50;
            font-weight: bold;
        }
        .articles-table tbody td {
            padding: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
        }
        .articles-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .total-row {
            background: #e9ecef !important;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .signatures-section {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 5px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin: 30px auto 5px;
            width: 90%;
        }
        .signature-name {
            font-weight: bold;
            font-size: 11px;
            margin-top: 3px;
        }
        .signature-info {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .notes-section {
            margin: 15px 0;
            padding: 10px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            font-size: 11px;
        }
        .notes-label {
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
        }
        .stats-section {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 11px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-container">
                ' . $logoHtml . '
            </div>
            
            <div class="company-info">
                <h1>' . htmlspecialchars(strtoupper($parametres['nom_etablissement'] ?? 'Établissement')) . '</h1>
                <div class="company-details">
                    ' . htmlspecialchars($parametres['adresse'] ?? '') . '<br>';
                    
$contact = [];
if (!empty($parametres['tel_fixe'])) $contact[] = 'Tel: ' . $parametres['tel_fixe'];
if (!empty($parametres['fax'])) $contact[] = 'Fax: ' . $parametres['fax'];
if (!empty($parametres['email'])) $contact[] = 'Email: ' . $parametres['email'];

if (!empty($contact)) {
    $html .= implode('<br>', array_map('htmlspecialchars', $contact));
}

$html .= '
                </div>
            </div>
        </div>
    </div>

    <!-- Titre centré -->
    <div class="document-title">
        BON DE SORTIE
    </div>
    
    <!-- Numéro de document -->
    <div class="document-number">
        N°: ' . $sortie['id'] . '
    </div>

    <!-- Informations de base -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Date de sortie:</span>
            <span class="info-value">' . date('d/m/Y', strtotime($sortie['date'])) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Créé par:</span>
            <span class="info-value">' . htmlspecialchars($sortie['user_prenom'] . ' ' . $sortie['user_nom']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Créé le:</span>
            <span class="info-value">' . date('d/m/Y à H:i', strtotime($sortie['created_at'])) . '</span>
        </div>
    </div>

    <!-- Informations demandeur seulement dans un tableau simple -->
    <table class="info-table">
        <tr>
            <td class="section-title" width="100%">INFORMATIONS DEMANDEUR</td>
        </tr>
        <tr>
            <td>
                <div class="info-row">
    <span class="info-label">Service:</span>
    <span class="info-value">' . htmlspecialchars($sortie['service_nom']) . '</span>
</div>
<div class="info-row">
    <span class="info-label">Employé:</span>
    <span class="info-value">' . htmlspecialchars($sortie['employe_nom'] . ' ' . $sortie['employe_prenom']) . '</span>
</div>';

if (!empty($sortie['employe_matricule'])) {
    $html .= '<div class="info-row">
        <span class="info-label">Matricule:</span>
        <span class="info-value">' . htmlspecialchars($sortie['employe_matricule']) . '</span>
    </div>';
}

$html .= '
            </td>
        </tr>
    </table>

    <!-- Tableau des articles -->
    <table class="articles-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="20%">Code Article</th>
                <th width="45%">Désignation</th>
                <th width="15%">Unité</th>
                <th width="15%">Quantité Sortie</th>
            </tr>
        </thead>
        <tbody>';

if (count($lignes) > 0) {
    $num = 1;
    foreach ($lignes as $ligne) {
        // Formater la quantité sans virgule ni zéros après
        $qte_formatee = (int)$ligne['qte_sortie'];
        
        $html .= '<tr>
                    <td>' . $num . '</td>
                    <td><code>' . htmlspecialchars($ligne['code_article']) . '</code></td>
                    <td style="text-align: left;">' . htmlspecialchars($ligne['designation']) . '</td>
                    <td>' . htmlspecialchars($ligne['unite'] ?? 'Pièce') . '</td>
                    <td>' . $qte_formatee . '</td>
                  </tr>';
        $num++;
    }
    
    // Formater le total sans virgule ni zéros après
    $total_qte_formate = (int)$total_qte;
    
    
} 

$html .= '</tbody>
    </table>';



// Signatures - MODIFICATION ICI: Employé Demandeur à gauche et Responsable Stock à droite
$html .= '<div class="signatures-section">
        <table class="signatures-table">
            <tr>
                <!-- Employé Demandeur - Gauche -->
                <td style="width: 50%;">
                    <div class="signature-line"></div>
                    <div class="signature-name">' . htmlspecialchars($sortie['employe_nom'] . ' ' . $sortie['employe_prenom']) . '</div>
                    <div class="signature-info">Employé Demandeur</div>
                    <div class="signature-info">Matricule: ' . (!empty($sortie['employe_matricule']) ? htmlspecialchars($sortie['employe_matricule']) : 'N/A') . '</div>
                </td>
                
                <!-- Responsable Stock - Droite -->
                <td style="width: 50%;">
                    <div class="signature-line"></div>
                    <div class="signature-name">' . htmlspecialchars($sortie['user_prenom'] . ' ' . $sortie['user_nom']) . '</div>
                    <div class="signature-info">Responsable Stock</div>
                    <div class="signature-info">Date: ' . date('d/m/Y') . '</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Document généré le ' . date('d/m/Y \à H:i') . ' | 
        N° Sortie: ' . $sortie['id'] . ' | 
        Articles: ' . count($lignes) . ' | 
        Quantité totale: ' . $total_qte_formate . '
    </div>
</body>
</html>';

try {
    // Configuration de DomPDF
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', false);
    $options->set('tempDir', sys_get_temp_dir());
    
    // Créer l'instance DomPDF
    $dompdf = new Dompdf($options);
    
    // Charger le HTML
    $dompdf->loadHtml($html, 'UTF-8');
    
    // Définir la taille et l'orientation du papier
    $dompdf->setPaper('A4', 'portrait');
    
    // Rendre le PDF
    $dompdf->render();
    
    // Log de la trace
    $auth->logTrace($auth->getUserId(), 'sorties', 'pdf', 'sorties', $id, "Génération PDF sortie #" . $id);
    
    // Envoyer le PDF au navigateur
    $dompdf->stream('Bon_Sortie_' . $sortie['id'] . '.pdf', [
        'Attachment' => 0
    ]);
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la génération du PDF: ' . $e->getMessage();
    error_log('Erreur DomPDF: ' . $e->getMessage());
    
    header('Location: ' . BASE_URL . '/pages/sorties/view.php?id=' . $id);
    exit;
}