<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('fournisseurs', 'delete');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence du fournisseur
$db->prepare("SELECT * FROM fournisseurs WHERE id = :id");
$db->bind(':id', $id);
$fournisseur = $db->fetch();

if (!$fournisseur) {
    $_SESSION['error'] = 'Fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/fournisseurs/index.php');
    exit;
}

// Vérifier si le fournisseur est utilisé dans les tables liées
$verifications = [
    
    'entrees' => "SELECT COUNT(*) as count FROM entrees WHERE fournisseur_id = :id",
    'retour_fournisseur' => "SELECT COUNT(*) as count FROM retour_fournisseur WHERE fournisseur_id = :id"
];

$utilisations = [];
$total_utilisations = 0;

foreach ($verifications as $table => $sql) {
    $db->prepare($sql);
    $db->bind(':id', $id);
    $count = $db->fetch()['count'];
    
    if ($count > 0) {
        $utilisations[$table] = $count;
        $total_utilisations += $count;
    }
}

// Si le fournisseur est utilisé, on empêche la suppression
if ($total_utilisations > 0) {
    $message = 'Impossible de supprimer ce fournisseur car il est utilisé dans :<br>';
    $message .= '<ul>';
    
    
    if (isset($utilisations['entrees'])) {
        $message .= '<li>' . $utilisations['entrees'] . ' entrée(s)</li>';
    }
    if (isset($utilisations['retour_fournisseur'])) {
        $message .= '<li>' . $utilisations['retour_fournisseur'] . ' retour(s) fournisseur</li>';
    }
    
    $message .= '</ul>';
    $message .= 'Vous devez d\'abord supprimer ou réaffecter ces éléments.';
    
    $_SESSION['error'] = $message;
    header('Location: ' . BASE_URL . '/pages/fournisseurs/view.php?id=' . $id);
    exit;
}

// Si on arrive ici, c'est que le fournisseur n'est utilisé nulle part
try {
    // Démarrer une transaction pour plus de sécurité
    $db->getConnection()->beginTransaction();
    
    // Supprimer le fournisseur
    $db->prepare("DELETE FROM fournisseurs WHERE id = :id");
    $db->bind(':id', $id);
    
    if ($db->execute()) {
        // Valider la transaction
        $db->getConnection()->commit();
        
        // Log de la trace
        $auth->logTrace(
            $auth->getUserId(), 
            'fournisseurs', 
            'delete', 
            'fournisseurs', 
            $id, 
            "Suppression: " . $fournisseur['nom_complet']
        );
        
        $_SESSION['success'] = 'Fournisseur supprimé avec succès.';
    } else {
        throw new Exception("Erreur lors de la suppression");
    }
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/fournisseurs/index.php');
exit;