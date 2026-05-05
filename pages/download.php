<?php
/**
 * Téléchargement sécurisé des fichiers joints
 * Usage: download.php?type=sorties&file=nom_du_fichier.pdf
 */
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    exit('Accès non autorisé.');
}

$type = $_GET['type'] ?? '';
$file = basename($_GET['file'] ?? ''); // basename() empêche la traversée de répertoire

$upload_paths = [
    'sorties'     => UPLOAD_SORTIES_PATH,
    'entrees'     => UPLOAD_ENTREES_PATH,
    'retours'     => UPLOAD_RETOURS_PATH,
    'inventaires' => UPLOAD_INVENTAIRES_PATH,
];

if (!array_key_exists($type, $upload_paths) || empty($file)) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

$path = $upload_paths[$type] . DIRECTORY_SEPARATOR . $file;

if (!file_exists($path) || !is_file($path)) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime_types = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$mime = $mime_types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $file . '"');
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;
