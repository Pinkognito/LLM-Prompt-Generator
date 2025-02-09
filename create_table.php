<?php
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
$dbFile = __DIR__ . '/api/prompt_generator.sqlite';
echo "Database path: " . $dbFile . "<br>";
if (!file_exists($dbFile)) {
    echo "Database file does not exist. Attempting to create it...<br>";
    if (!touch($dbFile)) {
        die("Error: Database file could not be created.");
    } else {
        echo "Database file successfully created.<br>";
    }
}
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Successfully established connection to the SQLite database.<br>";
    // Create or update table "prefabs"
    $sqlPrefabs = "CREATE TABLE IF NOT EXISTS prefabs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        parent_id INTEGER DEFAULT NULL,
        FOREIGN KEY (parent_id) REFERENCES prefabs(id)
    )";
    $pdo->exec($sqlPrefabs);
    echo "Table 'prefabs' was successfully created or already exists.<br>";
    // Create or update table "options"
    $sqlOptions = "CREATE TABLE IF NOT EXISTS options (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        display_name VARCHAR(255) NOT NULL,
        value VARCHAR(255) NOT NULL,
        category VARCHAR(255) NOT NULL
    )";
    $pdo->exec($sqlOptions);
    echo "Table 'options' was successfully created or already exists.<br>";
    // Unique index for options
    $sqlIndex = "CREATE UNIQUE INDEX IF NOT EXISTS idx_options_category_value ON options(category, value)";
    $pdo->exec($sqlIndex);
    echo "Unique index 'idx_options_category_value' created or already exists.<br>";
    // Create or update table "prefab_options"
    $sqlJoin = "CREATE TABLE IF NOT EXISTS prefab_options (
        prefab_id INTEGER NOT NULL,
        option_id INTEGER NOT NULL,
        PRIMARY KEY (prefab_id, option_id),
        FOREIGN KEY (prefab_id) REFERENCES prefabs(id),
        FOREIGN KEY (option_id) REFERENCES options(id)
    )";
    $pdo->exec($sqlJoin);
    echo "Table 'prefab_options' was successfully created or already exists.<br>";
    // Insert known options (INSERT OR IGNORE)
    $knownOptions = [
        "Feature Packages" => [
            ["display_name" => "Detailed Description", "value" => "detailed"],
            ["display_name" => "Creative Approaches", "value" => "creative"],
            ["display_name" => "Analytical Perspective", "value" => "analytical"],
            ["display_name" => "Technical Expertise", "value" => "technical"],
            ["display_name" => "Humorous Elements", "value" => "humorous"],
        ],
        "Roles & Scenarios" => [
            ["display_name" => "Act as Consultant", "value" => "consultant"],
            ["display_name" => "Act as Developer", "value" => "developer"],
            ["display_name" => "Act as Teacher", "value" => "teacher"],
            ["display_name" => "Act as Analyst", "value" => "analyst"],
            ["display_name" => "Act as Designer", "value" => "designer"],
        ],
        "Additional Options" => [
            ["display_name" => "Contextual Classification", "value" => "contextual"],
            ["display_name" => "Targeted Address", "value" => "targeted"],
        ],
        "Personality" => [
            ["display_name" => "Friendly", "value" => "friendly"],
            ["display_name" => "Professional", "value" => "professional"],
            ["display_name" => "Humorous", "value" => "humorous"],
            ["display_name" => "Sarcastic", "value" => "sarcastic"],
            ["display_name" => "Casual", "value" => "casual"],
        ],
        "Response Style & Interaction Modes" => [
            ["display_name" => "Direct response without introduction", "value" => "direct"],
            ["display_name" => "No standard warnings or lectures", "value" => "noWarnings"],
            ["display_name" => "Ask directly in case of uncertainties", "value" => "ask"],
            ["display_name" => "Start an interactive Q&A", "value" => "interactive"],
            ["display_name" => "Evaluate conversation as soon as 'Stop' is mentioned", "value" => "evaluation"],
        ],
    ];
    $insertStmt = $pdo->prepare("INSERT OR IGNORE INTO options (display_name, value, category) VALUES (:display_name, :value, :category)");
    foreach ($knownOptions as $category => $options) {
        foreach ($options as $opt) {
            $insertStmt->bindParam(":display_name", $opt['display_name']);
            $insertStmt->bindParam(":value", $opt['value']);
            $insertStmt->bindParam(":category", $category);
            $insertStmt->execute();
            echo "Option '" . $opt['display_name'] . "' in category '" . $category . "' inserted (or already exists).<br>";
        }
    }
    echo "<br>Database setup completed. All tables have been created/updated and known options inserted.";
} catch (PDOException $e) {
    die("Error during database setup: " . $e->getMessage());
}
