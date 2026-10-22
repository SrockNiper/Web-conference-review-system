<?php

/**
 * Model pro správu uživatelů
 *
 * Zajišťuje operace s uživateli včetně automatické detekce názvů sloupců
 * v databázi a normalizace výsledků na jednotnou strukturu.
 */
class UserModel {
    private $db;
    private $cols = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->initColumns();
    }

    /**
     * Inicializuje názvy sloupců podle skutečné struktury tabulky
     *
     * @return void
     */
    private function initColumns() {
        try {
            $stmt = $this->db->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            $columns = [];
        }

        // mapa lower => original pro case-insensitive hledání
        $columnsLower = [];
        foreach ($columns as $col) {
            $columnsLower[strtolower($col)] = $col;
        }

        $has = function(array $candidates) use ($columnsLower) {
            foreach ($candidates as $c) {
                $low = strtolower($c);
                if (isset($columnsLower[$low])) return $columnsLower[$low];
            }
            return null;
        };

        $this->cols['id'] = $has(['id', 'ID', 'user_id', 'userid']);
        $this->cols['username'] = $has(['username', 'user', 'login', 'name', 'nick']);
        $this->cols['email'] = $has(['email', 'mail', 'e_mail']);
        $this->cols['password'] = $has(['password', 'pass', 'passwd', 'passwd_hash']);
        $this->cols['role'] = $has(['role', 'user_role', 'rol']);
        $this->cols['first_name'] = $has(['first_name', 'firstname', 'fname','name']);
        $this->cols['last_name'] = $has(['last_name', 'lastname', 'surname', 'lname']);

    }

    /**
     * Normalizuje řádek z databáze na jednotnou strukturu
     *
     * @param array|false $row Řádek z databáze
     * @return array|null Normalizovaná data uživatele nebo null
     */
    private function normalizeRow($row) {
        if (!$row || !is_array($row)) return null;

        $findInRow = function(array $row, array $candidates) {
            foreach ($candidates as $c) {
                if (array_key_exists($c, $row)) return $row[$c];
            }
            foreach ($row as $k => $v) {
                foreach ($candidates as $c) {
                    if (strcasecmp($k, $c) === 0) return $v;
                }
            }
            return null;
        };

        $get = function($canon) use ($row, $findInRow) {
            $mapCandidates = [
                'id' => ['id', 'ID', 'user_id', 'userid', 'userId'],
                'username' => ['username', 'user', 'login', 'name', 'nick'],
                'email' => ['email', 'mail', 'e_mail'],
                'role' => ['role', 'user_role', 'rol'],
                'created_at' => ['created_at', 'created', 'created_on', 'registered_at', 'reg_date'],
                'password' => ['password', 'pass', 'passwd', 'passwd_hash'],
                'first_name' => ['first_name', 'firstname', 'fname'],
                'last_name' => ['last_name', 'lastname', 'surname', 'lname']
            ];

            if (!empty($this->cols[$canon])) {
                $col = $this->cols[$canon];
                if (array_key_exists($col, $row)) return $row[$col];
            }

            $candidates = $mapCandidates[$canon] ?? [$canon];
            return $findInRow($row, $candidates);
        };

        return [
            'id' => $get('id'),
            'username' => $get('username'),
            'email' => $get('email'),
            'role' => $get('role'),
            'created_at' => $get('created_at'),
            'first_name' => $get('first_name'),
            'last_name' => $get('last_name')
        ];
    }

    /**
     * Získá uživatele podle ID
     *
     * @param int $id ID uživatele
     * @return array|null Normalizovaná data uživatele nebo null
     */
    public function getById($id) {
        $idCol = $this->cols['id'] ?? 'id';
        $sql = "SELECT * FROM users WHERE {$idCol} = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $this->normalizeRow($row);
    }

    /**
     * Získá uživatele podle uživatelského jména
     *
     * @param string $username Uživatelské jméno
     * @return array|null Normalizovaná data uživatele nebo null
     */
    public function getByUsername($username) {
        if(!$this->cols['username']) {
            return null;
        }
        $col = $this->cols['username'];
        $sql = "SELECT * FROM users WHERE {$col} = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $this->normalizeRow($row);
    }

    /**
     * Získá uživatele podle emailové adresy
     *
     * @param string $email Email uživatele
     * @return array|null Normalizovaná data uživatele nebo null
     */
    public function getByEmail($email) {
        if(!$this->cols['email']) {
            return null;
        }
        $col = $this->cols['email'];
        $sql = "SELECT * FROM users WHERE {$col} = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $this->normalizeRow($row);
    }

    /**
     * Získá všechny uživatele s možností řazení
     *
     * @param string $sortBy Sloupec pro řazení
     * @param string $dir Směr řazení (ASC nebo DESC)
     * @return array[] Pole normalizovaných dat uživatelů
     */
    public function getAll($sortBy = 'id', $dir = 'DESC') {
        $allowed = ['id','username','first_name','last_name','email','role','created_at'];
        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = 'id';
        }
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $selectCols = [];
        foreach ($allowed as $canon) {
            if (!empty($this->cols[$canon])) {
                $selectCols[] = $this->cols[$canon] . " AS {$canon}";
            }
        }

        if (!empty($selectCols) && !empty($this->cols[$sortBy])) {
            $orderExpr = "{$sortBy} {$dir}";
        } else {
            $orderExpr = ($this->cols[$sortBy] ?? $sortBy) . " {$dir}";
        }
        $orderCol = $this->cols[$sortBy] ?? $sortBy;
        $sql = "SELECT * FROM users ORDER BY {$orderCol} {$dir}";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();
        $results = [];
        foreach ($rows as $r) {
            $results[] = $this->normalizeRow($r);
        }
        return $results;
    }

    /**
     * Vytvoří nového uživatele v databázi
     *
     * @param array $data Asociativní pole s daty uživatele (username, email, password, role, first_name, last_name)
     * @return int|false ID nově vytvořeného uživatele nebo false při chybě
     */
    public function createUser(array $data) {
        $available = [];
        $params = [];

        if($this->cols['username'] && isset($data['username'])) {
            $available[$this->cols['username']] = ':username';
            $params[':username'] = $data['username'];
        }
        if($this->cols['email'] && isset($data['email'])) {
            $available[$this->cols['email']] = ':email';
            $params[':email'] = $data['email'];
        }
        if($this->cols['password'] && isset($data['password'])) {
            $available[$this->cols['password']] = ':password';
            $params[':password'] = $data['password'];
        }
        if($this->cols['role'] && isset($data['role'])) {
            $available[$this->cols['role']] = ':role';
            $params[':role'] = $data['role'];
        }

        if($this->cols['first_name'] && isset($data['first_name'])) {
            $available[$this->cols['first_name']] = ':first_name';
            $params[':first_name'] = $data['first_name'];
        }
        if($this->cols['last_name'] && isset($data['last_name'])) {
            $available[$this->cols['last_name']] = ':last_name';
            $params[':last_name'] = $data['last_name'];
        }

        if(empty($available)) {
            return false;
        }

        $cols = array_keys($available);
        $placeholders = array_values($available);

        $sql = "INSERT INTO users (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);

        if($stmt->execute($params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Získá názvy sloupců tabulky users
     *
     * @return string[] Pole názvů sloupců
     */
    private function getTableColumns() {
        try {
            $stmt = $this->db->query("DESCRIBE users");
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Ověří přihlašovací údaje uživatele
     *
     * @param string $usernameOrEmail Uživatelské jméno nebo email
     * @param string $password Heslo v plain textu
     * @return array|false Normalizovaná data uživatele nebo false při chybě
     */
    public function verifyCredentials($usernameOrEmail, $password) {
        $user = null;
        $rawUser = null;

        if ($this->cols['username']) {
            $col = $this->cols['username'];
            $sql = "SELECT * FROM users WHERE {$col} = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usernameOrEmail]);
            $rawUser = $stmt->fetch();
            if ($rawUser) {
                $user = $this->normalizeRow($rawUser);
            }
        }

        if (!$user && $this->cols['email']) {
            $col = $this->cols['email'];
            $sql = "SELECT * FROM users WHERE {$col} = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usernameOrEmail]);
            $rawUser = $stmt->fetch();
            if ($rawUser) {
                $user = $this->normalizeRow($rawUser);
            }
        }

        if (!$user || !$rawUser) {
            return false;
        }

        $passwordCol = $this->cols['password'];
        if (!$passwordCol || !isset($rawUser[$passwordCol])) {
            return false;
        }

        $storedPassword = $rawUser[$passwordCol];

        if (password_verify($password, $storedPassword)) {
            return $user;
        }

        return false;
    }

    /**
     * Získá mapování ID rolí na jejich názvy
     *
     * @return array Asociativní pole [id => název]
     */
    public function getRoles() {
        try {
            $stmt = $this->db->query("SELECT id, name FROM roles");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ($rows as $r) {
                $map[(int)$r['id']] = $r['name'];
            }
            return $map;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Převede ID role na čitelný název
     *
     * @param mixed $role ID nebo název role
     * @param array $rolesMap Mapa rolí [id => název]
     * @return string Název role nebo placeholder
     */
    public function resolveRoleLabel($role, array $rolesMap = []) {
        if ($role === null || $role === '') return '-';
        if (is_numeric($role)) {
            $rid = (int)$role;
            if (isset($rolesMap[$rid])) return $rolesMap[$rid];
            return 'Role #' . $rid;
        }
        return htmlspecialchars((string)$role);
    }



    public function updateRole($id, $roleValue) {
        $roleCol = $this->cols['role'] ?? 'role';
        $idCol = $this->cols['id'] ?? 'id';
        try {
            $sql = "UPDATE users SET {$roleCol} = :role WHERE {$idCol} = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':role' => $roleValue, ':id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }
    public function countArticlesByAuthor($userId) {
        $sql = "SELECT COUNT(*) as cnt FROM articles WHERE author_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['cnt'];
    }
    public function countReviewsByReviewer($userId) {
        $sql = "SELECT COUNT(*) as cnt FROM article_reviewers WHERE reviewer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['cnt'];
    }

    public function deleteById($userId) {
        // Kontrola vazeb
        $articlesCount = $this->countArticlesByAuthor($userId);
        $reviewsCount = $this->countReviewsByReviewer($userId);

        if ($articlesCount > 0) {
            return ['success' => false, 'message' => "Uživatel má {$articlesCount} článků. Nejprve je smažte."];
        }

        if ($reviewsCount > 0) {
            return ['success' => false, 'message' => "Uživatel má {$reviewsCount} přiřazených recenzí. Nejprve je odeberte."];
        }

        $sql = "DELETE FROM {$this->tableName} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$userId]);

        return ['success' => $success, 'message' => $success ? 'Uživatel smazán.' : 'Chyba při mazání.'];
    }
}
?>
