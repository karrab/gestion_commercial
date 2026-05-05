<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('entrees', 'delete');

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM entrees WHERE id = ?");
$db->bind(1, $id);
$entree = $db->fetch();

if (!$entree) {
    $_SESSION['error'] = 'Entrée introuvable.';
    header('Location: ' . BASE_URL . '/pages/entrees/index.php');
    exit;
}

// Optionnel: Vérification supplémentaire - l'entrée est-elle récente?
// $dateEntree = new DateTime($entree['created_at']);
// $dateNow = new DateTime();
// $interval = $dateNow->diff($dateEntree);
// if ($interval->days > 30) {
//     $_SESSION['error'] = 'Impossible de supprimer une entrée de plus de 30 jours.';
//     header('Location: ' . BASE_URL . '/pages/entrees/index.php');
//     exit;
// }

try {
    $db->beginTransaction();
    
    $user_id = $auth->getUserId();

    // Récupérer les lignes d'entrée avec les informations détaillées des articles
    $db->prepare("SELECT le.*, a.code_article, a.designation, a.qte_disponible, a.stock_initial, a.stock_min, a.stock_max 
                  FROM ligne_entrees le 
                  JOIN articles a ON le.article_id = a.id 
                  WHERE le.entree_id = ?");
    $db->bind(1, $id);
    $lignes = $db->fetchAll();

    // Ajuster le stock des articles et enregistrer l'historique
    foreach ($lignes as $ligne) {
        $stock_avant = $ligne['qte_disponible'];
        $stock_initial = $ligne['stock_initial'];
        $stock_min = $ligne['stock_min'];
        $stock_max = $ligne['stock_max'];
        $qte_entree = floatval($ligne['qte_entree']);
        
        // Ajuster le stock des articles
        $sql = "UPDATE articles 
                SET qte_entree = qte_entree - ?,
                    qte_disponible = qte_disponible - ?
                WHERE id = ?";
        
        $db->prepare($sql);
        $db->bind(1, $qte_entree);
        $db->bind(2, $qte_entree);
        $db->bind(3, $ligne['article_id']);
        $db->execute();
        
        $stock_apres = $stock_avant - $qte_entree;
        
        // Enregistrer dans l'historique pour la suppression d'entrée
        $sql_hist = "INSERT INTO historique_article 
                    (code_article, designation, operation, qte, stock_avant_operation, stock_apres_operation, 
                     stock_initial, stock_min, stock_max, article_id, entree_id, sortie_id, retour_id, user_id, 
                     date_operation, commentaire, created_at)
                     VALUES (?, ?, 'entree', ?, ?, ?, 
                             ?, ?, ?, 
                             ?, ?, NULL, NULL, ?, NOW(), ?, NOW())";

        $db->prepare($sql_hist);
        $db->bind(1, $ligne['code_article']);
        $db->bind(2, $ligne['designation']);
        $db->bind(3, -$qte_entree); // Quantité négative pour suppression
        $db->bind(4, $stock_avant);
        $db->bind(5, $stock_apres);
        $db->bind(6, $stock_initial);
        $db->bind(7, $stock_min);
        $db->bind(8, $stock_max);
        $db->bind(9, $ligne['article_id']);
        $db->bind(10, $id); // Conserver l'ID de l'entrée pour référence
        $db->bind(11, $user_id);
        $db->bind(12, "Suppression entrée #$id - retrait de $qte_entree unité(s) de l'article " . $ligne['code_article']);
        $db->execute();
    }

    // Supprimer les lignes d'entrée
    $db->prepare("DELETE FROM ligne_entrees WHERE entree_id = ?");
    $db->bind(1, $id);
    $db->execute();

    // Supprimer l'entrée
    $db->prepare("DELETE FROM entrees WHERE id = ?");
    $db->bind(1, $id);
    $db->execute();

    // Supprimer le fichier joint si existant
    if (!empty($entree['fichier']) && file_exists(UPLOAD_ENTREES_PATH . '/' . $entree['fichier'])) {
        unlink(UPLOAD_ENTREES_PATH . '/' . $entree['fichier']);
    }

    $db->commit();

    $auth->logTrace($auth->getUserId(), 'entrees', 'delete', 'entrees', $id, "Suppression entrée #" . $id . " avec historique des articles");

    $_SESSION['success'] = 'Entrée supprimée avec succès. L\'historique des articles a été mis à jour.';
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/entrees/index.php');
exit;