<?php

/**
 * Singleton třída pro správu databázového připojení
 *
 * Poskytuje jediné sdílené PDO připojení k databázi
 * pro celou aplikaci.
 */
class Database {
    private static $instance = null;
    private $pdo;

    /**
     * Privátní konstruktor - zajišťuje singleton pattern
     */
    private function __construct() {
        $host = DB_HOST;
        $db   = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        $charset = DB_CHARSET;
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Vrací instanci třídy Database (singleton)
     *
     * @return Database Instance třídy Database
     */
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Vrací PDO připojení k databázi
     *
     * @return PDO Objekt PDO připojení
     */
    public function getConnection() {
        return $this->pdo;
    }
}
?>

