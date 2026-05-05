<?php
// DÉBUT ABSOLU - aucun espace, aucune ligne vide avant
ob_start(); // Démarre la mise en mémoire tampon

require_once __DIR__ . '/../../includes/header.php';

// Nettoyer tout ce qui a pu être envoyé avant
ob_clean();

$db = Database::getInstance();
$search = $_GET['search'] ?? '';

// Récupération des données
$sql = "SELECT * FROM armoires WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nom LIKE :search OR notes LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY nom ASC";

$stmt = $db->getConnection()->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$armoires = $stmt->fetchAll();

// Inclure TCPDF
require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';

// Créer une nouvelle instance de TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Définir les informations du document
$pdf->SetCreator('Gestion Armoires');
$pdf->SetAuthor('Gestion Armoires');
$pdf->SetTitle('Liste des Armoires');
$pdf->SetSubject('Export PDF');

// Supprimer l'en-tête et le pied de page par défaut
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Ajouter une page
$pdf->AddPage();

// Définir la police
$pdf->SetFont('helvetica', 'B', 16);

// Titre
$pdf->Cell(0, 10, 'Liste des Armoires', 0, 1, 'C');
$pdf->Ln(5);

// Date de génération
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, 'Généré le : ' . date('d/m/Y H:i'), 0, 1, 'R');
$pdf->Ln(5);

// Si recherche
if (!empty($search)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Recherche : ' . htmlspecialchars($search), 0, 1);
    $pdf->Ln(3);
}

// Créer le tableau
$pdf->SetFont('helvetica', 'B', 11);

// En-têtes du tableau
$header = array('ID', 'Nom', 'Notes', 'Date Création');
$w = array(15, 50, 80, 40);

// Couleur d'arrière-plan pour l'en-tête
$pdf->SetFillColor(230, 230, 230);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(128, 128, 128);
$pdf->SetLineWidth(0.1);

// En-têtes
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Contenu du tableau
$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0);

$fill = false;
foreach($armoires as $armoire) {
    // ID
    $pdf->Cell($w[0], 6, $armoire['id'], 'LR', 0, 'C', $fill);
    
    // Nom
    $pdf->Cell($w[1], 6, $armoire['nom'], 'LR', 0, 'L', $fill);
    
    // Notes (tronqué si trop long)
    $notes = $armoire['notes'] ?? '';
    if (strlen($notes) > 80) {
        $notes = substr($notes, 0, 77) . '...';
    }
    $pdf->Cell($w[2], 6, $notes, 'LR', 0, 'L', $fill);
    
    // Date création
    $pdf->Cell($w[3], 6, date('d/m/Y', strtotime($armoire['created_at'])), 'LR', 0, 'C', $fill);
    
    $pdf->Ln();
    $fill = !$fill;
}

// Fermer le tableau
$pdf->Cell(array_sum($w), 0, '', 'T');
$pdf->Ln(10);

// Statistiques
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 10, 'Total des armoires : ' . count($armoires), 0, 1);

// Pied de page
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');

// Nettoyer la mémoire tampon et générer le PDF
ob_end_clean();

// Générer le PDF
$filename = 'armoires_' . date('Y-m-d_H-i') . '.pdf';
$pdf->Output($filename, 'D');

exit();