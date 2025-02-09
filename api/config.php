<?php
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
$dbFile = __DIR__ . '/prompt_generator.sqlite';
error_log("Checking if the database file exists: " . $dbFile);
if (!file_exists($dbFile)) {
    error_log("Database file does not exist. Attempting to create it.");
    if (!touch($dbFile)) {
        error_log("Error creating the database file.");
        die("Database could not be created.");
    } else {
        error_log("Database file created successfully: " . $dbFile);
    }
}
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Successfully established connection to the SQLite database.");
} catch (PDOException $e) {
    error_log("Connection error: " . $e->getMessage());
    die("Error establishing connection: " . $e->getMessage());
}
