<?php
session_start();
require_once 'database.php';
require_once 'admin_function.php';

header('Content-Type: application/json');

// Add detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $violation_id = $_POST['sanctionViolationId'] ?? '';
    $student_number = $_POST['sanctionStudentNumber'] ?? '';
    $sanction_type = $_POST['sanctionType'] ?? '';
    $due_date = $_POST['sanctionDueDate'] ?? '';
    $notes = $_POST['sanctionNotes'] ?? '';
    
    // Handle file upload for violation proof
    $proof_filename = null;
    $proof_db_path = null;
    if (isset($_FILES['violationProof']) && $_FILES['violationProof']['error'] === UPLOAD_ERR_OK) {
        $proof_tmp = $_FILES['violationProof']['tmp_name'];
        $proof_name = $_FILES['violationProof']['name'];
        $proof_size = $_FILES['violationProof']['size'];
        $proof_ext = strtolower(pathinfo($proof_name, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        if (!in_array($proof_ext, $allowed_ext)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, PDF, DOC, DOCX allowed']);
            exit;
        }
        
        // Validate file size (5MB max)
        if ($proof_size > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
            exit;
        }
        
        $proof_filename = 'violation_proof_' . time() . '_' . uniqid() . '.' . $proof_ext;
        $upload_path = 'uploads/violation_proofs/' . $proof_filename;
        // DB should store the relative path so retrieval and display work consistently
        $proof_db_path = $upload_path;
        
        // Create directory if it doesn't exist
        if (!file_exists('uploads/violation_proofs')) {
            mkdir('uploads/violation_proofs', 0777, true);
        }
        
        if (!move_uploaded_file($proof_tmp, $upload_path)) {
            error_log("Failed to upload proof file: " . $proof_name . " tmp: " . $proof_tmp . " upload_path: " . $upload_path);
            $proof_filename = null;
            $proof_db_path = null;
        } else {
            error_log("Uploaded violation proof: " . $upload_path . " (original: " . $proof_name . ")");
        }
    }

    // Validate required fields
    if (empty($student_number) || empty($sanction_type) || empty($due_date)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }

    // Validate sanction type (removed mandatory_counseling and extended_community_service)
    $valid_minor_sanctions = ['verbal_reprimand', 'written_warning_1', 'written_warning_2', 'written_warning_3'];
    $valid_major_sanctions = ['suspension_6_days', 'suspension_10_20_days', 'non_readmission', 'dismissal', 'expulsion'];
    
    if (!in_array($sanction_type, array_merge($valid_minor_sanctions, $valid_major_sanctions))) {
        echo json_encode(['success' => false, 'message' => 'Invalid sanction type selected']);
        exit;
    }

    // Convert date format from dd/mm/yyyy to Y-m-d
    $date_parts = explode('/', $due_date);
    if (count($date_parts) === 3) {
        $day = $date_parts[0];
        $month = $date_parts[1];
        $year = $date_parts[2];
        
        // Handle 2-digit year
        if (strlen($year) === 2) {
            $year = '20' . $year; // Assuming 20xx years
        }
        
        // Validate date
        if (checkdate($month, $day, $year)) {
            $mysql_due_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid date format. Use dd/mm/yyyy']);
            exit;
        }
    } else {
        // Try to parse as Y-m-d format (if coming from HTML5 date input)
        if (strtotime($due_date)) {
            $mysql_due_date = date('Y-m-d', strtotime($due_date));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid date format. Use dd/mm/yyyy']);
            exit;
        }
    }

    // Validate year restriction (2025-2026 only)
    $due_year = date('Y', strtotime($mysql_due_date));
    if ($due_year < 2025 || $due_year > 2026) {
        echo json_encode(['success' => false, 'message' => 'Only years 2025-2026 are allowed for due date']);
        exit;
    }

    // Debug: Check what we received
    error_log("Received data: student_number=$student_number, sanction_type=$sanction_type, original_due_date=$due_date, mysql_due_date=$mysql_due_date, proof_filename=" . ($proof_filename ?? 'none'));

    // Get student ID from student number
    $sql1 = "SELECT user_id FROM student_profiles WHERE student_number = ?";
    $stmt = $conn->prepare($sql1);
    if (!$stmt) {
        throw new Exception("Prepare failed for student query: " . $conn->error . " | SQL: " . $sql1);
    }
    
    $stmt->bind_param("s", $student_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    $student = $result->fetch_assoc();
    $user_id = $student['user_id'];

    // If no violation_id provided, get the latest violation for this student
    if (empty($violation_id)) {
        $sql2 = "SELECT id FROM violations WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql2);
        if (!$stmt) {
            throw new Exception("Prepare failed for violation query: " . $conn->error . " | SQL: " . $sql2);
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $violation = $result->fetch_assoc();
            $violation_id = $violation['id'];
        } else {
            echo json_encode(['success' => false, 'message' => 'No pending violations found for this student']);
            exit;
        }
    }

    // Update violation with proof filename BEFORE assigning sanction
    $upload_success = false;
    $db_update_success = false;
    $db_affected_rows = 0;
    $column_exists = false;
    if ($proof_db_path) {
        // Check if column exists
        $col_check = $conn->query("SHOW COLUMNS FROM violations LIKE 'proof_filename'");
        if ($col_check && $col_check->num_rows > 0) {
            $column_exists = true;
        }
        $upload_success = true;
        if ($column_exists) {
            $update_proof_sql = "UPDATE violations SET proof_filename = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_proof_sql);
            if ($update_stmt) {
                $update_stmt->bind_param("si", $proof_db_path, $violation_id);
                if ($update_stmt->execute()) {
                    error_log("Violations table updated with proof: " . $proof_db_path . " for violation_id: " . $violation_id);
                    $db_update_success = true;
                    $db_affected_rows = $update_stmt->affected_rows;
                } else {
                    error_log("Failed to update violations.proof_filename: " . $update_stmt->error);
                }
            } else {
                error_log("Prepare failed for updating proof_filename: " . $conn->error . " | SQL: " . $update_proof_sql);
            }
        } else {
            error_log("Column proof_filename does not exist in violations table. Skipping DB update.");
        }
    }

    // Use the function to assign sanction (notes will be saved as resolution_notes in violations table)
    if (assignSanction($violation_id, $sanction_type, $mysql_due_date, $notes, $conn)) {
        $response = ['success' => true, 'message' => 'Sanction assigned successfully'];
        // Add debug info
        $response['debug'] = [
            'file_uploaded' => $upload_success,
            'file_path' => $proof_db_path,
            'column_exists' => $column_exists,
            'db_update_success' => $db_update_success,
            'db_update_affected_rows' => $db_affected_rows,
            'notes_saved_to' => 'violations.resolution_notes'
        ];
        echo json_encode($response);
    } else {
        throw new Exception("Failed to assign sanction using function");
    }
    
} catch (Exception $e) {
    error_log("Sanction assignment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>