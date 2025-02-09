<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';
error_log("prefab_actions.php started.");
$action = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents("php://input"), true);
error_log("Received data: " . print_r($data, true));
try {
    if ($action === 'save') {
        error_log("Action: save");
        if (!isset($data['name']) || !isset($data['options'])) {
            echo json_encode(["success" => false, "message" => "Invalid data."]);
            exit;
        }
        $name = $data['name'];
        $parent_id = isset($data['parent_id']) && !empty($data['parent_id']) ? $data['parent_id'] : null;
        $stmt = $pdo->prepare("INSERT INTO prefabs (name, parent_id) VALUES (:name, :parent_id)");
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":parent_id", $parent_id);
        if ($stmt->execute()) {
            $prefabId = $pdo->lastInsertId();
            error_log("Prefab saved with ID: " . $prefabId);
            if (isset($data['options']) && is_array($data['options'])) {
                $insertStmt = $pdo->prepare("INSERT OR IGNORE INTO prefab_options (prefab_id, option_id) VALUES (:prefab_id, :option_id)");
                foreach ($data['options'] as $optId) {
                    $insertStmt->bindParam(":prefab_id", $prefabId);
                    $insertStmt->bindParam(":option_id", $optId);
                    $insertStmt->execute();
                    error_log("Option with ID " . $optId . " inserted for prefab " . $prefabId . ".");
                }
            }
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Error saving prefab."]);
        }
    } elseif ($action === 'update') {
        error_log("Action: update");
        if (!isset($data['id']) || !isset($data['options'])) {
            echo json_encode(["success" => false, "message" => "Invalid data."]);
            exit;
        }
        $id = $data['id'];
        $name = isset($data['name']) ? $data['name'] : null;
        $parent_id = isset($data['parent_id']) && !empty($data['parent_id']) ? $data['parent_id'] : null;
        if ($name !== null) {
            $updateStmt = $pdo->prepare("UPDATE prefabs SET name = :name, parent_id = :parent_id WHERE id = :id");
            $updateStmt->bindParam(":name", $name);
            $updateStmt->bindParam(":parent_id", $parent_id);
            $updateStmt->bindParam(":id", $id);
            $updateStmt->execute();
            error_log("Prefab ID " . $id . " updated (name and parent).");
        }
        $deleteStmt = $pdo->prepare("DELETE FROM prefab_options WHERE prefab_id = :id");
        $deleteStmt->bindParam(":id", $id);
        $deleteStmt->execute();
        error_log("Old options for prefab ID " . $id . " deleted.");
        if (isset($data['options']) && is_array($data['options'])) {
            $insertStmt = $pdo->prepare("INSERT OR IGNORE INTO prefab_options (prefab_id, option_id) VALUES (:prefab_id, :option_id)");
            foreach ($data['options'] as $optId) {
                $insertStmt->bindParam(":prefab_id", $id);
                $insertStmt->bindParam(":option_id", $optId);
                $insertStmt->execute();
                error_log("Option with ID " . $optId . " inserted for prefab " . $id . ".");
            }
        }
        echo json_encode(["success" => true]);
    } elseif ($action === 'delete') {
        error_log("Action: delete");
        if (!isset($data['id'])) {
            echo json_encode(["success" => false, "message" => "Invalid data."]);
            exit;
        }
        $id = $data['id'];
        $deleteJoin = $pdo->prepare("DELETE FROM prefab_options WHERE prefab_id = :id");
        $deleteJoin->bindParam(":id", $id);
        $deleteJoin->execute();
        error_log("Options for prefab ID " . $id . " deleted.");
        $stmt = $pdo->prepare("DELETE FROM prefabs WHERE id = :id");
        $stmt->bindParam(":id", $id);
        if ($stmt->execute()) {
            error_log("Prefab ID " . $id . " deleted.");
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Error deleting prefab."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid action."]);
    }
} catch (PDOException $e) {
    error_log("Error in prefab_actions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
$output = ob_get_clean();
$output = preg_replace('/^\xEF\xBB\xBF/', '', $output);
echo $output;
