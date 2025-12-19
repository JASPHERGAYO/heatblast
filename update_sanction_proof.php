<?php
session_start();
require_once 'database.php';
require_once 'email_config.php'; // Make sure this file exists with PHPMailer setup

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
    $sanction_id = $_POST['proofSanctionId'] ?? '';
    $completion_date = $_POST['completionDate'] ?? '';
    $counselor_notes = $_POST['counselorNotes'] ?? '';
    $hours_completed = $_POST['hoursCompleted'] ?? '';

    if (empty($sanction_id) || empty($completion_date)) {
        echo json_encode(['success' => false, 'message' => 'Sanction ID and Completion Date are required']);
        exit;
    }

    // ========== PORTABLE FILE UPLOAD ==========
    $proof_filename = null;
    if (isset($_FILES['completionProof']) && $_FILES['completionProof']['error'] === UPLOAD_ERR_OK) {
        // Define upload directory (relative to current script)
        $upload_dir = 'uploads/sanction_proofs/';
        
        // Create the directory if it doesn't exist
        $full_upload_dir = __DIR__ . '/' . $upload_dir;
        if (!is_dir($full_upload_dir)) {
            mkdir($full_upload_dir, 0777, true);
        }
        
        // Validate file
        $file_name = $_FILES['completionProof']['name'];
        $file_tmp = $_FILES['completionProof']['tmp_name'];
        $file_size = $_FILES['completionProof']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_ext)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, PDF, DOC, DOCX allowed']);
            exit;
        }
        
        // Max 5MB file size
        if ($file_size > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
            exit;
        }
        
        // Generate unique filename
        $unique_name = 'sanction_' . $sanction_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
        $upload_path = $full_upload_dir . $unique_name;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // ✅ Store ONLY the relative path from the current directory
            $proof_filename = $upload_dir . $unique_name; // e.g., "uploads/sanction_proofs/sanction_37_timestamp_hash.jpg"
        } else {
            error_log("Failed to move uploaded file from $file_tmp to $upload_path");
            echo json_encode(['success' => false, 'message' => 'Failed to upload file. Check server permissions.']);
            exit;
        }
    }

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update sanction in database
        $sql = "UPDATE sanctions SET 
                status = 'completed', 
                completion_date = ?, 
                completion_proof = ?, 
                counselor_notes = ?,
                hours_completed = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for sanction update: " . $conn->error);
        }
        
        $db_proof_filename = !empty($proof_filename) ? $proof_filename : null;
        $stmt->bind_param("ssssi", $completion_date, $db_proof_filename, $counselor_notes, $hours_completed, $sanction_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed for sanction update: " . $stmt->error);
        }
        
        // ✅ CRITICAL FIX: Get violation_id and student info
        $get_info_stmt = $conn->prepare("
            SELECT s.violation_id, s.sanction_type, s.due_date,
                   v.violation_type, v.violation_category, v.description, v.created_at,
                   u.email, sp.surname, sp.firstname
            FROM sanctions s
            JOIN violations v ON s.violation_id = v.id
            JOIN users u ON v.user_id = u.id
            JOIN student_profiles sp ON u.id = sp.user_id
            WHERE s.id = ?
        ");
        $get_info_stmt->bind_param("i", $sanction_id);
        $get_info_stmt->execute();
        $info_result = $get_info_stmt->get_result();
        
        if ($info_result->num_rows === 0) {
            throw new Exception("Sanction not found with ID: $sanction_id");
        }
        
        $sanction_info = $info_result->fetch_assoc();
        $violation_id = $sanction_info['violation_id'];
        $student_email = $sanction_info['email'];
        $student_name = $sanction_info['surname'] . ', ' . $sanction_info['firstname'];
        $sanction_type = $sanction_info['sanction_type'];
        $violation_type = $sanction_info['violation_type'];
        $violation_category = $sanction_info['violation_category'];
        $violation_description = $sanction_info['description'];
        $violation_date = $sanction_info['created_at'];
        $due_date = $sanction_info['due_date'];
        
        // ✅ FIXED: Update violation status WITHOUT resolved_at column
        $update_violation_sql = "UPDATE violations SET status = 'resolved' WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_violation_sql);
        if (!$update_stmt) {
            throw new Exception("Prepare failed for violation update: " . $conn->error);
        }
        
        $update_stmt->bind_param("i", $violation_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Execute failed for violation update: " . $update_stmt->error);
        }
        
        // ✅ Verify the update worked
        $check_stmt = $conn->prepare("SELECT status FROM violations WHERE id = ?");
        $check_stmt->bind_param("i", $violation_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $updated_violation = $check_result->fetch_assoc();
        
        if ($updated_violation['status'] !== 'resolved') {
            throw new Exception("Violation status not updated to 'resolved'. Current status: " . $updated_violation['status']);
        }
        
        // Commit transaction
        $conn->commit();
        
        // ========== SEND EMAIL TO STUDENT ==========
        $email_sent = false;
        $email_error = '';
        
        if (function_exists('sendSanctionCompletedEmail')) {
            try {
                $recorded_by_name = 'Administrator'; // Get from session if needed
                
                $email_sent = sendSanctionCompletedEmail(
                    $student_email,
                    $student_name,
                    $violation_type,
                    $violation_description,
                    $violation_category,
                    $sanction_type,
                    $violation_date,
                    $completion_date,
                    $counselor_notes,
                    $recorded_by_name,
                    $due_date,
                    $hours_completed
                );
                
                if ($email_sent) {
                    error_log("SUCCESS: Sanction completion email sent to $student_email");
                } else {
                    error_log("WARNING: Sanction completed but email failed to send to $student_email");
                    $email_error = "Email notification failed to send.";
                }
            } catch (Exception $e) {
                $email_error = "Email error: " . $e->getMessage();
                error_log("ERROR sending sanction completion email: " . $e->getMessage());
            }
        } else {
            error_log("WARNING: sendSanctionCompletedEmail function not found in email_config.php");
            $email_error = "Email function not available.";
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sanction completed successfully! Violation has been resolved.' . ($email_error ? ' ' . $email_error : ''),
            'sanction_id' => $sanction_id,
            'violation_id' => $violation_id,
            'violation_status' => $updated_violation['status'],
            'email_sent' => $email_sent,
            'email_error' => $email_error,
            'file_path' => $proof_filename
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Proof upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>