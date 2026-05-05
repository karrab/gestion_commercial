<?php
/**
 * Classe HistoriqueArticle
 * Gère l'historique des mouvements de stock (entrées, sorties, retours)
 */
class HistoriqueArticle {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Enregistre un mouvement dans l'historique
     *
     * @param array $data Données du mouvement
     * @return int|bool ID du mouvement créé ou false en cas d'erreur
     */
    public function enregistrerMouvement($data) {
        try {
            // Récupérer les informations de l'article
            $article = $this->getArticleInfo($data['article_id']);

            if (!$article) {
                throw new Exception("Article non trouvé");
            }

            $sql = "INSERT INTO historique_article (
                        code_article, designation, operation, qte,
                        stock_avant_operation, stock_apres_operation,
                        stock_initial, stock_min, stock_max,
                        article_id, entree_id, sortie_id, retour_id,
                        user_id, date_operation, commentaire
                    ) VALUES (
                        :code_article, :designation, :operation, :qte,
                        :stock_avant, :stock_apres,
                        :stock_initial, :stock_min, :stock_max,
                        :article_id, :entree_id, :sortie_id, :retour_id,
                        :user_id, :date_operation, :commentaire
                    )";

            $this->db->prepare($sql);

            $this->db->bind(':code_article', $article['code']);
            $this->db->bind(':designation', $article['designation']);
            $this->db->bind(':operation', $data['operation']);
            $this->db->bind(':qte', $data['qte']);
            $this->db->bind(':stock_avant', $data['stock_avant_operation']);
            $this->db->bind(':stock_apres', $data['stock_apres_operation']);
            $this->db->bind(':stock_initial', $article['stock_initial'] ?? 0);
            $this->db->bind(':stock_min', $article['stock_min'] ?? 0);
            $this->db->bind(':stock_max', $article['stock_max'] ?? 0);
            $this->db->bind(':article_id', $data['article_id']);
            $this->db->bind(':entree_id', $data['entree_id'] ?? null);
            $this->db->bind(':sortie_id', $data['sortie_id'] ?? null);
            $this->db->bind(':retour_id', $data['retour_id'] ?? null);
            $this->db->bind(':user_id', $data['user_id']);
            $this->db->bind(':date_operation', $data['date_operation']);
            $this->db->bind(':commentaire', $data['commentaire'] ?? null);

            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }

            return false;
        } catch (Exception $e) {
            error_log("Erreur enregistrement historique: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enregistre un mouvement d'entrée
     */
    public function enregistrerEntree($article_id, $qte_entree, $entree_id, $user_id, $date_operation, $commentaire = null) {
        // Récupérer le stock actuel avant l'opération
        $stock_avant = $this->getStockActuel($article_id);
        $stock_apres = $stock_avant + $qte_entree;

        return $this->enregistrerMouvement([
            'article_id' => $article_id,
            'operation' => 'entree',
            'qte' => $qte_entree,
            'stock_avant_operation' => $stock_avant,
            'stock_apres_operation' => $stock_apres,
            'entree_id' => $entree_id,
            'user_id' => $user_id,
            'date_operation' => $date_operation,
            'commentaire' => $commentaire
        ]);
    }

    /**
     * Enregistre un mouvement de sortie
     */
    public function enregistrerSortie($article_id, $qte_sortie, $sortie_id, $user_id, $date_operation, $commentaire = null) {
        // Récupérer le stock actuel avant l'opération
        $stock_avant = $this->getStockActuel($article_id);
        $stock_apres = $stock_avant - $qte_sortie;

        return $this->enregistrerMouvement([
            'article_id' => $article_id,
            'operation' => 'sortie',
            'qte' => $qte_sortie,
            'stock_avant_operation' => $stock_avant,
            'stock_apres_operation' => $stock_apres,
            'sortie_id' => $sortie_id,
            'user_id' => $user_id,
            'date_operation' => $date_operation,
            'commentaire' => $commentaire
        ]);
    }

    /**
     * Enregistre un mouvement de retour
     */
    public function enregistrerRetour($article_id, $qte_retour, $retour_id, $user_id, $date_operation, $commentaire = null) {
        // Récupérer le stock actuel avant l'opération
        $stock_avant = $this->getStockActuel($article_id);
        $stock_apres = $stock_avant + $qte_retour;

        return $this->enregistrerMouvement([
            'article_id' => $article_id,
            'operation' => 'retour',
            'qte' => $qte_retour,
            'stock_avant_operation' => $stock_avant,
            'stock_apres_operation' => $stock_apres,
            'retour_id' => $retour_id,
            'user_id' => $user_id,
            'date_operation' => $date_operation,
            'commentaire' => $commentaire
        ]);
    }

    /**
     * Récupère les informations d'un article
     */
    private function getArticleInfo($article_id) {
        $sql = "SELECT code, designation, stock_initial, stock_min, stock_max
                FROM articles
                WHERE id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $article_id);
        $this->db->execute();

        return $this->db->fetch();
    }

    /**
     * Récupère le stock actuel d'un article
     */
    private function getStockActuel($article_id) {
        $sql = "SELECT qte_physiques FROM articles WHERE id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $article_id);
        $this->db->execute();

        $result = $this->db->fetch();
        return $result ? (float)$result['qte_physiques'] : 0;
    }

    /**
     * Récupère tous les mouvements avec pagination
     */
    public function getAll($page = 1, $perPage = DEFAULT_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT h.*, u.nom as user_nom, u.prenom as user_prenom
                FROM historique_article h
                LEFT JOIN users u ON h.user_id = u.id
                WHERE 1=1";

        $params = [];

        // Filtres
        if (!empty($filters['code_article'])) {
            $sql .= " AND h.code_article LIKE :code_article";
            $params[':code_article'] = '%' . $filters['code_article'] . '%';
        }

        if (!empty($filters['designation'])) {
            $sql .= " AND h.designation LIKE :designation";
            $params[':designation'] = '%' . $filters['designation'] . '%';
        }

        if (!empty($filters['operation'])) {
            $sql .= " AND h.operation = :operation";
            $params[':operation'] = $filters['operation'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(h.date_operation) >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(h.date_operation) <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['article_id'])) {
            $sql .= " AND h.article_id = :article_id";
            $params[':article_id'] = $filters['article_id'];
        }

        $sql .= " ORDER BY h.date_operation DESC, h.id DESC LIMIT :limit OFFSET :offset";

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère tous les mouvements sans pagination (pour DataTables)
     */
    public function getAllWithoutPagination($filters = []) {
        $sql = "SELECT h.*, u.nom as user_nom, u.prenom as user_prenom
                FROM historique_article h
                LEFT JOIN users u ON h.user_id = u.id
                WHERE 1=1";

        $params = [];

        // Filtres
        if (!empty($filters['code_article'])) {
            $sql .= " AND h.code_article LIKE :code_article";
            $params[':code_article'] = '%' . $filters['code_article'] . '%';
        }

        if (!empty($filters['designation'])) {
            $sql .= " AND h.designation LIKE :designation";
            $params[':designation'] = '%' . $filters['designation'] . '%';
        }

        if (!empty($filters['operation'])) {
            $sql .= " AND h.operation = :operation";
            $params[':operation'] = $filters['operation'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(h.date_operation) >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(h.date_operation) <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['article_id'])) {
            $sql .= " AND h.article_id = :article_id";
            $params[':article_id'] = $filters['article_id'];
        }

        $sql .= " ORDER BY h.date_operation DESC, h.id DESC";

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre total de mouvements
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM historique_article WHERE 1=1";

        $params = [];

        if (!empty($filters['code_article'])) {
            $sql .= " AND code_article LIKE :code_article";
            $params[':code_article'] = '%' . $filters['code_article'] . '%';
        }

        if (!empty($filters['designation'])) {
            $sql .= " AND designation LIKE :designation";
            $params[':designation'] = '%' . $filters['designation'] . '%';
        }

        if (!empty($filters['operation'])) {
            $sql .= " AND operation = :operation";
            $params[':operation'] = $filters['operation'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(date_operation) >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(date_operation) <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['article_id'])) {
            $sql .= " AND article_id = :article_id";
            $params[':article_id'] = $filters['article_id'];
        }

        $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $this->db->execute();
        $result = $this->db->fetch();

        return $result['total'];
    }

    /**
     * Récupère un mouvement par ID
     */
    public function getById($id) {
        $sql = "SELECT h.*, u.nom as user_nom, u.prenom as user_prenom,
                       a.code as article_code, a.designation as article_designation
                FROM historique_article h
                LEFT JOIN users u ON h.user_id = u.id
                LEFT JOIN articles a ON h.article_id = a.id
                WHERE h.id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $id);
        $this->db->execute();

        return $this->db->fetch();
    }

    /**
     * Récupère l'historique d'un article spécifique
     */
    public function getByArticle($article_id, $limit = 50) {
        $sql = "SELECT h.*, u.nom as user_nom, u.prenom as user_prenom
                FROM historique_article h
                LEFT JOIN users u ON h.user_id = u.id
                WHERE h.article_id = :article_id
                ORDER BY h.date_operation DESC, h.id DESC
                LIMIT :limit";

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':article_id', $article_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Récupère les statistiques de mouvements
     */
    public function getStatistiques($date_debut = null, $date_fin = null) {
        $sql = "SELECT
                    operation,
                    COUNT(*) as nombre_mouvements,
                    SUM(qte) as quantite_totale
                FROM historique_article
                WHERE 1=1";

        $params = [];

        if ($date_debut) {
            $sql .= " AND DATE(date_operation) >= :date_debut";
            $params[':date_debut'] = $date_debut;
        }

        if ($date_fin) {
            $sql .= " AND DATE(date_operation) <= :date_fin";
            $params[':date_fin'] = $date_fin;
        }

        $sql .= " GROUP BY operation";

        $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $this->db->execute();

        return $this->db->fetchAll();
    }

    /**
     * Supprime les mouvements liés à une entrée
     */
    public function supprimerParEntree($entree_id) {
        $sql = "DELETE FROM historique_article WHERE entree_id = :entree_id";
        $this->db->prepare($sql);
        $this->db->bind(':entree_id', $entree_id);
        return $this->db->execute();
    }

    /**
     * Supprime les mouvements liés à une sortie
     */
    public function supprimerParSortie($sortie_id) {
        $sql = "DELETE FROM historique_article WHERE sortie_id = :sortie_id";
        $this->db->prepare($sql);
        $this->db->bind(':sortie_id', $sortie_id);
        return $this->db->execute();
    }

    /**
     * Supprime les mouvements liés à un retour
     */
    public function supprimerParRetour($retour_id) {
        $sql = "DELETE FROM historique_article WHERE retour_id = :retour_id";
        $this->db->prepare($sql);
        $this->db->bind(':retour_id', $retour_id);
        return $this->db->execute();
    }
}