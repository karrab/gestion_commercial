<?php
ob_start();

$base_dir = realpath(__DIR__ . '/../../');

require_once $base_dir . '/config/config.php';
require_once $base_dir . '/vendor/autoload.php';

session_start();

require_once $base_dir . '/classes/Database.php';
require_once $base_dir . '/classes/Auth.php';
require_once $base_dir . '/classes/HistoriqueArticle.php';

$db   = Database::getInstance();
$auth = new Auth();

if (!$auth->hasPermission('mouvements', 'view')) {
    ob_end_clean();
    header('HTTP/1.0 403 Forbidden');
    echo 'Accès interdit';
    exit;
}

// Paramètres de filtrage (identiques à index.php)
$code_article = $_GET['code_article'] ?? '';
$designation  = $_GET['designation']  ?? '';
$operation    = $_GET['operation']    ?? '';
$date_debut   = $_GET['date_debut']   ?? '';
$date_fin     = $_GET['date_fin']     ?? '';
$article_id   = $_GET['article_id']   ?? '';
$dt_search    = trim($_GET['dt_search'] ?? ''); // terme de recherche DataTables côté client

$filters = [];
if (!empty($code_article)) $filters['code_article'] = $code_article;
if (!empty($designation))  $filters['designation']  = $designation;
if (!empty($operation))    $filters['operation']    = $operation;
if (!empty($date_debut))   $filters['date_debut']   = $date_debut;
if (!empty($date_fin))     $filters['date_fin']     = $date_fin;
if (!empty($article_id))   $filters['article_id']   = $article_id;

$historique = new HistoriqueArticle();
$mouvements = $historique->getAllWithoutPagination($filters);
$stats      = $historique->getStatistiques($date_debut ?: null, $date_fin ?: null);

// Appliquer le filtre de recherche DataTables (client-side → server-side pour le PDF)
if (!empty($dt_search)) {
    $s = strtolower($dt_search);
    $mouvements = array_values(array_filter($mouvements, function ($m) use ($s) {
        return stripos($m['code_article']       ?? '', $s) !== false
            || stripos($m['designation']         ?? '', $s) !== false
            || stripos($m['operation']           ?? '', $s) !== false
            || stripos($m['user_nom']            ?? '', $s) !== false
            || stripos($m['user_prenom']         ?? '', $s) !== false
            || stripos($m['commentaire']         ?? '', $s) !== false;
    }));
}

// Paramètres établissement
$parametres        = $db->query("SELECT * FROM parametres LIMIT 1")->fetch();
$nom_etablissement = $parametres['nom_etablissement'] ?? 'Stock Matériel';
$adresse           = $parametres['adresse']   ?? '';
$tel_fixe          = $parametres['tel_fixe']  ?? '';
$email_param       = $parametres['email']     ?? '';

// Logo
$logo_path = '';
if (!empty($parametres['logo'])) {
    $uploaded = $base_dir . '/uploads/logo/' . $parametres['logo'];
    if (file_exists($uploaded)) $logo_path = $uploaded;
}
if (empty($logo_path) && file_exists($base_dir . '/assets/images/logo.png')) {
    $logo_path = $base_dir . '/assets/images/logo.png';
}

// Libellés filtres actifs
$filtres_text = [];
if (!empty($date_debut) && !empty($date_fin))
    $filtres_text[] = 'Période : ' . date('d/m/Y', strtotime($date_debut)) . ' au ' . date('d/m/Y', strtotime($date_fin));
elseif (!empty($date_debut))
    $filtres_text[] = 'À partir du : ' . date('d/m/Y', strtotime($date_debut));
elseif (!empty($date_fin))
    $filtres_text[] = "Jusqu'au : " . date('d/m/Y', strtotime($date_fin));
if (!empty($code_article)) $filtres_text[] = 'Code : ' . $code_article;
if (!empty($designation))  $filtres_text[] = 'Désignation : ' . $designation;
if (!empty($operation)) {
    $op_labels_header = ['entree'=>'Entrée','sortie'=>'Sortie','retour'=>'Retour Employé','retour_fournisseur'=>'Retour Fournisseur'];
    $filtres_text[] = 'Opération : ' . ($op_labels_header[$operation] ?? $operation);
}
if (!empty($dt_search)) $filtres_text[] = 'Recherche : "' . $dt_search . '"';

function formatQte($n) {
    $f = floatval($n);
    return $f == intval($f)
        ? number_format($f, 0, '', ' ')
        : rtrim(rtrim(number_format($f, 2, ',', ' '), '0'), ',');
}

ob_end_clean();

// -----------------------------------------------------------------------
// Tentative TCPDF
// -----------------------------------------------------------------------
$tcpdf_paths = [
    $base_dir . '/vendor/tecnickcom/tcpdf/tcpdf.php',
    $base_dir . '/vendor/tcpdf/tcpdf.php',
    $base_dir . '/tcpdf/tcpdf.php',
];
$tcpdf_loaded = false;
foreach ($tcpdf_paths as $p) {
    if (file_exists($p)) { require_once $p; $tcpdf_loaded = true; break; }
}

// -----------------------------------------------------------------------
// Fallback HTML/impression si TCPDF absent
// -----------------------------------------------------------------------
if (!$tcpdf_loaded) {
    header('Content-Type: text/html; charset=utf-8');
    $op_colors = ['entree'=>'#28a745','sortie'=>'#dc3545','retour'=>'#17a2b8','retour_fournisseur'=>'#e67e00'];
    $op_labels = ['entree'=>'Entrée','sortie'=>'Sortie','retour'=>'Retour Emp.','retour_fournisseur'=>'Ret. Fourn.'];
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
    <title>Historique mouvements</title>
    <style>
        body{font-family:Arial,sans-serif;margin:15px;font-size:10px}
        h1{font-size:15px;text-align:center;margin-bottom:3px}
        h2{font-size:11px;text-align:center;color:#555;margin-top:0;margin-bottom:6px}
        .info{text-align:center;font-size:9px;color:#666;margin-bottom:8px}
        .filters{background:#f8f9fa;padding:4px 8px;border-radius:3px;margin-bottom:8px;font-size:9px}
        .stats{display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap}
        .stat{padding:5px 10px;border-radius:3px;text-align:center;min-width:80px;font-size:9px}
        .stat span{display:block;font-size:16px;font-weight:bold}
        .s-ent{background:#d4edda;color:#155724}
        .s-sor{background:#f8d7da;color:#721c24}
        .s-ret{background:#d1ecf1;color:#0c5460}
        .s-rf {background:#fff3cd;color:#856404}
        table{width:100%;border-collapse:collapse;font-size:8px}
        th{background:#3b5998;color:#fff;padding:4px 5px;text-align:center;border:1px solid #2d4373;white-space:nowrap}
        td{border:1px solid #dee2e6;padding:3px 4px;vertical-align:top;word-break:break-word}
        tr:nth-child(even){background:#f8f9fa}
        .tr{text-align:right}.tc{text-align:center}.tl{text-align:left}
        tfoot td{background:#e9ecef;font-weight:bold}
        @media print{button{display:none}body{margin:5px}}
    </style></head><body>';
    echo '<h1>' . htmlspecialchars($nom_etablissement) . '</h1>';
    echo '<h2>Historique des mouvements de stock</h2>';
    echo '<div class="info">Généré le ' . date('d/m/Y à H:i') . ' &mdash; ' . count($mouvements) . ' mouvement(s)</div>';
    if (!empty($filtres_text)) {
        echo '<div class="filters"><strong>Filtres :</strong> ' . implode(' &nbsp;|&nbsp; ', array_map('htmlspecialchars', $filtres_text)) . '</div>';
    }
    if (!empty($stats)) {
        echo '<div class="stats">';
        $op_labels_full = ['entree'=>'Entrées','sortie'=>'Sorties','retour'=>'Retours Emp.','retour_fournisseur'=>'Retours Fourn.'];
        foreach ($stats as $s) {
            $cls = ['entree'=>'s-ent','sortie'=>'s-sor','retour'=>'s-ret','retour_fournisseur'=>'s-rf'][$s['operation']] ?? '';
            $lbl = $op_labels_full[$s['operation']] ?? $s['operation'];
            echo '<div class="stat ' . $cls . '"><span>' . formatQte($s['nombre_mouvements']) . '</span>' . htmlspecialchars($lbl) . '<br>' . formatQte($s['quantite_totale']) . ' u.</div>';
        }
        echo '</div>';
    }
    echo '<table><thead><tr>
        <th>Date</th><th>Code</th><th>Désignation</th><th>Opération</th>
        <th class="tr">Qté</th><th class="tr">±</th><th class="tr">St. Avant</th><th class="tr">St. Après</th>
        <th class="tr">Min</th><th class="tr">Max</th><th>Utilisateur</th><th>Commentaire</th>
    </tr></thead><tbody>';
    foreach ($mouvements as $m) {
        $op    = $m['operation'];
        $lbl   = $op_labels[$op]   ?? $op;
        $col   = $op_colors[$op]   ?? '#000';
        $qte   = formatQte($m['qte']);
        $signe = in_array($op, ['entree','retour']) ? '+' . $qte : '-' . $qte;
        $user  = trim(($m['user_nom'] ?? '') . ' ' . ($m['user_prenom'] ?? ''));
        $comm  = $m['commentaire'] ?? '';
        echo '<tr>
            <td class="tc" style="white-space:nowrap">' . date('d/m/Y H:i', strtotime($m['date_operation'])) . '</td>
            <td>' . htmlspecialchars($m['code_article']) . '</td>
            <td>' . htmlspecialchars($m['designation']) . '</td>
            <td class="tc" style="color:' . $col . ';font-weight:bold">' . htmlspecialchars($lbl) . '</td>
            <td class="tr">' . $qte . '</td>
            <td class="tr" style="color:' . $col . ';font-weight:bold">' . $signe . '</td>
            <td class="tr">' . formatQte($m['stock_avant_operation']) . '</td>
            <td class="tr"><strong>' . formatQte($m['stock_apres_operation']) . '</strong></td>
            <td class="tr">' . formatQte($m['stock_min']) . '</td>
            <td class="tr">' . formatQte($m['stock_max']) . '</td>
            <td>' . htmlspecialchars($user) . '</td>
            <td>' . htmlspecialchars($comm ?: '-') . '</td>
        </tr>';
    }
    echo '</tbody></table>
    <script>window.onload=function(){window.print();}</script>
    </body></html>';
    exit;
}

// -----------------------------------------------------------------------
// TCPDF - Classe personnalisée avec en-tête et pied de page
// -----------------------------------------------------------------------
class MouvementsPDF extends TCPDF {
    public $nom_etablissement = '';
    public $adresse           = '';
    public $tel_fixe          = '';
    public $email_param       = '';
    public $logo_path         = '';
    public $filtres_text      = [];
    public $total_mouvements  = 0;

    public function Header() {
        if (!empty($this->logo_path) && file_exists($this->logo_path)) {
            $this->Image($this->logo_path, 10, 8, 25, 0, '', '', 'T', false, 300);
        }
        $this->SetFont('helvetica', 'B', 15);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, $this->nom_etablissement, 0, 1, 'C');

        $this->SetFont('helvetica', 'I', 11);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 6, 'Historique des mouvements de stock', 0, 1, 'C');

        $info = [];
        if (!empty($this->adresse))     $info[] = $this->adresse;
        if (!empty($this->tel_fixe))    $info[] = 'Tél : ' . $this->tel_fixe;
        if (!empty($this->email_param)) $info[] = 'Email : ' . $this->email_param;
        if ($info) {
            $this->SetFont('helvetica', '', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, implode('  |  ', $info), 0, 1, 'C');
        }

        $this->SetDrawColor(59, 89, 152);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY() + 1, $this->GetPageWidth() - 10, $this->GetY() + 1);
        $this->Ln(4);

        if (!empty($this->filtres_text)) {
            $this->SetFont('helvetica', '', 8);
            $this->SetTextColor(60, 60, 60);
            $this->Cell(0, 5, 'Filtres : ' . implode('  |  ', $this->filtres_text), 0, 1, 'L');
        }
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Généré le ' . date('d/m/Y à H:i') . '  —  ' . $this->total_mouvements . ' mouvement(s)', 0, 1, 'R');
        $this->Ln(2);
    }

    public function Footer() {
        $this->SetY(-12);
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new MouvementsPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Système de Gestion');
$pdf->SetAuthor('Système de Gestion');
$pdf->SetTitle('Historique mouvements de stock');

$pdf->nom_etablissement = $nom_etablissement;
$pdf->adresse           = $adresse;
$pdf->tel_fixe          = $tel_fixe;
$pdf->email_param       = $email_param;
$pdf->logo_path         = $logo_path;
$pdf->filtres_text      = $filtres_text;
$pdf->total_mouvements  = count($mouvements);

$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetMargins(10, 55, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(8);
$pdf->SetAutoPageBreak(false); // géré manuellement pour contrôler le saut de page par ligne
$pdf->AddPage();

// ---- Bloc statistiques ----
if (!empty($stats)) {
    $op_labels_pdf = ['entree'=>'Entrées','sortie'=>'Sorties','retour'=>'Retours Emp.','retour_fournisseur'=>'Retours Fourn.'];
    $fills_stat    = ['entree'=>[212,237,218],'sortie'=>[248,215,218],'retour'=>[209,236,241],'retour_fournisseur'=>[255,243,205]];
    $page_w        = $pdf->GetPageWidth() - 20;
    $col_w         = $page_w / max(count($stats), 1);

    $pdf->SetFont('helvetica', 'B', 8);
    foreach ($stats as $s) {
        $fc = $fills_stat[$s['operation']] ?? [240,240,240];
        $pdf->SetFillColor($fc[0], $fc[1], $fc[2]);
        $pdf->Cell($col_w, 7, $op_labels_pdf[$s['operation']] ?? $s['operation'], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('helvetica', 'B', 9);
    foreach ($stats as $s) {
        $fc = $fills_stat[$s['operation']] ?? [240,240,240];
        $pdf->SetFillColor($fc[0], $fc[1], $fc[2]);
        $pdf->Cell($col_w, 7, formatQte($s['nombre_mouvements']) . ' mvt  /  ' . formatQte($s['quantite_totale']) . ' u.', 1, 0, 'C', true);
    }
    $pdf->Ln(6);
}

// -----------------------------------------------------------------------
// Tableau des mouvements
// -----------------------------------------------------------------------
// Largeurs colonnes — total = 277 mm (A4 paysage, marges 10mm)
// Date:22 | Code:20 | Désig:50 | Op:18 | Qté:12 | ±:12 | StAvant:16 | StAprès:16 | Min:12 | Max:12 | User:25 | Comm:62
$w = [22, 20, 50, 18, 12, 12, 16, 16, 12, 12, 25, 62];
$headers = ['Date','Code','Désignation','Opération','Quantité','±','St. Avant','St. Après','Min','Max','Utilisateur','Commentaire'];

// Fonction pour dessiner l'en-tête du tableau (appelée aussi après saut de page)
$drawHeader = function () use ($pdf, $w, $headers) {
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(59, 89, 152);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(59, 89, 152);
    $pdf->SetLineWidth(0.3);
    for ($i = 0; $i < count($headers); $i++) {
        $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetDrawColor(180, 180, 180);
};

$drawHeader();

// Constantes opérations
$op_abbr = ['entree'=>'ENT','sortie'=>'SOR','retour'=>'RET','retour_fournisseur'=>'RF'];
$op_rgb  = ['entree'=>[40,167,69],'sortie'=>[220,53,69],'retour'=>[23,162,184],'retour_fournisseur'=>[255,153,0]];

$line_h = 5;   // hauteur de base par ligne de texte
$fill   = false;
$page_bottom = $pdf->GetPageHeight() - 15; // limite basse avant pied de page

foreach ($mouvements as $m) {
    $op    = $m['operation'];
    $rgb   = $op_rgb[$op]  ?? [0,0,0];
    $abbr  = $op_abbr[$op] ?? strtoupper(substr($op, 0, 3));
    $qte   = formatQte($m['qte']);
    $signe = in_array($op, ['entree','retour']) ? '+' . $qte : '-' . $qte;
    $desig = $m['designation'];
    $user  = trim(($m['user_nom'] ?? '') . ' ' . ($m['user_prenom'] ?? ''));
    $comm  = trim($m['commentaire'] ?? '');

    // Calculer la hauteur nécessaire pour désignation et commentaire
    $pdf->SetFont('helvetica', '', 6.5);
    $h_desig = $pdf->getStringHeight($w[2],  $desig);
    $h_comm  = $comm ? $pdf->getStringHeight($w[11], $comm) : $line_h;
    $row_h   = max($line_h, $h_desig, $h_comm);

    // Saut de page si nécessaire
    if ($pdf->GetY() + $row_h > $page_bottom) {
        $pdf->AddPage();
        $drawHeader();
        $pdf->SetFont('helvetica', '', 6.5);
    }

    $y0 = $pdf->GetY();

    // Couleur de fond alternée
    $pdf->SetFillColor($fill ? 248 : 255, $fill ? 249 : 255, $fill ? 250 : 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 6.5);

    // [0] Date
    $pdf->Cell($w[0], $row_h, date('d/m/Y H:i', strtotime($m['date_operation'])), 'LRB', 0, 'C', $fill);

    // [1] Code
    $pdf->Cell($w[1], $row_h, $m['code_article'], 'LRB', 0, 'L', $fill);

    // [2] Désignation — MultiCell, texte complet sans troncature
    $xc = $pdf->GetX();
    $pdf->MultiCell($w[2], $line_h, $desig, 'LRB', 'L', $fill, 0, $xc, $y0, true, 0, false, true, $row_h, 'T');
    $pdf->SetXY($xc + $w[2], $y0);

    // [3] Opération (colorée, gras)
    $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell($w[3], $row_h, $abbr, 'LRB', 0, 'C', $fill);
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(0, 0, 0);

    // [4] Quantité
    $pdf->Cell($w[4], $row_h, $qte, 'LRB', 0, 'R', $fill);

    // [5] ±
    $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->Cell($w[5], $row_h, $signe, 'LRB', 0, 'R', $fill);
    $pdf->SetTextColor(0, 0, 0);

    // [6] Stock Avant
    $pdf->Cell($w[6], $row_h, formatQte($m['stock_avant_operation']), 'LRB', 0, 'R', $fill);

    // [7] Stock Après (gras)
    $pdf->SetFont('helvetica', 'B', 6.5);
    $pdf->Cell($w[7], $row_h, formatQte($m['stock_apres_operation']), 'LRB', 0, 'R', $fill);
    $pdf->SetFont('helvetica', '', 6.5);

    // [8] Min
    $pdf->Cell($w[8], $row_h, formatQte($m['stock_min']), 'LRB', 0, 'R', $fill);

    // [9] Max
    $pdf->Cell($w[9], $row_h, formatQte($m['stock_max']), 'LRB', 0, 'R', $fill);

    // [10] Utilisateur
    $pdf->Cell($w[10], $row_h, $user, 'LRB', 0, 'L', $fill);

    // [11] Commentaire — MultiCell, texte complet, dernière cellule (ln=1)
    $xc = $pdf->GetX();
    $pdf->MultiCell($w[11], $line_h, $comm ?: '-', 'LRB', 'L', $fill, 1, $xc, $y0, true, 0, false, true, $row_h, 'T');

    // Après le dernier MultiCell (ln=1) : Y = y0 + row_h, X = marge gauche ✓
    $fill = !$fill;
}

// Fermeture tableau
$pdf->Cell(array_sum($w), 0, '', 'T');

$pdf->Output('mouvements_' . date('Ymd_His') . '.pdf', 'I');
exit;