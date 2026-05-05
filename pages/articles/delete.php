<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM articles WHERE id = :id");
$db->bind(':id', $id);
$article = $db->fetch();

if (!$article) {
    $_SESSION['error'] = 'Article introuvable.';
    header('Location: ' . BASE_URL . '/pages/articles/index.php');
    exit;
}

// Vérifier si l'article est utilisé dans différentes tables (sans employes et bureaux)
$tables_a_verifier = [
    'ligne_entrees' => "SELECT COUNT(*) as count FROM ligne_entrees WHERE article_id = :id",
    'ligne_sorties' => "SELECT COUNT(*) as count FROM ligne_sorties WHERE article_id = :id",
    'ligne_retours' => "SELECT COUNT(*) as count FROM ligne_retours WHERE article_id = :id",
    'ligne_retour_fournisseur' => "SELECT COUNT(*) as count FROM ligne_retour_fournisseur WHERE article_id = :id",
    'ligne_inventaires' => "SELECT COUNT(*) as count FROM ligne_inventaires WHERE article_id = :id",
    'historique_article' => "SELECT COUNT(*) as count FROM historique_article WHERE article_id = :id"
];

$tables_utilisees = [];
foreach ($tables_a_verifier as $table => $sql) {
    $db->prepare($sql);
    $db->bind(':id', $id);
    $result = $db->fetch();
    if ($result && $result['count'] > 0) {
        $tables_utilisees[] = $table;
    }
}

if (!empty($tables_utilisees)) {
    $message_tables = implode(', ', $tables_utilisees);
    $_SESSION['error'] = 'Suppression impossible!! Article est déjà utilisé dans: ' . $message_tables . '.';
    header('Location: ' . BASE_URL . '/pages/articles/index.php');
    exit;
}

try {
    // Supprimer l'article
    $db->prepare("DELETE FROM articles WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), 'articles', 'delete', 'articles', $id, "Suppression: " . $article['nom']);

        $_SESSION['success'] = 'Article supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/articles/index.php');
exit;