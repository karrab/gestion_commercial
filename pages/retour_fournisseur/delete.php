<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requirePermission('retour_fournisseur', 'delete');

$db = Database::getInstance();
$retourFournisseur = new RetourFournisseur();
$id = $_GET['id'] ?? 0;

// Vérifier existence
$retour = $retourFournisseur->getById($id);

if (!$retour) {
    $_SESSION['error'] = 'Retour fournisseur introuvable.';
    header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
    exit;
}

try {
    // Récupérer les détails du retour
    $fournisseurId = $retour['fournisseur_id'];
    $dateRetour = $retour['date'];
    $userId = $auth->getUserId();
    
    // Récupérer les articles associés au retour avant suppression
    $sql = "SELECT lrf.article_id, lrf.qte, a.code_article, a.designation, 
                   a.qte_retour_frs as stock_avant, 
                   a.stock_initial, a.stock_min, a.stock_max
            FROM ligne_retour_fournisseur lrf
            JOIN articles a ON lrf.article_id = a.id
            WHERE lrf.retour_fournisseur_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $lignesRetour = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Commencer une transaction
    $db->beginTransaction();
    
    // 1. Mettre à jour les quantités de retour fournisseur pour chaque article ET enregistrer dans l'historique
    foreach ($lignesRetour as $ligne) {
        $articleId = $ligne['article_id'];
        $quantite = $ligne['qte'];
        $codeArticle = $ligne['code_article'];
        $designation = $ligne['designation'];
        $stockAvant = $ligne['stock_avant'];
        $stockInitial = $ligne['stock_initial'];
        $stockMin = $ligne['stock_min'];
        $stockMax = $ligne['stock_max'];
        
        // Calculer la nouvelle quantité de retour fournisseur
        $nouvelleQteRetourFrs = max(0, $stockAvant - $quantite);
        $stockApres = $nouvelleQteRetourFrs;
        
        // Calculer la quantité disponible avant et après
        // Récupérer les autres quantités pour calculer la quantité disponible
        $sqlArticle = "SELECT stock_initial, qte_entree, qte_sortie, qte_retour, qte_disponible 
                      FROM articles WHERE id = ?";
        $stmtArticle = $db->prepare($sqlArticle);
        $stmtArticle->execute([$articleId]);
        $articleDetails = $stmtArticle->fetch(PDO::FETCH_ASSOC);
        
        if ($articleDetails) {
            // Calculer le stock avant suppression
            $stockDispoAvant = $articleDetails['qte_disponible'];
            
            // Calculer le stock après suppression
            $stockDispoApres = $articleDetails['stock_initial'] + 
                              $articleDetails['qte_entree'] - 
                              $articleDetails['qte_sortie'] + 
                              $articleDetails['qte_retour'] - 
                              $nouvelleQteRetourFrs;
            
            // 1a. Enregistrer dans l'historique AVANT de mettre à jour l'article
            $sqlHistorique = "INSERT INTO historique_article 
                (code_article, designation, operation, qte_retour_fournisseur, qte, 
                 stock_avant_operation, stock_apres_operation, stock_initial, stock_min, stock_max,
                 article_id, retour_fournisseur_id, user_id, date_operation, commentaire, created_at)
                VALUES (?, ?, 'retour_fournisseur', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $commentaire = "Suppression retour fournisseur N°" . $id . ". Augmentation du stock de " . $quantite . " unité(s).";
            
            $stmtHistorique = $db->prepare($sqlHistorique);
            $stmtHistorique->execute([
                $codeArticle,
                $designation,
                $quantite, // CORRIGÉ: qte_retour_fournisseur = $quantite (positive)
                $quantite, // CORRIGÉ: qte = $quantite (positive)
                $stockDispoAvant,
                $stockDispoApres,
                $stockInitial,
                $stockMin,
                $stockMax,
                $articleId,
                $id,
                $userId,
                $dateRetour . ' ' . date('H:i:s'),
                $commentaire
            ]);
            
            // 1b. Mettre à jour l'article avec la nouvelle quantité de retour fournisseur
            $sqlUpdate = "UPDATE articles 
                         SET qte_retour_frs = ?, 
                             qte_disponible = stock_initial + qte_entree - qte_sortie + qte_retour - ?,
                             updated_at = NOW()
                         WHERE id = ?";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->execute([$nouvelleQteRetourFrs, $nouvelleQteRetourFrs, $articleId]);
        }
    }
    
    // 2. Supprimer les lignes du retour
    $sqlDeleteLignes = "DELETE FROM ligne_retour_fournisseur WHERE retour_fournisseur_id = ?";
    $stmtDeleteLignes = $db->prepare($sqlDeleteLignes);
    $stmtDeleteLignes->execute([$id]);
    
    // 3. Supprimer le retour fournisseur
    $sqlDeleteRetour = "DELETE FROM retour_fournisseur WHERE id = ?";
    $stmtDeleteRetour = $db->prepare($sqlDeleteRetour);
    $stmtDeleteRetour->execute([$id]);
    
    // Valider la transaction
    $db->commit();
    
    $auth->logTrace($userId, 'retour_fournisseur', 'delete', 'retour_fournisseur', $id, "Suppression retour fournisseur #" . $id . " avec mise à jour des articles");
    
    $_SESSION['success'] = 'Retour fournisseur supprimé avec succès. Les quantités ont été mises à jour.';
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/retour_fournisseur/index.php');
exit;