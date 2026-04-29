<?php
/**
 * Uyut Rental Agency - Database Connection
 */

// Create database directory if it doesn't exist
$dbDir = dirname(__DIR__) . '/database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$dbFile = $dbDir . '/uyut.db';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
