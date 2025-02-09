<?php
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (ob_get_length()) {
    ob_clean();
}
require 'config.php';
error_log("get_options.php started.");
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS options (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        display_name VARCHAR(255) NOT NULL,
        value VARCHAR(255) NOT NULL,
        category VARCHAR(255) NOT NULL
    )");
    error_log("Table 'options' checked/created.");
    $stmt = $pdo->query("SELECT id, display_name, value, category FROM options ORDER BY category, display_name");
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Options loaded from database: " . print_r($options, true));
    $grouped = [];
    foreach ($options as $opt) {
        $grouped[$opt['category']][] = $opt;
    }
    error_log("Options grouped: " . print_r($grouped, true));
    echo json_encode($grouped, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("SQL error in get_options.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "SQL error: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
