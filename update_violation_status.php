<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $violation_id = $_POST['violation_id'] ?? null;
    $status = $_POST['status'] ?? '';

    if (empty($violation_id) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE violations SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $violation_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Violation status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>