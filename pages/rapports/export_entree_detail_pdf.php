<?php
ob_start(); // Démarre la temporisation de sortie

require_once __DIR__ . '/../../includes/header.php';
$auth->requirePermission('rapports', 'export');

// Vérifier qu'aucun contenu n'a été envoyé avant
if (headers_sent()) {
    die('Erreur : Les en-têtes HTTP ont déjà été envoyés. Vérifiez les espaces blancs dans vos fichiers PHP.');
}

require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';

$db = Database::getInstance();

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$fournisseur_id = $_GET['fournisseur_id'] ?? '';
$code_article = $_GET['code_article'] ?? '';
$designation = $_GET['designation'] ?? '';

// Requête pour les lignes détaillées des articles
$sql_detail = "SELECT e.id as entree_id, e.date, f.nom_complet as fournisseur,
                      le.code_article, le.designation, le.qte_entree
               FROM entrees e
               INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
               INNER JOIN ligne_entrees le ON e.id = le.entree_id
               WHERE e.date BETWEEN :date_debut AND :date_fin";

$params_detail = [':date_debut' => $date_debut, ':date_fin' => $date_fin];

if (!empty($fournisseur_id)) {
    $sql_detail .= " AND e.fournisseur_id = :fournisseur_id";
    $params_detail[':fournisseur_id'] = $fournisseur_id;
}

if (!empty($code_article)) {
    $sql_detail .= " AND le.code_article LIKE :code_article";
    $params_detail[':code_article'] = "%$code_article%";
}

if (!empty($designation)) {
    $sql_detail .= " AND le.designation LIKE :designation";
    $params_detail[':designation'] = "%$designation%";
}

$sql_detail .= " ORDER BY e.date DESC, le.code_article";

$stmt_detail = $db->getConnection()->prepare($sql_detail);
foreach ($params_detail as $key => $value) {
    $stmt_detail->bindValue($key, $value);
}
$stmt_detail->execute();
$lignes_detail = $stmt_detail->fetchAll();

// Calcul des totaux
$total_qte_detail = 0;
$total_articles_detail = count($lignes_detail);
foreach ($lignes_detail as $ligne) {
    $total_qte_detail += $ligne['qte_entree'];
}

// Fonction pour formater les nombres sans virgule et zéros inutiles
function formatNombre($nombre) {
    if (is_numeric($nombre)) {
        // Vérifier si c'est un nombre entier
        if (floor($nombre) == $nombre) {
            return number_format($nombre, 0, '', ' ');
        } else {
            // Pour les nombres décimaux, on garde 2 décimales mais on enlève les .00
            $formatted = rtrim(number_format($nombre, 2, ',', ' '), '0');
            $formatted = rtrim($formatted, ',');
            return $formatted;
        }
    }
    return $nombre;
}

// Paramètres de l'établissement
$db->prepare("SELECT * FROM parametres LIMIT 1");
$parametres = $db->fetch();

// Stocker les paramètres dans une variable globale pour l'en-tête
$GLOBALS['parametres'] = $parametres;

// Création du PDF
class MYPDF extends TCPDF {
    // En-tête de page
    public function Header() {
        // Logo
        $logo_path = __DIR__ . '/../../uploads/' . $GLOBALS['parametres']['logo'];
        if (isset($GLOBALS['parametres']['logo']) && !empty($GLOBALS['parametres']['logo']) && file_exists($logo_path)) {
            $this->Image($logo_path, 10, 10, 25, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $x_start = 40;
        } else {
            $x_start = 10;
        }
        
        // Titre
        $this->SetFont('helvetica', 'B', 16);
        $this->SetXY($x_start, 10);
        $this->Cell(0, 15, 'Rapport détaillé des entrées d\'articles', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Sous-titre
        $this->SetFont('helvetica', '', 10);
        $this->SetXY($x_start, 25);
        $this->Cell(0, 15, 'Généré le ' . date('d/m/Y à H:i'), 0, false, 'R', 0, '', 0, false, 'M', 'M');
    }
    
    // Pied de page
    public function Footer() {
        // Position à 15 mm du bas
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        
        // Numéro de page
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Nettoyer toute sortie précédente
ob_end_clean();

// Création du document PDF
$pdf = new MYPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

// Information du document
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Système de gestion de stock');
$pdf->SetTitle('Rapport détaillé des entrées');
$pdf->SetSubject('Rapport des entrées d\'articles');

// Marges
$pdf->SetMargins(10, 40, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Saut de page automatique
$pdf->SetAutoPageBreak(TRUE, 15);

// Ajouter une page
$pdf->AddPage();

// Informations de l'établissement
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, htmlspecialchars($parametres['nom_etablissement']), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, htmlspecialchars($parametres['adresse']), 0, 1, 'C');
if (!empty($parametres['tel_fixe'])) {
    $pdf->Cell(0, 6, 'Tél: ' . htmlspecialchars($parametres['tel_fixe']), 0, 1, 'C');
}
if (!empty($parametres['email'])) {
    $pdf->Cell(0, 6, 'Email: ' . htmlspecialchars($parametres['email']), 0, 1, 'C');
}
$pdf->Ln(5);

// Informations sur le filtre
$info_filtre = "Période : " . date('d/m/Y', strtotime($date_debut)) . " au " . date('d/m/Y', strtotime($date_fin));

if (!empty($fournisseur_id)) {
    $db->prepare("SELECT nom_complet FROM fournisseurs WHERE id = :id");
    $db->bind(':id', $fournisseur_id);
    $fournisseur = $db->fetch();
    if ($fournisseur) {
        $info_filtre .= " | Fournisseur : " . $fournisseur['nom_complet'];
    }
}

if (!empty($code_article)) {
    $info_filtre .= " | Code article : " . $code_article;
}

if (!empty($designation)) {
    $info_filtre .= " | Désignation : " . $designation;
}

$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, $info_filtre, 0, 1, 'L', true);
$pdf->Ln(3);

// Tableau des données
$pdf->SetFont('helvetica', '', 8);

// En-tête du tableau
$header = array('Date', 'Fournisseur', 'Code Article', 'Désignation', 'Quantité');
$w = array(20, 45, 30, 120, 25); // Largeurs des colonnes

// Couleurs, largeur de ligne et police en gras
$pdf->SetFillColor(200, 200, 200);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(128, 128, 128);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('', 'B');

// En-tête
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Données
$pdf->SetFont('', '');
$pdf->SetFillColor(255, 255, 255);
$fill = false;

foreach ($lignes_detail as $ligne) {
    // Gestion du saut de page automatique
    if ($pdf->GetY() > 170) { // Hauteur avant le bas de page
        $pdf->AddPage();
        
        // Réafficher l'en-tête du tableau
        $pdf->SetFont('', 'B');
        $pdf->SetFillColor(200, 200, 200);
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('', '');
        $pdf->SetFillColor(255, 255, 255);
        $fill = false;
    }
    
    $pdf->Cell($w[0], 6, date('d/m/Y', strtotime($ligne['date'])), 'LR', 0, 'L', $fill);
    $pdf->Cell($w[1], 6, mb_substr($ligne['fournisseur'], 0, 40, 'UTF-8'), 'LR', 0, 'L', $fill);
    $pdf->SetFont('', 'B');
    $pdf->Cell($w[2], 6, mb_substr($ligne['code_article'], 0, 20, 'UTF-8'), 'LR', 0, 'L', $fill);
    $pdf->SetFont('', '');
    $pdf->Cell($w[3], 6, mb_substr($ligne['designation'], 0, 80, 'UTF-8'), 'LR', 0, 'L', $fill);
    
    // Utiliser la fonction formatNombre pour afficher sans virgule et zéros inutiles
    $pdf->Cell($w[4], 6, formatNombre($ligne['qte_entree']), 'LR', 0, 'R', $fill);
    $pdf->Ln();
    
    $fill = !$fill;
}

// Fermeture du tableau
$pdf->Cell(array_sum($w), 0, '', 'T');
$pdf->Ln(5);

// Totaux
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($w[0] + $w[1] + $w[2], 8, 'TOTAL :', 0, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($w[3], 8, $total_articles_detail . ' article(s)', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 10);
// Utiliser formatNombre pour le total aussi
$pdf->Cell($w[4], 8, formatNombre($total_qte_detail), 0, 1, 'R');

// Résumé
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 5, "Résumé du rapport :\n" . 
                     "- Nombre d'articles : " . $total_articles_detail . "\n" .
                     "- Quantité totale : " . formatNombre($total_qte_detail) . " unité(s)\n" .
                     "- Période couverte : " . date('d/m/Y', strtotime($date_debut)) . " au " . date('d/m/Y', strtotime($date_fin)), 
                     0, 'L', false);

// Vérifier qu'aucune sortie n'a été générée
if (ob_get_contents()) {
    ob_end_clean();
}

// Output du PDF
$pdf->Output('rapport_entrees_detail_' . date('Y-m-d_His') . '.pdf', 'I');