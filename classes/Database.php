<?php
/**
 * Classe Database - Gestion de la connexion et des requêtes MySQL
 */
class Database {
    private static $instance = null;
    private $connection;
    private $stmt;

    /**
     * Constructeur privé pour singleton
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    /**
     * Récupère l'instance unique de la base de données
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Récupère la connexion PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Prépare une requête SQL
     */
    public function prepare($sql) {
        $this->stmt = $this->connection->prepare($sql);
        return $this;
    }

    /**
     * Bind des valeurs (supporte à la fois les paramètres nommés et positionnels)
     */
    public function bind($param, $value, $type = null) {
        if (is_int($param)) {
            // Paramètre positionnel (commence à 1 pour PDO)
            $param = $param;
        } else {
            // Paramètre nommé
            if (strpos($param, ':') !== 0) {
                $param = ':' . $param;
            }
        }

        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    /**
     * Exécute la requête préparée
     */
    public function execute($params = null) {
        try {
            if ($params !== null && !empty($params)) {
                // Binder chaque valeur avec le bon type pour éviter LIMIT '10'
                foreach ($params as $key => $value) {
                    $paramKey = is_int($key) ? $key + 1 : $key;
                    if (is_int($value)) {
                        $this->stmt->bindValue($paramKey, $value, PDO::PARAM_INT);
                    } elseif (is_null($value)) {
                        $this->stmt->bindValue($paramKey, $value, PDO::PARAM_NULL);
                    } else {
                        $this->stmt->bindValue($paramKey, $value, PDO::PARAM_STR);
                    }
                }
                return $this->stmt->execute();
            } else {
                return $this->stmt->execute();
            }
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            error_log("Requête: " . $this->stmt->queryString);
            throw $e;
        }
    }

    /**
     * Retourne toutes les lignes
     */
    public function fetchAll() {
        $this->stmt->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Retourne une seule ligne
     */
    public function fetch() {
        $this->stmt->execute();
        return $this->stmt->fetch();
    }

    /**
     * Retourne une seule valeur
     */
    public function fetchColumn() {
        $this->stmt->execute();
        return $this->stmt->fetchColumn();
    }

    /**
     * Retourne le nombre de lignes affectées
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    /**
     * Retourne le dernier ID inséré
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Démarre une transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Annule une transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Requête simple sans paramètres
     */
    public function query($sql, $params = []) {
    if (empty($params)) {
        return $this->connection->query($sql);
    } else {
        // Utilisation de requêtes préparées avec paramètres
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

    /**
     * Requête avec pagination Keyset (plus performant que OFFSET)
     */
    public function paginateKeyset($sql, $params = [], $lastId = 0, $perPage = DEFAULT_PER_PAGE, $orderColumn = 'id', $orderDir = 'ASC') {
        // Ajoute la condition WHERE pour keyset pagination
        if ($lastId > 0) {
            if ($orderDir === 'ASC') {
                $sql .= " AND $orderColumn > :last_id";
            } else {
                $sql .= " AND $orderColumn < :last_id";
            }
            $params[':last_id'] = $lastId;
        }

        // Ajoute ORDER BY et LIMIT
        $sql .= " ORDER BY $orderColumn $orderDir LIMIT :limit";
        $params[':limit'] = (int)$perPage;

        $stmt = $this->connection->prepare($sql);

        // Bind des paramètres
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre total de résultats
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        if ($where) {
            $sql .= " WHERE $where";
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }

    /**
     * Échapper une chaîne (utilisé pour recherche LIKE)
     */
    public function escape($string) {
        return str_replace(['%', '_'], ['\%', '\_'], $string);
    }

    /**
     * Déboguer une requête préparée
     */
    public function debug() {
        if ($this->stmt) {
            ob_start();
            $this->stmt->debugDumpParams();
            $debug = ob_get_clean();
            error_log("Debug SQL: " . $debug);
            return $debug;
        }
        return null;
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}