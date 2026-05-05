<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('retours', 'delete');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM retours WHERE id = :id");
$db->bind(':id', $id);
$retour = $db->fetch();

if (!$retour) {
    $_SESSION['error'] = 'Retour introuvable.';
    header('Location: ' . BASE_URL . '/pages/retours/index.php');
    exit;
}

try {
    $db->beginTransaction();

    // Récupérer les lignes pour rembourser le stock
    $db->prepare("SELECT article_id, qte_retour FROM ligne_retours WHERE retour_id = :id");
    $db->bind(':id', $id);
    $lignes = $db->fetchAll();
    
    // Rembourser le stock (retirer les quantités retournées)
    foreach ($lignes as $ligne) {
        $sql = "UPDATE articles 
                SET qte_retour = qte_retour - :qte, qte_disponible = qte_disponible - :qte
                WHERE id = :article_id";
        
        $db->prepare($sql);
        $db->bind(':qte', $ligne['qte_retour']);
        $db->bind(':article_id', $ligne['article_id']);
        $db->execute();
    }

    // Supprimer les lignes
    $db->prepare("DELETE FROM ligne_retours WHERE retour_id = :id");
    $db->bind(':id', $id);
    $db->execute();
    
    // Supprimer le retour
    $db->prepare("DELETE FROM retours WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Supprimer le fichier s'il existe
    if (!empty($retour['fichier']) && file_exists(__DIR__ . '/../../../uploads/retours/' . $retour['fichier'])) {
        unlink(__DIR__ . '/../../../uploads/retours/' . $retour['fichier']);
    }

    $auth->logTrace($auth->getUserId(), 'retours', 'delete', 'retours', $id, "Suppression retour #$id");
    $db->commit();

    $_SESSION['success'] = 'Retour supprimé avec succès.';
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/retours/index.php');
exit;