<?php
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (ob_get_length()) {
    ob_clean();
}
require 'config.php';
error_log("get_prefabs.php started.");
// Create table "prefabs" if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS prefabs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        parent_id INTEGER DEFAULT NULL,
        FOREIGN KEY (parent_id) REFERENCES prefabs(id)
    )");
    error_log("Table 'prefabs' checked/created.");
} catch (PDOException $e) {
    error_log("Error creating table 'prefabs': " . $e->getMessage());
}
try {
    // Create table "prefab_options" if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS prefab_options (
        prefab_id INTEGER NOT NULL,
        option_id INTEGER NOT NULL,
        PRIMARY KEY (prefab_id, option_id),
        FOREIGN KEY (prefab_id) REFERENCES prefabs(id),
        FOREIGN KEY (option_id) REFERENCES options(id)
    )");
    error_log("Table 'prefab_options' checked/created.");
} catch (PDOException $e) {
    error_log("Error creating table 'prefab_options': " . $e->getMessage());
}
try {
    $stmt = $pdo->query("SELECT id, name, parent_id FROM prefabs ORDER BY name");
    $prefabs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $prefabId = $row['id'];
        $optStmt = $pdo->prepare("SELECT option_id FROM prefab_options WHERE prefab_id = :prefab_id");
        $optStmt->bindParam(":prefab_id", $prefabId);
        $optStmt->execute();
        $selectedOptions = $optStmt->fetchAll(PDO::FETCH_COLUMN);
        $row['selectedOptions'] = $selectedOptions;
        $prefabs[] = $row;
        error_log("Prefab loaded: " . print_r($row, true));
    }
    error_log("Number of prefabs loaded: " . count($prefabs));
    function buildTree(array $elements, $parentId = null) {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = buildTree($elements, $element['id']);
                $element['children'] = $children ? $children : [];
                $branch[] = $element;
            }
        }
        return $branch;
    }
    $tree = buildTree($prefabs);
    error_log("Tree structure created: " . print_r($tree, true));
    echo json_encode($tree, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("SQL error in get_prefabs.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "SQL error: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
