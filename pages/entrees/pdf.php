<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('entrees', 'view');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de l'entrée
$db->prepare("SELECT e.*, f.nom_complet as fournisseur, u.nom as user_nom, u.prenom as user_prenom 
              FROM entrees e 
              LEFT JOIN fournisseurs f ON e.fournisseur_id = f.id
              LEFT JOIN users u ON e.user_id = u.id
              WHERE e.id = ?");
$db->bind(1, $id);
$entree = $db->fetch();

if (!$entree) {
    $_SESSION['error'] = 'Entrée introuvable.';
    header('Location: ' . BASE_URL . '/pages/entrees/index.php');
    exit;
}

// Récupération des lignes d'entrée
$db->prepare("SELECT le.*, a.qte_disponible 
              FROM ligne_entrees le 
              LEFT JOIN articles a ON le.article_id = a.id
              WHERE le.entree_id = ?");
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
        header('Location: ' . BASE_URL . '/pages/entrees/view.php?id=' . $id);
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
    $logoHtml = '<div style="height: 80px; width: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-weight: bold;">
                    LOGO
                </div>';
}

// Fonction pour formater les nombres sans virgule et zéros inutiles
function formatQte($nombre) {
    // Convertir en float pour s'assurer que c'est un nombre
    $nombre = floatval($nombre);
    
    // Si c'est un nombre entier, retourner sans décimales
    if (intval($nombre) == $nombre) {
        return number_format($nombre, 0, '', ' ');
    }
    
    // Sinon, retourner avec 2 décimales maximum
    return rtrim(rtrim(number_format($nombre, 2, ',', ' '), '0'), ',');
}

// Création du HTML pour le PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bon d\'Entrée N° ' . $entree['id'] . '</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: left;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-content {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .logo-container {
            flex-shrink: 0;
        }
        .company-info {
            flex: 1;
        }
        .company-info h1 {
            margin: 0 0 10px 0;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
        }
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }
        .entry-info {
            margin-top: 15px;
            font-size: 12px;
        }
        .info-line {
            margin-bottom: 5px;
            display: flex;
            align-items: baseline;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #333;
        }
        .info-value {
            color: #000;
            margin-left: 10px;
        }
        .document-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 11px;
        }
        .articles-table thead th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: center;
            border: 1px solid #2c3e50;
            font-weight: bold;
        }
        .articles-table tbody td {
            padding: 8px;
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
            margin-top: 60px;
            padding-top: 20px;
        }
        .signature-right {
            float: right;
            text-align: center;
            width: 250px;
            margin-top: 40px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 40px auto 10px;
            height: 20px;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 5px;
            font-size: 13px;
        }
        .signature-role {
            font-size: 11px;
            color: #666;
            font-style: italic;
            margin-top: 2px;
        }
        .notes-section {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
        }
        .notes-label {
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
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
if (!empty($parametres['tel_mobile'])) $contact[] = 'Mobile: ' . $parametres['tel_mobile'];
if (!empty($parametres['email'])) $contact[] = 'Email: ' . $parametres['email'];

if (!empty($contact)) {
    $html .= implode('<br>', array_map('htmlspecialchars', $contact));
}

$html .= '</div>
                <!-- Titre centré -->
    <div class="document-title">
        BON D\'ENTRÉE
    </div>

                <div class="entry-info">
                    <div class="info-line">
                        <span class="info-label">Fournisseur:</span>
                        <span class="info-value">' . htmlspecialchars($entree['fournisseur']) . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">Date d\'entrée:</span>
                        <span class="info-value">' . date('d/m/Y', strtotime($entree['date'])) . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">Référence:</span>
                        <span class="info-value">' . $entree['id'] . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">Créé par:</span>
                        <span class="info-value">' . htmlspecialchars($entree['user_prenom'] . ' ' . $entree['user_nom']) . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">Document généré le:</span>
                        <span class="info-value">' . date('d/m/Y \à H:i') . '</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <!-- Tableau des articles -->
    <table class="articles-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="20%">Code Article</th>
                <th width="45%">Désignation</th>
                <th width="15%">Quantité</th>
                <th width="15%">Stock Actuel</th>
            </tr>
        </thead>
        <tbody>';

if (count($lignes) > 0) {
    $num = 1;
    $total_qte = 0;
    foreach ($lignes as $ligne) {
        $qte_disponible = isset($ligne['qte_disponible']) ? $ligne['qte_disponible'] : 0;
        $total_qte += $ligne['qte_entree'];
        
        // Utilisation de la fonction formatQte pour formater les nombres
        $html .= '<tr>
                    <td>' . $num . '</td>
                    <td><code>' . htmlspecialchars($ligne['code_article']) . '</code></td>
                    <td style="text-align: left;">' . htmlspecialchars($ligne['designation']) . '</td>
                    <td>' . formatQte($ligne['qte_entree']) . '</td>
                    <td>' . formatQte($qte_disponible) . '</td>
                  </tr>';
        $num++;
    }
    
    // Suppression de la ligne TOTAL GÉNÉRAL
    // La ligne suivante a été supprimée:
    // $html .= '<tr class="total-row">...</tr>';
} else {
    $html .= '<tr>
                <td colspan="5" style="text-align: center; padding: 30px; color: #666;">
                    <i>Aucun article dans cette entrée.</i>
                </td>
              </tr>';
}

$html .= '</tbody>
    </table>';

if (!empty($entree['notes'])) {
    $html .= '<div class="notes-section">
                <div class="notes-label">Notes:</div>
                <div>' . nl2br(htmlspecialchars($entree['notes'])) . '</div>
              </div>';
}

$html .= '
    <!-- Section signature à droite -->
    <div class="signatures-section">
        <div style="clear: both;"></div>
        <div class="signature-right">
            <div class="signature-line"></div>
            <div class="signature-name">' . htmlspecialchars($entree['user_prenom'] . ' ' . $entree['user_nom']) . '</div>
            <div class="signature-role">Responsable stock</div>
        </div>
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
    $auth->logTrace($auth->getUserId(), 'entrees', 'pdf', 'entrees', $id, "Génération PDF entrée #" . $id);
    
    // Envoyer le PDF au navigateur
    $dompdf->stream('Bon_Entree_' . $entree['id'] . '.pdf', [
        'Attachment' => 0
    ]);
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la génération du PDF: ' . $e->getMessage();
    error_log('Erreur DomPDF: ' . $e->getMessage());
    
    header('Location: ' . BASE_URL . '/pages/entrees/view.php?id=' . $id);
    exit;
}