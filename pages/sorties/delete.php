<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence de la sortie
$db->prepare("SELECT * FROM sorties WHERE id = :id");
$db->bind(':id', $id);
$sortie = $db->fetch();

if (!$sortie) {
    $_SESSION['error'] = 'Sortie introuvable.';
    header('Location: ' . BASE_URL . '/pages/sorties/index.php');
    exit;
}

try {
    // Récupérer les informations de l'employé pour l'historique AVANT toute suppression
    $db->prepare("SELECT e.nom, e.prenom FROM employes e WHERE e.id = :employe_id");
    $db->bind(':employe_id', $sortie['employe_id']);
    $employe = $db->fetch();
    $employe_nom = $employe ? $employe['prenom'] . ' ' . $employe['nom'] : 'Inconnu';

    // Récupérer toutes les lignes de sortie associées AVANT toute suppression
    $db->prepare("SELECT ls.*, a.code_article, a.designation, a.qte_disponible, 
                  a.stock_initial, a.stock_min, a.stock_max
                  FROM ligne_sorties ls 
                  JOIN articles a ON ls.article_id = a.id 
                  WHERE ls.sortie_id = :id");
    $db->bind(':id', $id);
    $lignes_sorties = $db->fetchAll();

    // Traiter chaque ligne de sortie pour mettre à jour les stocks et historiques
    foreach ($lignes_sorties as $ligne) {
        $article_id = $ligne['article_id'];
        $code_article = $ligne['code_article'];
        $designation = $ligne['designation'];
        $qte_sortie = intval($ligne['qte_sortie']); // INT pour correspondre à la structure
        $stock_avant = intval($ligne['qte_disponible']); // INT
        $stock_initial = intval($ligne['stock_initial']);
        $stock_min = intval($ligne['stock_min']);
        $stock_max = intval($ligne['stock_max']);
        
        // Calculer le nouveau stock disponible (retourner la quantité supprimée)
        $nouveau_stock = $stock_avant + $qte_sortie;
        
        // Mettre à jour la quantité disponible dans la table articles
        $db->prepare("UPDATE articles SET 
                      qte_disponible = :qte_disponible,
                      qte_sortie = qte_sortie - :qte_sortie,
                      updated_at = NOW()
                      WHERE id = :id");
        $db->bind(':qte_disponible', $nouveau_stock);
        $db->bind(':qte_sortie', $qte_sortie);
        $db->bind(':id', $article_id);
        $db->execute();
        
        // Enregistrer dans l'historique des articles avec sortie_id
        $db->prepare("INSERT INTO historique_article 
                      (code_article, designation, operation, 
                       qte_entree, qte_sortie, qte_retour_employe, qte_retour_fournisseur,
                       qte, 
                       stock_avant_operation, stock_apres_operation, 
                       stock_initial, stock_min, stock_max,
                       article_id, 
                       entree_id, sortie_id, retour_id, 
                       user_id, date_operation, commentaire, created_at) 
                      VALUES 
                      (:code_article, :designation, 'sortie', 
                       NULL, :qte_sortie, NULL, NULL,
                       :qte,
                       :stock_avant, :stock_apres,
                       :stock_initial, :stock_min, :stock_max,
                       :article_id,
                       NULL, :sortie_id, NULL,
                       :user_id, NOW(), :commentaire, NOW())");
        
        // La quantité retournée est positive car on ajoute au stock
        $qte_retour = $qte_sortie;
        
        $db->bind(':code_article', $code_article);
        $db->bind(':designation', $designation);
        $db->bind(':qte_sortie', $qte_sortie);
        $db->bind(':qte', $qte_retour); // Qte positive car retour au stock
        $db->bind(':stock_avant', $stock_avant);
        $db->bind(':stock_apres', $nouveau_stock);
        $db->bind(':stock_initial', $stock_initial);
        $db->bind(':stock_min', $stock_min);
        $db->bind(':stock_max', $stock_max);
        $db->bind(':article_id', $article_id);
        $db->bind(':sortie_id', $id); // ICI: L'ID de la sortie
        $db->bind(':user_id', $auth->getUserId());
        $db->bind(':commentaire', "Suppression sortie employé: {$employe_nom} suppression de {$qte_sortie} unité(s). ID_sortie: {$id}");
        
        // Exécuter l'insertion
        $result = $db->execute();
        
        // Vérifier si l'insertion a réussi
        if (!$result) {
            error_log("Erreur insertion historique pour article $code_article");
            // Vous pouvez aussi logger l'erreur SQL si disponible
            // error_log("Erreur SQL: " . $db->errorInfo());
        }
    }
    
    // Maintenant supprimer les lignes de sortie associées
    $db->prepare("DELETE FROM ligne_sorties WHERE sortie_id = :id");
    $db->bind(':id', $id);
    $db->execute();
    
    // Ensuite supprimer la sortie elle-même
    $db->prepare("DELETE FROM sorties WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();

    // Log de l'action
    $auth->logTrace($auth->getUserId(), 'sorties', 'delete', 'sorties', $id, 
                    "Suppression sortie #{$id} avec " . count($lignes_sorties) . " articles associés");

    $_SESSION['success'] = 'Sortie supprimée avec succès. Les stocks ont été mis à jour.';

} catch (Exception $e) {
    error_log("Erreur suppression sortie: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/sorties/index.php');
exit;