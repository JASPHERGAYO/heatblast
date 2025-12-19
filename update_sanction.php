<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $sanction_id = $_POST['sanction_id'] ?? null;
    $sanction_type = $_POST['sanction_type'] ?? '';
    $status = $_POST['status'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $counselor_notes = $_POST['counselor_notes'] ?? '';

    if (empty($sanction_id) || empty($sanction_type) || empty($status) || empty($due_date)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, counselor_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssi", $sanction_type, $status, $due_date, $counselor_notes, $sanction_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sanction updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>