<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(null);
    exit;
}

$employe_id = intval($_GET['employe_id'] ?? 0);
if (!$employe_id) {
    echo json_encode(null);
    exit;
}

$db = Database::getInstance();

// Dernière sortie de l'employé
$db->prepare("SELECT s.id, s.date, s.notes
              FROM sorties s
              WHERE s.employe_id = ?
              ORDER BY s.date DESC, s.id DESC
              LIMIT 1");
$db->bind(1, $employe_id);
$sortie = $db->fetch();

if (!$sortie) {
    echo json_encode(null);
    exit;
}

// Lignes de cette sortie
$db->prepare("SELECT code_article, designation, qte_sortie
              FROM ligne_sorties
              WHERE sortie_id = ?
              ORDER BY id");
$db->bind(1, $sortie['id']);
$lignes = $db->fetchAll();

$sortie['lignes'] = $lignes;
echo json_encode($sortie);
