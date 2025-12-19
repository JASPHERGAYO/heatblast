<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $case_id = $_POST['case_id'] ?? null;
    $violation_type = $_POST['violation_type'] ?? '';
    $violation_category = $_POST['violation_category'] ?? '';
    $status = $_POST['status'] ?? '';
    $violation_description = $_POST['violation_description'] ?? '';

    if (empty($case_id) || empty($violation_type) || empty($violation_category) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE violations SET violation_type = ?, violation_category = ?, status = ?, violation_description = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssi", $violation_type, $violation_category, $status, $violation_description, $case_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Case updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>