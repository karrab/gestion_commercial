<?php
/**
 * Classe Auth - Gestion de l'authentification
 */
class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Connecter un utilisateur
     */
    public function login($login, $password) {
        $sql = "SELECT u.*, r.nom as role_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.login = :login AND u.actif = 1
                LIMIT 1";

        $this->db->prepare($sql);
        $this->db->bind(':login', $login);
        $user = $this->db->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Enregistrement de la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_mail'] = $user['mail'];
            $_SESSION['user_role_id'] = $user['role_id'];
            $_SESSION['user_role_name'] = $user['role_name'];
            $_SESSION['last_activity'] = time();

            // Enregistrement de la trace de connexion
            $this->logTrace($user['id'], 'auth', 'login', 'users', $user['id'], 'Connexion réussie');

            return true;
        }

        return false;
    }

    /**
     * Déconnecter l'utilisateur
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logTrace($_SESSION['user_id'], 'auth', 'logout', 'users', $_SESSION['user_id'], 'Déconnexion');
        }

        session_unset();
        session_destroy();
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
            // Vérifier le timeout de session
            if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    /**
     * Récupérer l'utilisateur connecté
     */
    public function getUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'login' => $_SESSION['user_login'],
                'nom' => $_SESSION['user_nom'],
                'prenom' => $_SESSION['user_prenom'],
                'mail' => $_SESSION['user_mail'],
                'role_id' => $_SESSION['user_role_id'],
                'role_name' => $_SESSION['user_role_name']
            ];
        }
        return null;
    }

    /**
     * Récupérer l'ID de l'utilisateur connecté
     */
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin() {
        return isset($_SESSION['user_role_name']) && $_SESSION['user_role_name'] === 'admin';
    }

    /**
     * Vérifier une permission
     */
    public function hasPermission($module, $action) {
        // Admin a toutes les permissions
        if ($this->isAdmin()) {
            return true;
        }

        if (!isset($_SESSION['user_role_id'])) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count
                FROM role_permissions rp
                INNER JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id
                AND p.module = :module
                AND p.action = :action";

        $this->db->prepare($sql);
        $this->db->bind(':role_id', $_SESSION['user_role_id']);
        $this->db->bind(':module', $module);
        $this->db->bind(':action', $action);

        $result = $this->db->fetch();
        return $result['count'] > 0;
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Vérifier l'ancien mot de passe
        $sql = "SELECT password FROM users WHERE id = :id";
        $this->db->prepare($sql);
        $this->db->bind(':id', $userId);
        $user = $this->db->fetch();

        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return false;
        }

        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':id', $userId);

        if ($this->db->execute()) {
            $this->logTrace($userId, 'users', 'change_password', 'users', $userId, 'Changement de mot de passe');
            return true;
        }

        return false;
    }

    /**
     * Enregistrer une trace d'activité
     */
    public function logTrace($userId, $module, $action, $tableName, $recordId, $description = '') {
        $sql = "INSERT INTO traces (user_id, module, action, table_name, record_id, description, ip_address)
                VALUES (:user_id, :module, :action, :table_name, :record_id, :description, :ip_address)";

        try {
            $this->db->prepare($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':module', $module);
            $this->db->bind(':action', $action);
            $this->db->bind(':table_name', $tableName);
            $this->db->bind(':record_id', $recordId);
            $this->db->bind(':description', $description);
            $this->db->bind(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
            $this->db->execute();
        } catch (Exception $e) {
            // Ne pas bloquer l'exécution si le log échoue
            error_log("Erreur lors de l'enregistrement de la trace: " . $e->getMessage());
        }
    }

    /**
     * Vérifier l'accès et rediriger si non autorisé
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }

    /**
     * Vérifier une permission et rediriger si non autorisé
     */
    public function requirePermission($module, $action) {
        $this->requireLogin();

        if (!$this->hasPermission($module, $action)) {
            $_SESSION['error'] = "Vous n'avez pas la permission d'effectuer cette action.";
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }

    /**
     * Vérifier si admin et rediriger si non autorisé
     */
    public function requireAdmin() {
        $this->requireLogin();

        if (!$this->isAdmin()) {
            $_SESSION['error'] = "Seul un administrateur peut effectuer cette action.";
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}
