<?php
/**
 * Ajout d'articles à un inventaire existant
 */

require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requirePermission('inventaires', 'update');

$db = Database::getInstance();
$inventaire_id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'active'; // 'all' ou 'active'

// Récupérer l'inventaire
$db->prepare("SELECT * FROM inventaires WHERE id = :id");
$db->bind(':id', $inventaire_id);
$inventaire = $db->fetch();

if (!$inventaire) {
    $_SESSION['error'] = 'Inventaire introuvable.';
    header('Location: ' . BASE_URL . '/pages/inventaires/index.php');
    exit;
}

if ($inventaire['etat'] !== 'en_cours') {
    $_SESSION['error'] = 'Seuls les inventaires "En cours" peuvent être modifiés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
    exit;
}

// Vérifier si l'inventaire a été réinitialisé
if ($inventaire['reinitialise'] == 1) {
    $_SESSION['error'] = 'Cet inventaire a été réinitialisé. Les articles ne peuvent plus être ajoutés.';
    header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
    exit;
}

try {
    $conn = $db->getConnection();
    $conn->beginTransaction();
    
    // Récupérer les articles selon le type
    if ($type === 'all') {
        $sql_articles = "SELECT a.id, a.code_article, a.designation, a.qte_disponible
                        FROM articles a
                        WHERE NOT EXISTS (
                            SELECT 1 FROM ligne_inventaires li 
                            WHERE li.inventaire_id = :inv_id AND li.article_id = a.id
                        )
                        ORDER BY a.code_article";
    } else {
        $sql_articles = "SELECT a.id, a.code_article, a.designation, a.qte_disponible
                        FROM articles a
                        WHERE a.actif = 1
                        AND NOT EXISTS (
                            SELECT 1 FROM ligne_inventaires li 
                            WHERE li.inventaire_id = :inv_id AND li.article_id = a.id
                        )
                        ORDER BY a.code_article";
    }
    
    $stmt_articles = $conn->prepare($sql_articles);
    $stmt_articles->execute([':inv_id' => $inventaire_id]);
    $articles = $stmt_articles->fetchAll(PDO::FETCH_ASSOC);
    
    // Insérer les articles
    $sql_insert = "INSERT INTO ligne_inventaires 
                   (inventaire_id, article_id, code_article, designation, qte_theorique, qte_physique, ecart)
                   VALUES (:inv_id, :art_id, :code, :design, :qte_theo, 0, :ecart)";
    
    $stmt_insert = $conn->prepare($sql_insert);
    $nb_ajoutes = 0;
    
    foreach ($articles as $article) {
        $ecart = -$article['qte_disponible'];
        
        $stmt_insert->execute([
            ':inv_id' => $inventaire_id,
            ':art_id' => $article['id'],
            ':code' => $article['code_article'],
            ':design' => $article['designation'],
            ':qte_theo' => $article['qte_disponible'],
            ':ecart' => $ecart
        ]);
        
        $nb_ajoutes++;
    }
    
    $conn->commit();
    
    $_SESSION['success'] = "$nb_ajoutes article(s) ajouté(s) à l'inventaire.";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    $_SESSION['error'] = 'Erreur lors de l\'ajout des articles : ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/inventaires/view.php?id=' . $inventaire_id);
exit;