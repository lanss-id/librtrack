<?php
/**
 * LibTrack ERP - Database Configuration
 * PDO with prepared statements — prevents SQL Injection.
 *
 * Mendukung environment variable (Docker/produksi).
 * Jika tidak ada ENV, fallback ke default XAMPP.
 */

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'libtrack_db');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');           // XAMPP default kosong
define('DB_CHARSET', 'utf8mb4');

/**
 * Singleton PDO connection.
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[LibTrack DB] ' . $e->getMessage());
            die('<div style="font-family:monospace;padding:2rem;color:#c00">
                <strong>Database Connection Failed.</strong><br>
                Pastikan MySQL berjalan dan konfigurasi di <code>config/database.php</code> sudah benar.<br>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
                </div>');
        }
    }
    return $pdo;
}
