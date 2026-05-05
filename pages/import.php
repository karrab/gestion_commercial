<?php

//require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$host = "localhost";
$user = "root";
$password = "sim!admin,";
$dbname = "stock_materiel";

$conn = new mysqli($host, $user, $password, $dbname);

//$excelFile = "fournisseurs.xlsx";
$excelFile = __DIR__ . '/../fournisseurs.xls';
$spreadsheet = IOFactory::load($excelFile);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

foreach ($rows as $index => $row) {

    if ($index == 0) continue; // تجاهل header

    $id = $row[0];
    $code_frs = $row[1];
    $nom_complet = $row[2];
    $matricule_fiscal = $row[3];
    $adresse = $row[4];
    $ville = $row[5];
    $pays = $row[6];
    $code_postal = $row[7];
    $tel1 = $row[8];
    $tel2 = $row[9];
    $notes = $row[10];
    $actif = $row[11];
    $created_at = $row[12];
    $updated_at = $row[13];

    $sql = "INSERT INTO fournisseurs (id, code_frs, nom_complet, matricule_fiscal, adresse, ville, pays, code_postal, tel1, tel2, notes, actif)
VALUES ('$id', '$code_frs', '$nom_complet', '$matricule_fiscal', '$adresse', '$ville', '$pays', '$code_postal', '$tel1', '$tel2', '$notes', '$actif')";

if (!$conn->query($sql)) {
    echo "Error: " . $conn->error . "<br>";
}
}

echo "Import terminé";
?>