<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sanction_id = $_POST['sanction_id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    if (empty($sanction_id) || empty($new_status)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        error_log("=== UPDATE_SANCTION_STATUS.PHP ===");
        error_log("Sanction ID: $sanction_id, New Status: $new_status");
        
        // Start transaction
        $conn->begin_transaction();
        
        // Get violation_id from sanction first
        $get_stmt = $conn->prepare("SELECT violation_id FROM sanctions WHERE id = ?");
        $get_stmt->bind_param("i", $sanction_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Sanction not found");
        }
        
        $sanction_data = $result->fetch_assoc();
        $violation_id = $sanction_data['violation_id'];
        
        // Update sanction status
        $update_sanction = $conn->prepare("UPDATE sanctions SET status = ? WHERE id = ?");
        $update_sanction->bind_param("si", $new_status, $sanction_id);
        
        if (!$update_sanction->execute()) {
            throw new Exception("Failed to update sanction: " . $update_sanction->error);
        }
        
        error_log("Sanction status updated to: $new_status");
        
        // Update violation status based on sanction status
        $violation_status = 'pending'; // Default
        
        if ($new_status == 'completed') {
            $violation_status = 'resolved';
        } elseif ($new_status == 'in-progress') {
            $violation_status = 'under_review';
        } else {
            $violation_status = 'pending';
        }
        
        // Update violation status
        $update_violation = $conn->prepare("UPDATE violations SET status = ? WHERE id = ?");
        $update_violation->bind_param("si", $violation_status, $violation_id);
        
        if (!$update_violation->execute()) {
            throw new Exception("Failed to update violation: " . $update_violation->error);
        }
        
        error_log("Violation status updated to: $violation_status");
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sanction status updated successfully',
            'sanction_status' => $new_status,
            'violation_status' => $violation_status
        ]);
        
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("ERROR in update_sanction_status.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>