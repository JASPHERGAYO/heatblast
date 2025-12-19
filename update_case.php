<?php
session_start();
require_once 'database.php';

// Set error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id = $_POST['case_id'] ?? '';
    $violation_type = $_POST['violation_type'] ?? '';
    $violation_category = $_POST['violation_category'] ?? '';
    $status = $_POST['status'] ?? '';
    $description = $_POST['description'] ?? '';
    $resolution_notes = $_POST['resolution_notes'] ?? '';
    $created_at = $_POST['created_at'] ?? '';
    
    // Sanction-related fields
    $sanction_id = $_POST['sanction_id'] ?? '';
    $sanction_type = $_POST['sanction_type'] ?? '';
    $sanction_status = $_POST['sanction_status'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $completion_date = $_POST['completion_date'] ?? '';
    $hours_completed = $_POST['hours_completed'] ?? '';
    $counselor_notes = $_POST['counselor_notes'] ?? '';
    
    if (empty($case_id) || empty($violation_type) || empty($violation_category) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        error_log("=== UPDATE_CASE.PHP START ===");
        error_log("Case ID: $case_id, Status: $status");
        
        // Start transaction
        $conn->begin_transaction();
        
        // IMPORTANT FIX: Map status for violations table
        // Violations table uses 'under_review' but we display as 'in-progress'
        $violation_status = $status;
        if ($status == 'in-progress') {
            $violation_status = 'under_review';
        }
        
        // FORCE CHECK: If status is 'pending', ensure there's a sanction
        if ($status == 'pending') {
            error_log("Status is pending, checking for sanction...");
            
            // Check if sanction exists for this violation
            $sanction_check = $conn->prepare("SELECT id FROM sanctions WHERE violation_id = ?");
            if (!$sanction_check) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $sanction_check->bind_param("i", $case_id);
            $sanction_check->execute();
            $sanction_result = $sanction_check->get_result();
            
            $has_existing_sanction = ($sanction_result->num_rows > 0);
            error_log("Existing sanction found: " . ($has_existing_sanction ? 'YES' : 'NO'));
            
            // If we're trying to set status to pending without a sanction, REJECT
            if (!$has_existing_sanction && empty($sanction_type)) {
                error_log("ERROR: Cannot set to pending without sanction!");
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Cannot set case status to pending without assigning a sanction. Please select a sanction type.']);
                exit;
            }
        }
        
        // Update timestamps
        $current_timestamp = date('Y-m-d H:i:s');
        $formatted_created_at = !empty($created_at) ? date('Y-m-d H:i:s', strtotime($created_at)) : $current_timestamp;
        
        // FIXED: Update violations table with proper status mapping
        $update_violation_sql = "UPDATE violations SET 
            violation_type = ?, 
            violation_category = ?, 
            description = ?, 
            status = ?, 
            resolution_notes = ?, 
            created_at = ? 
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_violation_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for violation update: " . $conn->error);
        }
        
        $stmt->bind_param(
            "ssssssi", 
            $violation_type, 
            $violation_category, 
            $description, 
            $violation_status, // Use mapped status
            $resolution_notes, 
            $formatted_created_at, 
            $case_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update violation: " . $stmt->error);
        }
        
        error_log("Violation updated with status: $violation_status (displayed as: $status)");
        
        // Handle sanction updates if provided
        if (!empty($sanction_type)) {
            error_log("Processing sanction update...");
            
            // Format dates
            $formatted_due_date = !empty($due_date) ? date('Y-m-d', strtotime($due_date)) : null;
            $formatted_completion_date = !empty($completion_date) ? date('Y-m-d', strtotime($completion_date)) : null;
            $hours_float = !empty($hours_completed) ? floatval($hours_completed) : 0.00;
            
            // Check if sanction exists
            if (!empty($sanction_id)) {
                // Update existing sanction
                error_log("Updating existing sanction ID: $sanction_id");
                
                $update_sanction_sql = "UPDATE sanctions SET 
                    sanction_type = ?, 
                    status = ?, 
                    due_date = ?, 
                    completion_date = ?, 
                    hours_completed = ?, 
                    counselor_notes = ? 
                    WHERE id = ?";
                
                $sanction_stmt = $conn->prepare($update_sanction_sql);
                if (!$sanction_stmt) {
                    throw new Exception("Prepare failed for sanction update: " . $conn->error);
                }
                
                $sanction_stmt->bind_param(
                    "ssssdsi",
                    $sanction_type,
                    $sanction_status,
                    $formatted_due_date,
                    $formatted_completion_date,
                    $hours_float,
                    $counselor_notes,
                    $sanction_id
                );
                
                if (!$sanction_stmt->execute()) {
                    throw new Exception("Failed to update sanction: " . $sanction_stmt->error);
                }
                
                error_log("Sanction updated successfully");
                
                // IMPORTANT: If sanction is completed, automatically resolve the violation
                if ($sanction_status == 'completed') {
                    error_log("Sanction completed, auto-resolving violation...");
                    $resolve_stmt = $conn->prepare("UPDATE violations SET status = 'resolved' WHERE id = ?");
                    $resolve_stmt->bind_param("i", $case_id);
                    $resolve_stmt->execute();
                    error_log("Violation auto-resolved to 'resolved'");
                }
                
            } else {
                // Insert new sanction
                error_log("Inserting new sanction for violation: $case_id");
                
                // First get user_id from violation
                $user_stmt = $conn->prepare("SELECT user_id FROM violations WHERE id = ?");
                $user_stmt->bind_param("i", $case_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $violation_data = $user_result->fetch_assoc();
                $user_id = $violation_data['user_id'];
                
                $insert_sanction_sql = "INSERT INTO sanctions (
                    violation_id, 
                    user_id, 
                    sanction_type, 
                    status, 
                    due_date, 
                    completion_date, 
                    hours_completed, 
                    counselor_notes, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $sanction_stmt = $conn->prepare($insert_sanction_sql);
                if (!$sanction_stmt) {
                    throw new Exception("Prepare failed for sanction insert: " . $conn->error);
                }
                
                $sanction_stmt->bind_param(
                    "iissssdss",
                    $case_id,
                    $user_id,
                    $sanction_type,
                    $sanction_status,
                    $formatted_due_date,
                    $formatted_completion_date,
                    $hours_float,
                    $counselor_notes,
                    $current_timestamp
                );
                
                if (!$sanction_stmt->execute()) {
                    throw new Exception("Failed to insert sanction: " . $sanction_stmt->error);
                }
                
                error_log("New sanction inserted successfully");
                
                // If new sanction is not pending, update violation status
                if ($sanction_status != 'pending') {
                    $new_violation_status = ($sanction_status == 'completed') ? 'resolved' : 'under_review';
                    $update_viol_stmt = $conn->prepare("UPDATE violations SET status = ? WHERE id = ?");
                    $update_viol_stmt->bind_param("si", $new_violation_status, $case_id);
                    $update_viol_stmt->execute();
                    error_log("Violation status updated to: $new_violation_status");
                }
            }
        }
        
        $conn->commit();
        
        error_log("=== UPDATE_CASE.PHP SUCCESS ===");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Case updated successfully',
            'status' => $status,
            'sanction_status' => $sanction_status ?? 'none'
        ]);
        
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("ERROR in update_case.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>