<?php
/**
 * FICHIER DE DIAGNOSTIC - À placer à la racine de votre projet
 * Fichier: diagnostic.php
 * 
 * Accès: http://localhost/stock_materiel/diagnostic.php
 */

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnostic Système</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ccc; }
        .test h3 { margin: 0 0 10px 0; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; background: #fff5f5; }
        .warning { border-left-color: #ffc107; background: #fffef5; }
        .info { border-left-color: #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .icon { font-size: 20px; margin-right: 10px; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Système - Stock Matériel</h1>
    <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
";

// Variable pour compter les erreurs
$errors = 0;
$warnings = 0;

// ===== TEST 1: PHP Version =====
echo "<div class='test success'>";
echo "<h3><span class='icon'>✅</span>Test 1: Version PHP</h3>";
echo "<p>Version PHP: <strong>" . phpversion() . "</strong></p>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<p class='error'>⚠️ Version PHP trop ancienne. Recommandé: >= 7.4</p>";
    $warnings++;
}
echo "</div>";

// ===== TEST 2: Extensions PHP =====
echo "<div class='test " . (extension_loaded('pdo') && extension_loaded('pdo_mysql') ? 'success' : 'error') . "'>";
echo "<h3><span class='icon'>" . (extension_loaded('pdo') && extension_loaded('pdo_mysql') ? '✅' : '❌') . "</span>Test 2: Extensions PHP</h3>";

$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $icon = $loaded ? '✅' : '❌';
    echo "<p>$icon $ext: " . ($loaded ? 'Chargée' : '<strong>NON chargée</strong>') . "</p>";
    if (!$loaded && in_array($ext, ['pdo', 'pdo_mysql', 'mysqli'])) {
        $errors++;
    }
}
echo "</div>";

// ===== TEST 3: Fichier config.php =====
$configPaths = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/includes/config.php',
    __DIR__ . '/../config/config.php'
];

$configFound = false;
$configPath = '';

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        $configFound = true;
        $configPath = $path;
        break;
    }
}

echo "<div class='test " . ($configFound ? 'success' : 'error') . "'>";
echo "<h3><span class='icon'>" . ($configFound ? '✅' : '❌') . "</span>Test 3: Fichier config.php</h3>";

if ($configFound) {
    echo "<p>✅ Fichier trouvé: <code>$configPath</code></p>";
    
    // Essayer de charger le config
    try {
        require_once $configPath;
        echo "<p>✅ Fichier chargé avec succès</p>";
        
        // Vérifier les constantes
        $constants = ['BASE_URL', 'DB_HOST', 'DB_NAME', 'DB_USER'];
        foreach ($constants as $const) {
            if (defined($const)) {
                $value = constant($const);
                if ($const === 'DB_PASS') {
                    $value = '***hidden***';
                }
                echo "<p>✅ $const = <code>$value</code></p>";
            } else {
                echo "<p>❌ $const: <strong>NON définie</strong></p>";
                $errors++;
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ Erreur lors du chargement: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
} else {
    echo "<p>❌ Fichier config.php NON trouvé</p>";
    echo "<p>Chemins testés:</p><ul>";
    foreach ($configPaths as $path) {
        echo "<li><code>$path</code></li>";
    }
    echo "</ul>";
    $errors++;
}
echo "</div>";

// ===== TEST 4: Connexion base de données =====
echo "<div class='test info'>";
echo "<h3><span class='icon'>🔌</span>Test 4: Connexion Base de Données</h3>";

if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '');
        echo "<p>✅ Connexion réussie à la base de données</p>";
        echo "<p>Base: <strong>" . DB_NAME . "</strong></p>";
        
        // Test: Compter les tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>✅ Nombre de tables: <strong>" . count($tables) . "</strong></p>";
        
        // Vérifier les tables importantes
        $requiredTables = ['fournisseurs', 'entrees', 'ligne_entrees', 'articles', 'users'];
        $missingTables = [];
        foreach ($requiredTables as $table) {
            if (!in_array($table, $tables)) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            echo "<p>✅ Toutes les tables requises sont présentes</p>";
        } else {
            echo "<p>⚠️ Tables manquantes: <strong>" . implode(', ', $missingTables) . "</strong></p>";
            $warnings++;
        }
        
    } catch (PDOException $e) {
        echo "<p>❌ Erreur de connexion: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
} else {
    echo "<p>⚠️ Impossible de tester (constantes DB non définies)</p>";
    $warnings++;
}
echo "</div>";

// ===== TEST 5: Classe Database =====
echo "<div class='test info'>";
echo "<h3><span class='icon'>📦</span>Test 5: Classe Database</h3>";

if (class_exists('Database')) {
    echo "<p>✅ Classe Database existe</p>";
    
    try {
        $db = Database::getInstance();
        echo "<p>✅ getInstance() fonctionne</p>";
        
        // Test de requête simple
        $db->prepare("SELECT 1 as test");
        $result = $db->fetch();
        
        if ($result && $result['test'] == 1) {
            echo "<p>✅ Requête de test réussie</p>";
        } else {
            echo "<p>⚠️ Requête de test a échoué</p>";
            $warnings++;
        }
    } catch (Exception $e) {
        echo "<p>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
} else {
    echo "<p>❌ Classe Database NON trouvée</p>";
    echo "<p>Vérifiez que le fichier de la classe Database est bien inclus dans config.php</p>";
    $errors++;
}
echo "</div>";

// ===== TEST 6: Données de test =====
if (defined('DB_HOST') && isset($pdo)) {
    echo "<div class='test info'>";
    echo "<h3><span class='icon'>📊</span>Test 6: Données dans la base</h3>";
    
    try {
        // Compter les fournisseurs
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM fournisseurs");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Fournisseurs: <strong>" . $count['total'] . "</strong></p>";
        
        // Compter les entrées
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM entrees");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Entrées: <strong>" . $count['total'] . "</strong></p>";
        
        // Compter les articles
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM articles WHERE qte_disponible > 0");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Articles avec stock: <strong>" . $count['total'] . "</strong></p>";
        
        if ($count['total'] == 0) {
            echo "<p>⚠️ Aucun article avec stock disponible</p>";
            $warnings++;
        }
        
    } catch (PDOException $e) {
        echo "<p>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
    echo "</div>";
}

// ===== TEST 7: API last_entree_articles.php =====
$apiPath = __DIR__ . '/api/last_entree_articles.php';
echo "<div class='test " . (file_exists($apiPath) ? 'success' : 'error') . "'>";
echo "<h3><span class='icon'>" . (file_exists($apiPath) ? '✅' : '❌') . "</span>Test 7: API last_entree_articles.php</h3>";

if (file_exists($apiPath)) {
    echo "<p>✅ Fichier API trouvé: <code>$apiPath</code></p>";
    echo "<p>Tester l'API: <a href='api/last_entree_articles.php?fournisseur_id=1' target='_blank'>Cliquez ici</a></p>";
} else {
    echo "<p>❌ Fichier API NON trouvé</p>";
    echo "<p>Chemin testé: <code>$apiPath</code></p>";
    $errors++;
}
echo "</div>";

// ===== TEST 8: Fichiers includes =====
echo "<div class='test info'>";
echo "<h3><span class='icon'>📁</span>Test 8: Fichiers includes</h3>";

$includesFiles = [
    'includes/header.php',
    'includes/footer.php',
    'includes/navbar.php',
];

foreach ($includesFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $exists = file_exists($fullPath);
    $icon = $exists ? '✅' : '❌';
    echo "<p>$icon $file: " . ($exists ? 'Trouvé' : '<strong>NON trouvé</strong>') . "</p>";
    if (!$exists) {
        $warnings++;
    }
}
echo "</div>";

// ===== TEST 9: Permissions =====
echo "<div class='test info'>";
echo "<h3><span class='icon'>🔐</span>Test 9: Permissions fichiers</h3>";

$directories = [
    'uploads',
    'uploads/retour_fournisseur',
];

foreach ($directories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (file_exists($fullPath)) {
        $writable = is_writable($fullPath);
        $icon = $writable ? '✅' : '⚠️';
        echo "<p>$icon $dir: " . ($writable ? 'Accessible en écriture' : 'PAS accessible en écriture') . "</p>";
        if (!$writable) {
            $warnings++;
        }
    } else {
        echo "<p>ℹ️ $dir: N'existe pas encore (sera créé automatiquement)</p>";
    }
}
echo "</div>";

// ===== TEST 10: Erreurs PHP récentes =====
echo "<div class='test info'>";
echo "<h3><span class='icon'>📝</span>Test 10: Logs d'erreurs PHP</h3>";

$logFiles = [
    ini_get('error_log'),
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log',
    __DIR__ . '/error.log'
];

$logFound = false;
foreach ($logFiles as $logFile) {
    if ($logFile && file_exists($logFile) && is_readable($logFile)) {
        echo "<p>✅ Fichier log trouvé: <code>$logFile</code></p>";
        
        // Lire les 20 dernières lignes
        $lines = file($logFile);
        $recentLines = array_slice($lines, -20);
        
        if (!empty($recentLines)) {
            echo "<p><strong>Dernières erreurs:</strong></p>";
            echo "<pre style='max-height: 300px; overflow-y: auto;'>";
            foreach ($recentLines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        }
        
        $logFound = true;
        break;
    }
}

if (!$logFound) {
    echo "<p>ℹ️ Aucun fichier de log accessible</p>";
    echo "<p>Vérifiez la configuration de error_log dans php.ini</p>";
}
echo "</div>";

// ===== RÉSUMÉ =====
echo "<div class='test " . ($errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'success')) . "'>";
echo "<h2><span class='icon'>" . ($errors > 0 ? '❌' : ($warnings > 0 ? '⚠️' : '✅')) . "</span>RÉSUMÉ</h2>";

if ($errors == 0 && $warnings == 0) {
    echo "<p><strong>✅ Tous les tests sont OK !</strong></p>";
    echo "<p>Votre système semble configuré correctement.</p>";
} else {
    echo "<p><strong>Erreurs critiques: $errors</strong></p>";
    echo "<p><strong>Avertissements: $warnings</strong></p>";
    
    if ($errors > 0) {
        echo "<hr>";
        echo "<h3>🔧 Actions à entreprendre:</h3>";
        echo "<ol>";
        echo "<li>Corrigez les erreurs critiques (❌) en premier</li>";
        echo "<li>Vérifiez les logs PHP pour plus de détails</li>";
        echo "<li>Assurez-vous que config.php existe et est correct</li>";
        echo "<li>Vérifiez que la base de données est accessible</li>";
        echo "</ol>";
    }
}

echo "</div>";

echo "
<div style='margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px;'>
    <h3>📞 Prochaines étapes</h3>
    <p><strong>Copiez TOUTES les informations ci-dessus</strong> et envoyez-les moi.</p>
    <p>Je pourrai alors identifier exactement où se trouve le problème.</p>
</div>

</body>
</html>";
?>