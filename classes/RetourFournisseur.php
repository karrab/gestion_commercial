<?php
/**
 * Classe RetourFournisseur
 * Gère les retours d'articles vers les fournisseurs (articles défectueux, non conformes, etc.)
 */
class RetourFournisseur {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère tous les retours fournisseurs avec pagination et filtres
     */
    public function getAll($page = 1, $perPage = DEFAULT_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT rf.*, f.nom_complet as fournisseur, f.tel1 as fournisseur_tel,
                       u.nom as user_nom, u.prenom as user_prenom,
                       COUNT(lrf.id) as nb_articles,
                       SUM(lrf.qte) as qte_totale
                FROM retour_fournisseur rf
                INNER JOIN fournisseurs f ON rf.fournisseur_id = f.id
                INNER JOIN users u ON rf.user_id = u.id
                LEFT JOIN ligne_retour_fournisseur lrf ON rf.id = lrf.retour_fournisseur_id
                WHERE 1=1";

        $params = [];

        // Filtres
        if (!empty($filters['fournisseur_id'])) {
            $sql .= " AND rf.fournisseur_id = :fournisseur_id";
            $params[':fournisseur_id'] = $filters['fournisseur_id'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND rf.date >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND rf.date <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        $sql .= " GROUP BY rf.id
                  ORDER BY rf.date DESC, rf.id DESC
                  LIMIT :limit OFFSET :offset";

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
     * Compte le nombre total de retours fournisseurs
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM retour_fournisseur WHERE 1=1";

        $params = [];

        if (!empty($filters['fournisseur_id'])) {
            $sql .= " AND fournisseur_id = :fournisseur_id";
            $params[':fournisseur_id'] = $filters['fournisseur_id'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND date >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND date <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
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
     * Récupère un retour fournisseur par ID avec ses lignes
     */
    public function getById($id) {
        $sql = "SELECT rf.*, f.nom_complet as fournisseur, f.adresse as fournisseur_adresse,
                       f.ville as fournisseur_ville, f.tel1 as fournisseur_tel,
                       
                       u.nom as user_nom, u.prenom as user_prenom
                FROM retour_fournisseur rf
                INNER JOIN fournisseurs f ON rf.fournisseur_id = f.id
                INNER JOIN users u ON rf.user_id = u.id
                WHERE rf.id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $id);
        $this->db->execute();

        $retour = $this->db->fetch();

        if ($retour) {
            // Récupérer les lignes
            $retour['lignes'] = $this->getLignesByRetourId($id);
        }

        return $retour;
    }

    /**
     * Récupère les lignes d'un retour fournisseur
     */
    public function getLignesByRetourId($retour_id) {
        $sql = "SELECT lrf.*, a.qte_disponible as stock_actuel
                FROM ligne_retour_fournisseur lrf
                INNER JOIN articles a ON lrf.article_id = a.id
                WHERE lrf.retour_fournisseur_id = :retour_id
                ORDER BY lrf.id";

        $this->db->prepare($sql);
        $this->db->bind(':retour_id', $retour_id);
        $this->db->execute();

        return $this->db->fetchAll();
    }

    /**
     * Crée un nouveau retour fournisseur
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Insertion retour fournisseur
            $sql = "INSERT INTO retour_fournisseur (fournisseur_id, date, fichier, notes, user_id)
                    VALUES (:fournisseur_id, :date, :fichier, :notes, :user_id)";

            $this->db->prepare($sql);
            $this->db->bind(':fournisseur_id', $data['fournisseur_id']);
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':fichier', $data['fichier'] ?? null);
            $this->db->bind(':notes', $data['notes'] ?? null);
            $this->db->bind(':user_id', $data['user_id']);
            $this->db->execute();

            $retour_id = $this->db->lastInsertId();

            // Insertion lignes et mise à jour stocks
            if (!empty($data['articles'])) {
                $historique = new HistoriqueArticle();

                foreach ($data['articles'] as $article) {
                    // Récupérer infos article
                    $this->db->prepare("SELECT code_article, designation FROM articles WHERE id = :id");
                    $this->db->bind(':id', $article['article_id']);
                    $art = $this->db->fetch();

                    if (!$art) {
                        throw new Exception("Article ID {$article['article_id']} introuvable");
                    }

                    // Insertion ligne
                    $sql = "INSERT INTO ligne_retour_fournisseur
                            (retour_fournisseur_id, article_id, code_article, designation, qte)
                            VALUES (:retour_id, :article_id, :code, :designation, :qte)";

                    $this->db->prepare($sql);
                    $this->db->bind(':retour_id', $retour_id);
                    $this->db->bind(':article_id', $article['article_id']);
                    $this->db->bind(':code', $art['code_article']);
                    $this->db->bind(':designation', $art['designation']);
                    $this->db->bind(':qte', $article['qte']);
                    $this->db->execute();

                    // Mise à jour stock (DIMINUTION car retour vers fournisseur)
                    $sql = "UPDATE articles
                            SET qte_disponible = qte_disponible - :qte
                            WHERE id = :article_id";

                    $this->db->prepare($sql);
                    $this->db->bind(':qte', $article['qte']);
                    $this->db->bind(':article_id', $article['article_id']);
                    $this->db->execute();

                    // Enregistrement dans l'historique
                    $stock_avant = $this->getStockActuel($article['article_id']) + $article['qte'];
                    $stock_apres = $stock_avant - $article['qte'];

                    $historique->enregistrerMouvement([
                        'article_id' => $article['article_id'],
                        'operation' => 'retour_fournisseur',
                        'qte' => $article['qte'],
                        'stock_avant_operation' => $stock_avant,
                        'stock_apres_operation' => $stock_apres,
                        'retour_fournisseur_id' => $retour_id,
                        'user_id' => $data['user_id'],
                        'date_operation' => $data['date'],
                        'commentaire' => 'Retour vers fournisseur'
                    ]);
                }
            }

            $this->db->commit();
            return $retour_id;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Met à jour un retour fournisseur
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();

            // Récupérer les anciennes lignes pour restaurer les stocks
            $anciennes_lignes = $this->getLignesByRetourId($id);

            // Restaurer les stocks des anciennes lignes
            foreach ($anciennes_lignes as $ligne) {
                $sql = "UPDATE articles
                        SET qte_disponible = qte_disponible + :qte
                        WHERE id = :article_id";

                $this->db->prepare($sql);
                $this->db->bind(':qte', $ligne['qte']);
                $this->db->bind(':article_id', $ligne['article_id']);
                $this->db->execute();
            }

            // Supprimer les anciennes lignes
            $this->db->prepare("DELETE FROM ligne_retour_fournisseur WHERE retour_fournisseur_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Supprimer les anciens mouvements de l'historique
            $historique = new HistoriqueArticle();
            $this->db->prepare("DELETE FROM historique_article WHERE retour_fournisseur_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Mise à jour retour fournisseur
            $sql = "UPDATE retour_fournisseur
                    SET fournisseur_id = :fournisseur_id,
                        date = :date,
                        notes = :notes
                    WHERE id = :id";

            $this->db->prepare($sql);
            $this->db->bind(':fournisseur_id', $data['fournisseur_id']);
            $this->db->bind(':date', $data['date']);
            $this->db->bind(':notes', $data['notes'] ?? null);
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Insérer les nouvelles lignes
            if (!empty($data['articles'])) {
                foreach ($data['articles'] as $article) {
                    // Récupérer infos article
                    $this->db->prepare("SELECT code_article, designation FROM articles WHERE id = :id");
                    $this->db->bind(':id', $article['article_id']);
                    $art = $this->db->fetch();

                    if (!$art) {
                        throw new Exception("Article ID {$article['article_id']} introuvable");
                    }

                    // Insertion ligne
                    $sql = "INSERT INTO ligne_retour_fournisseur
                            (retour_fournisseur_id, article_id, code_article, designation, qte)
                            VALUES (:retour_id, :article_id, :code, :designation, :qte)";

                    $this->db->prepare($sql);
                    $this->db->bind(':retour_id', $id);
                    $this->db->bind(':article_id', $article['article_id']);
                    $this->db->bind(':code', $art['code_article']);
                    $this->db->bind(':designation', $art['designation']);
                    $this->db->bind(':qte', $article['qte']);
                    $this->db->execute();

                    // Mise à jour stock
                    $sql = "UPDATE articles
                            SET qte_disponible = qte_disponible - :qte
                            WHERE id = :article_id";

                    $this->db->prepare($sql);
                    $this->db->bind(':qte', $article['qte']);
                    $this->db->bind(':article_id', $article['article_id']);
                    $this->db->execute();

                    // Enregistrement dans l'historique
                    $stock_avant = $this->getStockActuel($article['article_id']) + $article['qte'];
                    $stock_apres = $stock_avant - $article['qte'];

                    $historique->enregistrerMouvement([
                        'article_id' => $article['article_id'],
                        'operation' => 'retour_fournisseur',
                        'qte' => $article['qte'],
                        'stock_avant_operation' => $stock_avant,
                        'stock_apres_operation' => $stock_apres,
                        'retour_fournisseur_id' => $id,
                        'user_id' => $data['user_id'],
                        'date_operation' => $data['date'],
                        'commentaire' => 'Retour vers fournisseur (modifié)'
                    ]);
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Supprime un retour fournisseur
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();

            // Récupérer les lignes pour restaurer les stocks
            $lignes = $this->getLignesByRetourId($id);

            // Restaurer les stocks
            foreach ($lignes as $ligne) {
                $sql = "UPDATE articles
                        SET qte_disponible = qte_disponible + :qte
                        WHERE id = :article_id";

                $this->db->prepare($sql);
                $this->db->bind(':qte', $ligne['qte']);
                $this->db->bind(':article_id', $ligne['article_id']);
                $this->db->execute();
            }

            // Supprimer de l'historique
            $this->db->prepare("DELETE FROM historique_article WHERE retour_fournisseur_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Supprimer les lignes (CASCADE le fait automatiquement)
            // Supprimer le retour
            $this->db->prepare("DELETE FROM retour_fournisseur WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Récupère le stock actuel d'un article
     */
    private function getStockActuel($article_id) {
        $sql = "SELECT qte_disponible FROM articles WHERE id = :id";
        $this->db->prepare($sql);
        $this->db->bind(':id', $article_id);
        $this->db->execute();
        $result = $this->db->fetch();
        return $result ? (float)$result['qte_disponible'] : 0;
    }
}
