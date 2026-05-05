<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$conn = new mysqli("localhost", "root", "sim!admin,", "stock_materiel");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$file = __DIR__ . '/../fournisseurs.xls';

if (!file_exists($file)) {
    die("File not found: " . $file);
}

$spreadsheet = IOFactory::load($file);
$rows = $spreadsheet->getActiveSheet()->toArray();

$total = count($rows);
$inserted = 0;
$updated = 0;
$errors = 0;

$stmt = $conn->prepare("
INSERT INTO fournisseurs 
(id, code_frs, nom_complet, matricule_fiscal, adresse, ville, pays, code_postal, tel1, tel2, notes, actif)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
code_frs = VALUES(code_frs),
nom_complet = VALUES(nom_complet),
matricule_fiscal = VALUES(matricule_fiscal),
adresse = VALUES(adresse),
ville = VALUES(ville),
pays = VALUES(pays),
code_postal = VALUES(code_postal),
tel1 = VALUES(tel1),
tel2 = VALUES(tel2),
notes = VALUES(notes),
actif = VALUES(actif)
");

foreach ($rows as $index => $row) {

    if ($index == 0) continue;

    $id = trim($row[0]);
    $code_frs = trim($row[1]);
    $nom_complet = trim($row[2]);
    $matricule_fiscal = trim($row[3]);
    $adresse = trim($row[4]);
    $ville = trim($row[5]);
    $pays = trim($row[6]);
    $code_postal = trim($row[7]);
    $tel1 = trim($row[8]);
    $tel2 = trim($row[9]);
    $notes = trim($row[10]);
    $actif = trim($row[11]);

    if (empty($code_frs)) continue;

    $stmt->bind_param(
        "ssssssssssss",
        $id,
        $code_frs,
        $nom_complet,
        $matricule_fiscal,
        $adresse,
        $ville,
        $pays,
        $code_postal,
        $tel1,
        $tel2,
        $notes,
        $actif
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows == 1) {
            $inserted++;
        } else {
            $updated++;
        }
    } else {
        $errors++;
        echo "Erreur ligne $index: " . $stmt->error . "<br>";
    }
}

echo "<h3>Résultat Import</h3>";
echo "Total: $total <br>";
echo "Inserted: $inserted <br>";
echo "Updated: $updated <br>";
echo "Errors: $errors <br>";