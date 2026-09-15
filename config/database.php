<?php
/**
 * CBT System - Database Configuration
 * PDO Connection with error handling
 */

// Prevent direct access
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

class Database {
    private static ?PDO $instance = null;
    
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'cbt_system';
    private const DB_USER = 'root';
    private const DB_PASS = '';  // Change in production
    private const DB_CHARSET = 'utf8mb4';
    
    /**
     * Get PDO database connection instance (Singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    // Pin MySQL's session timezone to match PHP's
                    // date_default_timezone_set('Africa/Lagos') in config.php.
                    // Without this, MySQL defaults to its own SYSTEM/global
                    // timezone, which can differ from PHP's - causing NOW()
                    // timestamps written by MySQL to be misread by PHP's
                    // strtotime(), and vice versa. That mismatch is what was
                    // producing wildly wrong exam timer values.
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET . " COLLATE utf8mb4_unicode_ci, time_zone = '+01:00'"
                ];
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                die(json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']));
            }
        }
        return self::$instance;
    }
    
    /**
     * Prevent cloning and unserialization
     */
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get DB connection
 */
function getDB(): PDO {
    return Database::getInstance();
}