<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$violation_id = $_GET['violation_id'] ?? '';

if (empty($violation_id)) {
    echo json_encode(['success' => false, 'message' => 'No violation ID provided']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id FROM sanctions WHERE violation_id = ?");
    $stmt->bind_param("i", $violation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sanction = $result->fetch_assoc();
        echo json_encode(['success' => true, 'sanction_id' => $sanction['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No sanction found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>