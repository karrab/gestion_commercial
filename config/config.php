<?php
/**
 * Fichier de configuration principal
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'stock_materiel');
define('DB_USER', 'root');
define('DB_PASS', 'sim!admin,');
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('APP_NAME', 'Gestion Stock Matériel');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/stock_manager');
define('SITE_URL', BASE_URL);

// Chemins
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_ENTREES_PATH', UPLOAD_PATH . '/uploadse');
define('UPLOAD_SORTIES_PATH', UPLOAD_PATH . '/uploadss');
define('UPLOAD_RETOURS_PATH', UPLOAD_PATH . '/uploadsr');
define('UPLOAD_INVENTAIRES_PATH', UPLOAD_PATH . '/uploadsinv');
define('IMAGES_PATH', ROOT_PATH . '/assets/images');

// URLs uploads
define('UPLOAD_URL', BASE_URL . '/uploads');
define('UPLOAD_ENTREES_URL', UPLOAD_URL . '/uploadse');
define('UPLOAD_SORTIES_URL', UPLOAD_URL . '/uploadss');
define('UPLOAD_RETOURS_URL', UPLOAD_URL . '/uploadsr');
define('UPLOAD_INVENTAIRES_URL', UPLOAD_URL . '/uploadsinv');
define('IMAGES_URL', BASE_URL . '/assets/images');

// Configuration de session
define('SESSION_LIFETIME', 3600); // 1 heure
define('SESSION_NAME', 'stock_materiel_session');

// Configuration de pagination
define('DEFAULT_PER_PAGE', 10);
define('PER_PAGE_OPTIONS', [10, 50, 100]);

// Configuration des uploads
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);

// Configuration timezone
date_default_timezone_set('Europe/Paris');

// Configuration PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration OPcache (vérifier dans php.ini aussi)
if (function_exists('opcache_get_status')) {
    $opcache_status = opcache_get_status();
    if (!$opcache_status['opcache_enabled']) {
        // OPcache n'est pas activé
        // Activer dans php.ini:
        // opcache.enable=1
        // opcache.memory_consumption=128
        // opcache.interned_strings_buffer=8
        // opcache.max_accelerated_files=10000
        // opcache.revalidate_freq=2
    }
}

// Configuration APCu (vérifier dans php.ini aussi)
if (function_exists('apcu_enabled') && apcu_enabled()) {
    // APCu est activé
    // Configuration dans php.ini:
    // apc.enabled=1
    // apc.shm_size=32M
    // apc.ttl=7200
}

// Création des dossiers d'upload s'ils n'existent pas
$upload_dirs = [
    UPLOAD_PATH,
    UPLOAD_ENTREES_PATH,
    UPLOAD_SORTIES_PATH,
    UPLOAD_RETOURS_PATH,
    UPLOAD_INVENTAIRES_PATH,
    IMAGES_PATH
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Fonction d'autoload personnalisée
spl_autoload_register(function ($class) {
    $class_file = ROOT_PATH . '/classes/' . $class . '.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}