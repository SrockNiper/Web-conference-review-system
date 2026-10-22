<?php

/**
 * Pomocná třída pro práci s uživateli a rolemi
 *
 * Poskytuje metody pro ověřování rolí, získávání názvů rolí
 * a formátování uživatelských dat pro zobrazení.
 */
class UserHelper {
    private static $roleCache = null;

    /**
     * Načte role z databáze a uloží do cache
     *
     * @return array Asociativní pole [id => název_role]
     */
    private function loadRoles() {
        if (self::$roleCache !== null) {
            return self::$roleCache;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, name FROM roles");
            $roles = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $roles[$row['id']] = strtolower($row['name']);
            }
            self::$roleCache = $roles;
            return $roles;
        } catch (PDOException $e) {
            error_log("Chyba při načítání rolí: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Získá název role podle ID
     *
     * @param int $roleId ID role
     * @return string|null Název role nebo null pokud neexistuje
     */
    public function getRoleName($roleId) {
        $roles = $this->loadRoles();
        return $roles[$roleId] ?? null;
    }

    /**
     * Zjistí, zda má uživatel danou roli
     *
     * @param array|null $user Pole s uživatelskými daty
     * @param string|string[] $requiredRoles Název role nebo pole názvů rolí
     * @return bool True pokud uživatel má některou z požadovaných rolí
     */
    public function hasRole($user, $requiredRoles) {
        if (!$user || !is_array($user)) {
            return false;
        }

        $userRoleId = $user['role'] ?? null;
        if (!$userRoleId) {
            return false;
        }

        $userRoleName = $this->getRoleName($userRoleId);
        if (!$userRoleName) {
            return false;
        }

        // Normalizace na pole
        if (!is_array($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }

        // Normalizace na malá písmena
        $requiredRoles = array_map('strtolower', $requiredRoles);

        return in_array($userRoleName, $requiredRoles, true);
    }

    /**
     * Zjistí, zda je uživatel administrátor
     *
     * @param array|null $user Pole s uživatelskými daty
     * @return bool True pokud má roli admin nebo super-admin
     */
    public function isAdmin($user) {
        return $this->hasRole($user, ['admin', 'super-admin']);
    }

    /**
     * Získá zobrazované jméno uživatele
     *
     * @param array|null $user Pole s uživatelskými daty
     * @return string Formátované jméno nebo placeholder
     */
    public function getDisplayName($user) {
        if (!$user || !is_array($user)) {
            return 'Nepřihlášený';
        }

        if (!empty($user['username'])) {
            return $user['username'];
        }

        $firstName = $user['first_name'] ?? $user['name'] ?? '';
        $lastName = $user['last_name'] ?? $user['surname'] ?? '';

        if ($firstName || $lastName) {
            return trim($firstName . ' ' . $lastName);
        }

        return 'Uživatel';
    }

    /**
     * Získá ID role podle názvu
     *
     * @param string $roleName Název role (case-insensitive)
     * @return int|null ID role nebo null pokud neexistuje
     */
    public function getRoleIdByName($roleName) {
        $roles = $this->loadRoles();
        $normalizedName = strtolower($roleName);

        foreach ($roles as $id => $name) {
            if ($name === $normalizedName) {
                return $id;
            }
        }

        return null;
    }
}
