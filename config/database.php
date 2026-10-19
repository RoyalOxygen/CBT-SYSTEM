<?php
/**
 * CBT System - Database Configuration
 * PDO Connection with error handling
 */

// Prevent direct access
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

require_once __DIR__ . '/node.php';

class Database {
    private static ?PDO $instance = null;

    /**
     * Get PDO database connection instance (Singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $host = defined('NODE_DB_HOST') ? NODE_DB_HOST : 'localhost';
                $name = defined('NODE_DB_NAME') ? NODE_DB_NAME : 'cbt_system';
                $user = defined('NODE_DB_USER') ? NODE_DB_USER : 'root';
                $pass = defined('NODE_DB_PASS') ? NODE_DB_PASS : '';
                $charset = 'utf8mb4';

                $dsn = "mysql:host=" . $host . ";dbname=" . $name . ";charset=" . $charset;
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
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $charset . " COLLATE utf8mb4_unicode_ci, time_zone = '+01:00'"
                ];
                self::$instance = new PDO($dsn, $user, $pass, $options);

                // Used by sync triggers to attribute change-log rows.
                self::$instance->exec(
                    "SET @cbt_node_uid = " . self::$instance->quote(defined('NODE_UID') ? NODE_UID : 'unknown')
                    . ", @cbt_sync_applying = 0"
                );
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
