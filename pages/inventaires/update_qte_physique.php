<?php
/**
 * Endpoint Ajax pour mise à jour de la quantité physique d'une ligne d'inventaire
 * Utilisé en temps réel lors de la saisie sur la page view.php
 */

// Inclure les dépendances
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';

// Démarrer le buffering de sortie pour éviter les sorties prématurées
ob_start();

// Définir le type de contenu JSON
header('Content-Type: application/json; charset=utf-8');

// Log pour debugging
$log_file = __DIR__ . '/../../logs/qte_physique_debug.log';
$log_entry = "[" . date('Y-m-d H:i:s') . "] POST Data: " . json_encode($_POST) . "\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND);

// ============================================
// 1. VALIDATION DE LA MÉTHODE HTTP
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée. Utilisez POST.'
    ]);
    exit;
}

// ============================================
// 2. VÉRIFICATION AUTHENTIFICATION
// ============================================
$auth = new Auth();

if (!$auth->isLoggedIn()) {
    $log_entry = "[" . date('Y-m-d H:i:s') . "] ERREUR: Utilisateur non connecté\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    ob_end_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Non authentifié. Veuillez vous reconnecter.'
    ]);
    exit;
}

// Vérifier les permissions
if (!$auth->hasPermission('inventaires', 'update')) {
    $log_entry = "[" . date('Y-m-d H:i:s') . "] ERREUR: Permission refusée pour user_id=" . $auth->getUserId() . "\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    ob_end_clean();
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Permission refusée. Vous n\'avez pas les droits nécessaires.'
    ]);
    exit;
}

// ============================================
// 3. RÉCUPÉRATION ET VALIDATION DES DONNÉES
// ============================================
$ligne_id = isset($_POST['ligne_id']) ? intval($_POST['ligne_id']) : 0;
$qte_physique = isset($_POST['qte_physique']) ? floatval($_POST['qte_physique']) : 0;

$log_entry = "[" . date('Y-m-d H:i:s') . "] Traitement: ligne_id=$ligne_id, qte_physique=$qte_physique\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND);

// Validation de l'ID
if ($ligne_id <= 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID de ligne invalide.'
    ]);
    exit;
}

// Validation de la quantité
if ($qte_physique < 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'La quantité doit être supérieure ou égale à 0.'
    ]);
    exit;
}

// ============================================
// 4. TRAITEMENT DE LA MISE À JOUR
// ============================================
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Vérifier que la ligne existe et que l'inventaire est en cours et non réinitialisé
    $sql_check = "SELECT li.id, li.qte_theorique, i.etat, i.reference, i.reinitialise
                  FROM ligne_inventaires li
                  INNER JOIN inventaires i ON li.inventaire_id = i.id
                  WHERE li.id = :ligne_id";

    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindValue(':ligne_id', $ligne_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $ligne = $stmt_check->fetch(PDO::FETCH_ASSOC);

    // Ligne introuvable
    if (!$ligne) {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] ERREUR: Ligne introuvable (ID: $ligne_id)\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);

        ob_end_clean();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Ligne d\'inventaire introuvable.'
        ]);
        exit;
    }

    // Vérifier que l'inventaire est en cours
    if ($ligne['etat'] !== 'en_cours') {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] ERREUR: Inventaire non modifiable (état: {$ligne['etat']})\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);

        ob_end_clean();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Cet inventaire n\'est pas modifiable (état: ' . $ligne['etat'] . ')'
        ]);
        exit;
    }

    // Vérifier que l'inventaire n'est pas réinitialisé
    if ($ligne['reinitialise'] == 1) {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] ERREUR: Inventaire réinitialisé - modifications bloquées\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);

        ob_end_clean();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Cet inventaire a été réinitialisé. Les modifications ne sont plus autorisées.'
        ]);
        exit;
    }

    // Calculer l'écart
    $qte_theorique = floatval($ligne['qte_theorique']);
    $ecart = $qte_physique - $qte_theorique;

    $log_entry = "[" . date('Y-m-d H:i:s') . "] Calcul: qte_theorique=$qte_theorique, qte_physique=$qte_physique, ecart=$ecart\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Mettre à jour la ligne d'inventaire
    $sql_update = "UPDATE ligne_inventaires
                   SET qte_physique = :qte_physique,
                       ecart = :ecart
                   WHERE id = :ligne_id";

    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindValue(':qte_physique', $qte_physique, PDO::PARAM_STR);
    $stmt_update->bindValue(':ecart', $ecart, PDO::PARAM_STR);
    $stmt_update->bindValue(':ligne_id', $ligne_id, PDO::PARAM_INT);

    $log_entry = "[" . date('Y-m-d H:i:s') . "] Exécution UPDATE SQL...\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Exécuter la requête
    if ($stmt_update->execute()) {
        $rows_affected = $stmt_update->rowCount();

        $log_entry = "[" . date('Y-m-d H:i:s') . "] SUCCÈS: $rows_affected ligne(s) mise(s) à jour\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);

        // Enregistrer la trace
        $auth_instance = new Auth();
        if (method_exists($auth_instance, 'logTrace')) {
            $auth_instance->logTrace(
                $auth->getUserId(),
                'inventaires',
                'update_qte',
                'ligne_inventaires',
                $ligne_id,
                "Mise à jour qte_physique: $qte_physique (écart: $ecart)"
            );
        }

        // Réponse de succès
        ob_end_clean();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Quantité mise à jour avec succès',
            'data' => [
                'ligne_id' => $ligne_id,
                'qte_physique' => $qte_physique,
                'qte_theorique' => $qte_theorique,
                'ecart' => $ecart,
                'rows_affected' => $rows_affected
            ],
            // Rétro-compatibilité avec l'ancien format
            'qte_physique' => $qte_physique,
            'qte_theorique' => $qte_theorique,
            'ecart' => $ecart,
            'rows_affected' => $rows_affected
        ]);
    } else {
        // Échec de l'exécution
        $errorInfo = $stmt_update->errorInfo();
        $log_entry = "[" . date('Y-m-d H:i:s') . "] ERREUR SQL: " . json_encode($errorInfo) . "\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);

        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour de la base de données',
            'debug' => [
                'sql_state' => $errorInfo[0] ?? '',
                'driver_code' => $errorInfo[1] ?? '',
                'message' => $errorInfo[2] ?? 'Erreur inconnue'
            ]
        ]);
    }

} catch (PDOException $e) {
    // Erreur PDO
    $log_entry = "[" . date('Y-m-d H:i:s') . "] EXCEPTION PDO: " . $e->getMessage() . "\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données',
        'debug' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);

} catch (Exception $e) {
    // Autre erreur
    $log_entry = "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n";
    $log_entry .= "Trace: " . $e->getTraceAsString() . "\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND);

    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}