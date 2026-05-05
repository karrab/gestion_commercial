<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('retours', 'view');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération du retour
$db->prepare("SELECT r.*, 
                     s.nom as service_nom,
                     e.nom as employe_nom, 
                     e.prenom as employe_prenom,
                     e.matricule as employe_matricule,
                     u.nom as user_nom, 
                     u.prenom as user_prenom 
              FROM retours r 
              LEFT JOIN services s ON r.service_id = s.id
              LEFT JOIN employes e ON r.employe_id = e.id
              LEFT JOIN users u ON r.user_id = u.id
              WHERE r.id = ?");
$db->bind(1, $id);
$retour = $db->fetch();

if (!$retour) {
    $_SESSION['error'] = 'Retour introuvable.';
    header('Location: ' . BASE_URL . '/pages/retours/index.php');
    exit;
}

// Récupération des lignes de retour
$db->prepare("SELECT lr.*
              FROM ligne_retours lr 
              LEFT JOIN articles a ON lr.article_id = a.id
              WHERE lr.retour_id = ?");
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
        header('Location: ' . BASE_URL . '/pages/retours/view.php?id=' . $id);
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
    $total_qte += floatval($ligne['qte_retour']);
}

// Création du HTML pour le PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bon de Retour N° ' . $retour['id'] . '</title>
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
            margin: 20px 0;
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
        .separator {
            border-top: 1px dashed #ccc;
            margin: 20px 0;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
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
if (!empty($parametres['tel_fixe'])) $contact[] = 'Tél: ' . $parametres['tel_fixe'];
if (!empty($parametres['tel_mobile'])) $contact[] = 'Mobile: ' . $parametres['tel_mobile'];
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
        BON DE RETOUR DE MATÉRIEL
    </div>

    <!-- Informations de base -->
    <div class="info-section">
        <div class="info-section">
            <span>N° Bon de Retour: </span>
            <span class="info-value"><strong>' . str_pad($retour['id'], 4, '0', STR_PAD_LEFT) . '</strong></span>
        </div>
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Date de retour:</span>
                <span class="info-value">' . date('d/m/Y', strtotime($retour['date'])) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Créé par:</span>
                <span class="info-value">' . htmlspecialchars($retour['user_prenom'] . ' ' . $retour['user_nom']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Créé le:</span>
                <span class="info-value">' . date('d/m/Y à H:i', strtotime($retour['created_at'])) . '</span>
            </div>
        </div>
    </div>

    <!-- Informations du retour -->
    <div class="info-box">
        <div style="text-align: center; margin-bottom: 10px;">
            <strong>INFORMATIONS DU RETOUR</strong>
        </div>
        <div class="info-row">
            <span class="info-label">Service:</span>
            <span class="info-value">' . htmlspecialchars($retour['service_nom']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Employé:</span>
            <span class="info-value">' . htmlspecialchars($retour['employe_nom'] . ' ' . $retour['employe_prenom']) . '</span>
        </div>';
        
if (!empty($retour['employe_matricule'])) {
    $html .= '<div class="info-row">
                <span class="info-label">Matricule:</span>
                <span class="info-value">' . htmlspecialchars($retour['employe_matricule']) . '</span>
              </div>';
}

$html .= '
    </div>

    
    <!-- Tableau des articles retournés -->
    <div style="margin-top: 20px;">
        <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">ARTICLES RETOURNÉS</div>
        <table class="articles-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="20%">Code Article</th>
                    <th width="45%">Désignation</th>
                    <th width="15%">Unité</th>
                    <th width="15%">Quantité Retournée</th>
                </tr>
            </thead>
            <tbody>';

if (count($lignes) > 0) {
    $num = 1;
    foreach ($lignes as $ligne) {
        $html .= '<tr>
                    <td>' . $num . '</td>
                    <td><code>' . htmlspecialchars($ligne['code_article']) . '</code></td>
                    <td style="text-align: left;">' . htmlspecialchars($ligne['designation']) . '</td>
                    <td>' . htmlspecialchars($ligne['unite'] ?? 'Pièce') . '</td>
                    <td>' . number_format($ligne['qte_retour'], 2, ',', ' ') . '</td>
                  </tr>';
        $num++;
    }
    
    $html .= '<tr class="total-row">
                <td colspan="4" style="text-align: right; font-size: 11px;">TOTAL GÉNÉRAL</td>
                <td style="font-size: 11px;"><strong>' . number_format($total_qte, 2, ',', ' ') . '</strong></td>
              </tr>';
} else {
    $html .= '<tr>
                <td colspan="5" style="text-align: center; padding: 20px; color: #666; font-style: italic;">
                    Aucun article dans ce retour
                </td>
              </tr>';
}

$html .= '</tbody>
        </table>
    </div>

    <!-- Signatures -->
    <div class="separator"></div>
    
    <div class="signatures-section">
        <table class="signatures-table">
            <tr>
                <!-- Responsable Stock -->
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-name">' . htmlspecialchars($retour['user_prenom'] . ' ' . $retour['user_nom']) . '</div>
                    <div class="signature-info">Responsable Stock</div>
                    <div class="signature-info">Date: ' . date('d/m/Y') . '</div>
                </td>
                
                <!-- Employé Retourneur -->
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-name">' . htmlspecialchars($retour['employe_nom'] . ' ' . $retour['employe_prenom']) . '</div>
                    <div class="signature-info">Employé Retourneur</div>
                    <div class="signature-info">Matricule: ' . (!empty($retour['employe_matricule']) ? htmlspecialchars($retour['employe_matricule']) : 'N/A') . '</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Document généré le ' . date('d/m/Y \à H:i') . ' | 
        N° Retour: ' . str_pad($retour['id'], 4, '0', STR_PAD_LEFT) . ' | 
        Articles: ' . count($lignes) . ' | 
        Quantité totale: ' . number_format($total_qte, 2, ',', ' ') . '
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
    $auth->logTrace($auth->getUserId(), 'retours', 'pdf', 'retours', $id, "Génération PDF retour #" . $id);
    
    // Envoyer le PDF au navigateur
    $dompdf->stream('Bon_Retour_' . $retour['id'] . '.pdf', [
        'Attachment' => 0
    ]);
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la génération du PDF: ' . $e->getMessage();
    error_log('Erreur DomPDF: ' . $e->getMessage());
    
    header('Location: ' . BASE_URL . '/pages/retours/view.php?id=' . $id);
    exit;
}