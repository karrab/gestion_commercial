<?php
// Start output buffering to prevent any premature output
ob_start();

require_once __DIR__ . '/../../includes/header.php';

$auth->requirePermission('traces', 'view');
$db = Database::getInstance();

// Récupérer les informations de l'établissement depuis la table parametres
$sql_parametres = "SELECT nom_etablissement, logo, adresse, tel_fixe, tel_mobile, fax, email, site_web 
                   FROM parametres 
                   LIMIT 1";
try {
    $stmt_parametres = $db->getConnection()->prepare($sql_parametres);
    $stmt_parametres->execute();
    $parametres = $stmt_parametres->fetch(PDO::FETCH_ASSOC);
    
    if ($parametres) {
        $etablissement = [
            'nom' => $parametres['nom_etablissement'] ?? 'Nom Établissement',
            'adresse' => $parametres['adresse'] ?? 'Adresse non définie',
            'telephone' => $parametres['tel_fixe'] ?? ($parametres['tel_mobile'] ?? 'Non disponible'),
            'email' => $parametres['email'] ?? 'contact@exemple.com',
            'site_web' => $parametres['site_web'] ?? 'www.exemple.com',
            'logo' => $parametres['logo'] ?? 'logo.png'
        ];
        
        // Si on a les deux téléphones, on les combine
        if (!empty($parametres['tel_fixe']) && !empty($parametres['tel_mobile'])) {
            $etablissement['telephone'] = $parametres['tel_fixe'] . ' / ' . $parametres['tel_mobile'];
        }
    } else {
        // Valeurs par défaut si la table est vide
        $etablissement = [
            'nom' => 'Nom Établissement',
            'adresse' => 'Adresse non définie',
            'telephone' => '00 00 00 00 00',
            'email' => 'contact@exemple.com',
            'site_web' => 'www.exemple.com',
            'logo' => 'logo.png'
        ];
    }
} catch (Exception $e) {
    // En cas d'erreur, utiliser des valeurs par défaut
    $etablissement = [
        'nom' => 'Nom Établissement',
        'adresse' => 'Adresse non définie',
        'telephone' => '00 00 00 00 00',
        'email' => 'contact@exemple.com',
        'site_web' => 'www.exemple.com',
        'logo' => 'logo.png'
    ];
}

// Clear any output generated up to this point
ob_clean();

// Récupérer les mêmes paramètres que la page principale
$search = $_GET['search'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Requête identique à la page principale (sans pagination)
$sql = "SELECT t.*,
               u.nom as user_nom, u.prenom as user_prenom, u.login as user_login
        FROM traces t
        INNER JOIN users u ON t.user_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (t.description LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search OR t.module LIKE :search OR t.action LIKE :search OR t.table_name LIKE :search OR t.ip_address LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($user_id)) {
    $sql .= " AND t.user_id = :user_id";
    $params[':user_id'] = $user_id;
}

if (!empty($module)) {
    $sql .= " AND t.module = :module";
    $params[':module'] = $module;
}

if (!empty($action)) {
    $sql .= " AND t.action = :action";
    $params[':action'] = $action;
}

if (!empty($date_debut)) {
    $sql .= " AND DATE(t.created_at) >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND DATE(t.created_at) <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

$sql .= " ORDER BY t.created_at DESC LIMIT 5000"; // Limite pour l'export PDF

try {
    $stmt = $db->getConnection()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $traces = $stmt->fetchAll();
} catch (Exception $e) {
    // Clean buffer and show error
    ob_clean();
    die('Erreur lors de la récupération des données: ' . $e->getMessage());
}

// Actions avec icônes et couleurs (même configuration)
$action_config = [
    'create' => ['icon' => 'bi-plus-circle', 'color' => 'success', 'label' => 'Création'],
    'update' => ['icon' => 'bi-pencil', 'color' => 'warning', 'label' => 'Modification'],
    'delete' => ['icon' => 'bi-trash', 'color' => 'danger', 'label' => 'Suppression'],
    'view' => ['icon' => 'bi-eye', 'color' => 'info', 'label' => 'Consultation'],
    'login' => ['icon' => 'bi-box-arrow-in-right', 'color' => 'primary', 'label' => 'Connexion'],
    'logout' => ['icon' => 'bi-box-arrow-right', 'color' => 'secondary', 'label' => 'Déconnexion'],
    'export' => ['icon' => 'bi-download', 'color' => 'info', 'label' => 'Export'],
    'import' => ['icon' => 'bi-upload', 'color' => 'info', 'label' => 'Import'],
    'validate' => ['icon' => 'bi-check-circle', 'color' => 'success', 'label' => 'Validation'],
    'close' => ['icon' => 'bi-lock', 'color' => 'dark', 'label' => 'Clôture']
];

// Génération PDF avec TCPDF
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';

class PDF extends TCPDF {
    private $etablissement;
    
    public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false, $etablissement=null) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->etablissement = $etablissement;
    }
    
    // En-tête
    public function Header() {
        // Logo
        $image_file = __DIR__ . '/../../assets/images/' . ($this->etablissement['logo'] ?? 'logo.png');
        $logo_width = 30; // Largeur du logo
        $logo_x = 10; // Position X du logo
        $logo_y = 10; // Position Y du logo
        
        if (file_exists($image_file)) {
            $this->Image($image_file, $logo_x, $logo_y, $logo_width, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            // Si le logo n'existe pas, on utilise le logo par défaut
            $default_logo = __DIR__ . '/../../assets/images/logo.png';
            if (file_exists($default_logo)) {
                $this->Image($default_logo, $logo_x, $logo_y, $logo_width, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
        }
        
        // Informations de l'établissement à droite du logo
        $info_x = $logo_x + $logo_width + 5; // Commence après le logo avec une marge de 5mm
        $info_y = $logo_y;
        
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY($info_x, $info_y);
        $this->Cell(0, 5, $this->etablissement['nom'] ?? 'Nom Établissement', 0, 1);
        
        $this->SetFont('helvetica', '', 9);
        $this->SetX($info_x);
        
        // Adresse
        if (!empty($this->etablissement['adresse'])) {
            $this->Cell(0, 4, $this->etablissement['adresse'], 0, 1);
            $this->SetX($info_x);
        }
        
        // Téléphone
        if (!empty($this->etablissement['telephone'])) {
            $this->Cell(0, 4, 'Tél: ' . $this->etablissement['telephone'], 0, 1);
            $this->SetX($info_x);
        }
        
        // Email
        if (!empty($this->etablissement['email'])) {
            $this->Cell(0, 4, 'Email: ' . $this->etablissement['email'], 0, 1);
            $this->SetX($info_x);
        }
        
        // Site web
        if (!empty($this->etablissement['site_web'])) {
            $this->Cell(0, 4, 'Site: ' . $this->etablissement['site_web'], 0, 1);
        }
        
        // Titre du document (centré)
        $this->SetY($logo_y + 25); // Position Y après les informations
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 10, 'HISTORIQUE DES ACTIONS', 0, 1, 'C');
        
        // Sous-titre
        $this->SetFont('helvetica', 'I', 10);
        $this->Cell(0, 5, 'Rapport d\'audit et de traçabilité', 0, 1, 'C');
        
        // Ligne de séparation
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY() + 5, 287, $this->GetY() + 5);
        
        // Espace après la ligne
        $this->Ln(10);
    }
    
    // Pied de page
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        
        // Numéro de page centré
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        
        // Informations en bas à gauche
        $this->SetY(-15);
        $this->SetX(10);
        $this->Cell(0, 10, date('d/m/Y H:i:s'), 0, false, 'L', 0, '', 0, false, 'T', 'M');
        
        // Informations en bas à droite
        $this->SetY(-15);
        $this->SetX(-50);
        $this->Cell(0, 10, 'Document confidentiel', 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Clean any remaining output buffer before PDF generation
ob_clean();

// Création du PDF avec les informations de l'établissement
$pdf = new PDF('L', 'mm', 'A4', true, 'UTF-8', false, false, $etablissement);
$pdf->SetCreator('Système de Gestion');
$pdf->SetAuthor($etablissement['nom'] ?? 'Administration');
$pdf->SetTitle('Historique des Actions - ' . ($etablissement['nom'] ?? ''));
$pdf->SetSubject('Export historique des actions');
$pdf->SetKeywords('historique, actions, audit, traces, ' . ($etablissement['nom'] ?? ''));

// Marges
$pdf->SetMargins(15, 50, 15); // Marge supérieure augmentée pour l'en-tête
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 25);

// Ajout d'une page
$pdf->AddPage();

// Informations sur l'export
$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(0, 8, 'INFORMATIONS SUR L\'EXPORT', 1, 1, 'C', true);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(40, 6, 'Date d\'export :', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, date('d/m/Y à H:i:s'), 0, 1);
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell(40, 6, 'Généré par :', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, $_SESSION['user_nom'] . ' ' . $_SESSION['user_prenom'] ?? 'Système', 0, 1);
$pdf->SetFont('helvetica', '', 9);

$pdf->Ln(3);

// Filtres appliqués
$pdf->Cell(0, 6, 'FILTRES APPLIQUÉS :', 0, 1);
$pdf->SetFont('helvetica', 'I', 8);

$filtres = [];
if (!empty($search)) $filtres[] = "Recherche: \"$search\"";
if (!empty($user_id)) {
    // Récupérer le nom de l'utilisateur si possible
    $sql_user = "SELECT nom, prenom FROM users WHERE id = :user_id";
    try {
        $stmt_user = $db->getConnection()->prepare($sql_user);
        $stmt_user->bindValue(':user_id', $user_id);
        $stmt_user->execute();
        $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user_info) {
            $filtres[] = "Utilisateur: " . $user_info['prenom'] . ' ' . $user_info['nom'];
        } else {
            $filtres[] = "Utilisateur ID: $user_id";
        }
    } catch (Exception $e) {
        $filtres[] = "Utilisateur ID: $user_id";
    }
}
if (!empty($module)) $filtres[] = "Module: $module";
if (!empty($action)) $filtres[] = "Action: $action";
if (!empty($date_debut)) $filtres[] = "Date début: $date_debut";
if (!empty($date_fin)) $filtres[] = "Date fin: $date_fin";

if (empty($filtres)) {
    $pdf->Cell(0, 6, 'Aucun filtre spécifique (toutes les actions)', 0, 1);
} else {
    // Afficher les filtres sur plusieurs lignes si nécessaire
    $line = '';
    $line_height = 6;
    $max_width = 250; // Largeur maximale estimée
    
    foreach ($filtres as $filtre) {
        if ($pdf->GetStringWidth($line . $filtre . ' | ') < $max_width) {
            $line .= $filtre . ' | ';
        } else {
            $pdf->Cell(0, $line_height, rtrim($line, ' | '), 0, 1);
            $line = $filtre . ' | ';
        }
    }
    if (!empty($line)) {
        $pdf->Cell(0, $line_height, rtrim($line, ' | '), 0, 1);
    }
}

$pdf->Ln(5);

// Tableau
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.1);

// En-têtes du tableau (ajustés pour format paysage)
$headers = array('ID', 'Date/Heure', 'Utilisateur', 'Module', 'Action', 'Description', 'Table', 'IP');
$w = array(12, 25, 35, 22, 22, 80, 22, 25); // Largeurs ajustées

for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Contenu du tableau
$pdf->SetFont('helvetica', '', 8);
$fill = false;

foreach ($traces as $trace) {
    // Vérifier si on dépasse la page
    if ($pdf->GetY() > 180) { // Hauteur approximative avant le bas de page
        $pdf->AddPage();
        // Répéter l'en-tête du tableau sur la nouvelle page
        $pdf->SetFont('helvetica', 'B', 9);
        for ($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 8);
        $fill = false;
    }
    
    $act = $trace['action'];
    $config = $action_config[$act] ?? ['label' => $act];
    
    // ID
    $pdf->Cell($w[0], 6, '#' . $trace['id'], 'LR', 0, 'C', $fill);
    
    // Date/Heure
    $date_heure = date('d/m/Y', strtotime($trace['created_at'])) . "\n" . 
                  date('H:i:s', strtotime($trace['created_at']));
    $pdf->Cell($w[1], 6, $date_heure, 'LR', 0, 'C', $fill);
    
    // Utilisateur
    $user = $trace['user_prenom'] . ' ' . $trace['user_nom'] . "\n" . 
            '(' . $trace['user_login'] . ')';
    $pdf->Cell($w[2], 6, $user, 'LR', 0, 'L', $fill);
    
    // Module
    $pdf->Cell($w[3], 6, $trace['module'], 'LR', 0, 'C', $fill);
    
    // Action
    $pdf->Cell($w[4], 6, $config['label'], 'LR', 0, 'C', $fill);
    
    // Description (limiter la longueur)
    $description = !empty($trace['description']) ? $trace['description'] : '-';
    if (strlen($description) > 80) {
        $description = substr($description, 0, 77) . '...';
    }
    $pdf->Cell($w[5], 6, $description, 'LR', 0, 'L', $fill);
    
    // Table
    $pdf->Cell($w[6], 6, $trace['table_name'] ?? '-', 'LR', 0, 'C', $fill);
    
    // IP
    $pdf->Cell($w[7], 6, $trace['ip_address'] ?? '-', 'LR', 0, 'C', $fill);
    
    $pdf->Ln();
    $fill = !$fill;
}

// Fermeture du tableau
$pdf->Cell(array_sum($w), 0, '', 'T');
$pdf->Ln(10);

// Statistiques
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 10, 'STATISTIQUES DE L\'EXPORT', 0, 1);
$pdf->SetFont('helvetica', '', 9);

// Compter les actions par type
$actions_count = [];
foreach ($traces as $trace) {
    $action_type = $trace['action'];
    if (!isset($actions_count[$action_type])) {
        $actions_count[$action_type] = 0;
    }
    $actions_count[$action_type]++;
}

$pdf->Cell(0, 6, 'Nombre total d\'actions exportées : ' . count($traces), 0, 1);
$pdf->Cell(0, 6, 'Période couverte : du ' . ($date_debut ?: 'début des enregistrements') . ' au ' . ($date_fin ?: date('d/m/Y')), 0, 1);

if (!empty($actions_count)) {
    $pdf->Ln(2);
    $pdf->Cell(0, 6, 'Répartition par type d\'action :', 0, 1);
    
    foreach ($actions_count as $action_type => $count) {
        $config = $action_config[$action_type] ?? ['label' => $action_type];
        $percentage = count($traces) > 0 ? round(($count / count($traces)) * 100, 1) : 0;
        $pdf->Cell(5, 6, '', 0, 0);
        $pdf->Cell(40, 6, '- ' . $config['label'] . ' :', 0, 0);
        $pdf->Cell(20, 6, $count . ' actions', 0, 0);
        $pdf->Cell(0, 6, '(' . $percentage . '%)', 0, 1);
    }
}

// Clear output buffer completely before generating PDF
while (ob_get_level()) {
    ob_end_clean();
}

// Génération du PDF
$filename = 'historique_actions_' . ($etablissement['nom'] ?? '') . '_' . date('Ymd_His') . '.pdf';
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename); // Nettoyer le nom du fichier
$pdf->Output($filename, 'I');
exit; // Ensure no further output