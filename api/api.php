<?php
// api/api.php - Optimized ID handling for Postgres
error_reporting(0);
require_once 'db.php';

header('Content-Type: application/json');

// Check if we have a critical DB error but ONLY if we aren't using JSON fallback
if (isset($db_error) && $db_error !== null && $use_json === false) {
    echo json_encode(['success' => false, 'message' => "Database Connection Error: " . $db_error]);
    exit;
}

$action = $_GET['action'] ?? '';

function generateRandomKey($length = 11) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) { $randomString .= $characters[rand(0, $charactersLength - 1)]; }
    return 'cabbtral-beta-' . $randomString;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'generate') {
        $newKey = generateRandomKey();
        try {
            if ($use_json) {
                $keys = getJsonKeys();
                $keyData = ['id' => time(), 'key_value' => $newKey, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')];
                $keys[] = $keyData;
                saveJsonKeys($keys);
            } else {
                $stmt = $pdo->prepare("INSERT INTO keys (key_value) VALUES (?) RETURNING id");
                $stmt->execute([$newKey]);
                $id = $stmt->fetchColumn();
                if (!$id) $id = $pdo->lastInsertId();
                $keyData = ['id' => $id, 'key_value' => $newKey, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')];
            }
            echo json_encode(['success' => true, 'key' => $keyData]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'toggle_status') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($use_json) {
            $keys = getJsonKeys();
            $newStatus = 'active';
            foreach ($keys as &$k) { if ($k['id'] == $id) { $k['status'] = ($k['status'] === 'active') ? 'inactive' : 'active'; $newStatus = $k['status']; break; } }
            saveJsonKeys($keys);
            echo json_encode(['success' => true, 'new_status' => $newStatus]);
        } else {
            $pdo->prepare("UPDATE keys SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?")->execute([$id]);
            $stmt = $pdo->prepare("SELECT status FROM keys WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'new_status' => $stmt->fetchColumn()]);
        }
        exit;
    }

    if ($action === 'delete') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($use_json) {
            $keys = array_filter(getJsonKeys(), fn($k) => $k['id'] != $id);
            saveJsonKeys($keys);
        } else {
            $pdo->prepare("DELETE FROM keys WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    try {
        if ($use_json) {
            echo json_encode(array_reverse(getJsonKeys()));
        } else {
            $stmt = $pdo->query("SELECT * FROM keys ORDER BY created_at DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "List error: " . $e->getMessage()]);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid Request']);
