<?php
// admin_function.php

function getTotalViolations($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM violations");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}

function getPendingCases($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM violations WHERE status = 'pending'");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}

function getTotalStudents($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_profiles");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}

// Add this function to your admin_function.php file
function getCompletedSanctions($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sanctions WHERE status = 'completed'");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}


function getRecentViolations($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT v.*, sp.firstname, sp.surname, sp.course, sp.section, sp.student_number 
            FROM violations v 
            JOIN student_profiles sp ON v.user_id = sp.user_id 
            WHERE v.status = 'pending'
            ORDER BY 
    CASE 
        WHEN v.status = 'pending' THEN 1
        WHEN v.status = 'in-progress' THEN 2
        ELSE 3
    END,
    v.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function getAllStudents($conn) {
    $sql = "SELECT 
        sp.*, 
        u.email,
        -- Count only non-resolved violations as active violations
        (SELECT COUNT(*) FROM violations v WHERE v.user_id = sp.user_id AND v.status != 'resolved') as violation_count
    FROM student_profiles sp
    JOIN users u ON sp.user_id = u.id
    ORDER BY sp.surname, sp.firstname";
    
    return $conn->query($sql);
}

// In admin_function.php, update the getAllCases function:

function getAllCases($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                v.*, 
                sp.firstname, sp.surname, sp.course, sp.section, sp.student_number,
                CASE 
                    WHEN v.user_type = 'admin' THEN 'Administrator'
                    WHEN v.user_type = 'staff' THEN s.fullname
                    ELSE 'Unknown'
                END as recorded_by_name,
                sanc.status as sanction_status,
                sanc.id as sanction_id
            FROM violations v 
            JOIN student_profiles sp ON v.user_id = sp.user_id 
            LEFT JOIN staff s ON v.recorded_by = s.id AND v.user_type = 'staff'
            LEFT JOIN sanctions sanc ON v.id = sanc.violation_id
            ORDER BY 
                CASE 
                    WHEN v.status = 'pending' THEN 1
                    WHEN sanc.status = 'in-progress' THEN 2
                    WHEN v.status = 'under_review' THEN 3
                    WHEN sanc.status = 'completed' THEN 4
                    ELSE 5
                END,
                v.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function getViolationCounts($user_id, $conn) {
    try {
        // FIXED: Count minor violations - PENDING AND UNDER_REVIEW
        $minor_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'minor' AND status IN ('pending', 'under_review')");
        $minor_q->bind_param("i", $user_id);
        $minor_q->execute();
        $minor_result = $minor_q->get_result();
        $minor_total = $minor_result->fetch_assoc()['total'];

        // FIXED: Count major violations - PENDING AND UNDER_REVIEW
        $major_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'major' AND status IN ('pending', 'under_review')");
        $major_q->bind_param("i", $user_id);
        $major_q->execute();
        $major_result = $major_q->get_result();
        $major_total = $major_result->fetch_assoc()['total'];

        // Calculate conversions (every 4 minors = 1 major)
        $converted_majors = floor($minor_total / 4);
        $effective_major_total = $major_total + $converted_majors;
        $remaining_minors = $minor_total % 4;

        return [
            'minor_total' => $minor_total,
            'major_total' => $major_total,
            'converted_majors' => $converted_majors,
            'effective_major_total' => $effective_major_total,
            'remaining_minors' => $remaining_minors,
            'total_violations' => $minor_total + $major_total
        ];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return [
            'minor_total' => 0,
            'major_total' => 0,
            'converted_majors' => 0,
            'effective_major_total' => 0,
            'remaining_minors' => 0,
            'total_violations' => 0
        ];
    }
}

function getStudentSanctions($user_id, $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT s.*, v.violation_type, v.violation_category 
            FROM sanctions s 
            JOIN violations v ON s.violation_id = v.id 
            WHERE v.user_id = ? 
            ORDER BY s.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function getStudentData($student_id, $conn) {
    try {
        // Try to find by student_number first
        $q = $conn->prepare("
            SELECT sp.*, u.email 
            FROM student_profiles sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE sp.student_number = ? 
            LIMIT 1
        ");
        $q->bind_param("s", $student_id);
        $q->execute();
        $result = $q->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // If not found by student_number, try by user_id
        $q = $conn->prepare("
            SELECT sp.*, u.email 
            FROM student_profiles sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE sp.user_id = ? 
            LIMIT 1
        ");
        $q->bind_param("i", $student_id);
        $q->execute();
        $result = $q->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function getRecentViolation($user_id, $conn) {
    try {
        $viol_q = $conn->prepare("
            SELECT violation_type, created_at, violation_category
            FROM violations 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $viol_q->bind_param("i", $user_id);
        $viol_q->execute();
        $result = $viol_q->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function getAllSanctions($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT s.*, v.violation_type, v.violation_category, 
                   sp.firstname, sp.surname, sp.student_number, sp.course, sp.section
            FROM sanctions s 
            JOIN violations v ON s.violation_id = v.id 
            JOIN student_profiles sp ON v.user_id = sp.user_id 
            ORDER BY s.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function violationHasSanction($violation_id, $conn) {
    try {
        $stmt = $conn->prepare("SELECT id FROM sanctions WHERE violation_id = ?");
        $stmt->bind_param("i", $violation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function getCaseDetails($case_id, $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT v.*, sp.firstname, sp.surname, sp.student_number, sp.course, sp.section,
                   CASE 
                       WHEN v.user_type = 'admin' THEN 'Administrator'
                       WHEN v.user_type = 'staff' THEN s.fullname
                       ELSE 'Unknown'
                   END as recorded_by_name,
                   sanc.sanction_type, sanc.status as sanction_status, sanc.due_date, 
                   sanc.completion_date, sanc.completion_proof
            FROM violations v 
            JOIN student_profiles sp ON v.user_id = sp.user_id 
            LEFT JOIN staff s ON v.recorded_by = s.id AND v.user_type = 'staff'
            LEFT JOIN sanctions sanc ON v.id = sanc.violation_id
            WHERE v.id = ?
        ");
        $stmt->bind_param("i", $case_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

function getStudentViolationHistory($user_id, $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT v.*, 
                   s.sanction_type, s.status as sanction_status, s.due_date, s.completion_date
            FROM violations v 
            LEFT JOIN sanctions s ON v.id = s.violation_id
            WHERE v.user_id = ? 
            ORDER BY v.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

// Helper function to check if student has pending violations
function hasPendingViolations($user_id, $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT v.id 
            FROM violations v 
            LEFT JOIN sanctions s ON v.id = s.violation_id 
            WHERE v.user_id = ? AND v.status = 'pending' AND s.id IS NULL
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Helper function to count pending sanctions for a student
function countPendingSanctions($user_id, $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as pending_count 
            FROM sanctions s 
            JOIN violations v ON s.violation_id = v.id 
            WHERE v.user_id = ? AND s.status != 'completed'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['pending_count'];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}

// NEW FUNCTION: Assign sanction to violation
// Add this function to your admin_function.php
// NEW FUNCTION: Assign sanction to violation (UPDATED VERSION)
// CORRECTED FUNCTION: Assign sanction to violation
function assignSanction($violation_id, $sanction_type, $due_date, $resolution_notes, $conn) {
    error_log("=== assignSanction FUNCTION START ===");
    error_log("Parameters: violation_id=$violation_id, sanction_type=$sanction_type, due_date=$due_date");
    
    try {
        // Start transaction
        $conn->begin_transaction();
        error_log("Transaction started");

        // Get user_id from violation
        $user_stmt = $conn->prepare("SELECT user_id FROM violations WHERE id = ?");
        if (!$user_stmt) {
            error_log("Prepare failed for user query: " . $conn->error);
            throw new Exception("Prepare failed for user query: " . $conn->error);
        }
        
        $user_stmt->bind_param("i", $violation_id);
        if (!$user_stmt->execute()) {
            error_log("Execute failed for user query: " . $user_stmt->error);
            throw new Exception("Execute failed for user query: " . $user_stmt->error);
        }
        
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            error_log("Violation not found with id: $violation_id");
            throw new Exception("Violation not found");
        }
        
        $violation_data = $user_result->fetch_assoc();
        $user_id = $violation_data['user_id'];
        error_log("Found user_id: $user_id");

        // FIRST: Update the violation with resolution_notes
        error_log("Updating violations table with resolution_notes for violation_id: $violation_id");
        $update_violation_stmt = $conn->prepare("UPDATE violations SET resolution_notes = ? WHERE id = ?");
        
        if (!$update_violation_stmt) {
            error_log("Prepare failed for violation update: " . $conn->error);
            throw new Exception("Prepare failed for violation update: " . $conn->error);
        }

        $update_violation_stmt->bind_param("si", $resolution_notes, $violation_id);
        
        if (!$update_violation_stmt->execute()) {
            error_log("Execute failed for violation update: " . $update_violation_stmt->error);
            throw new Exception("Execute failed for violation update: " . $update_violation_stmt->error);
        }
        
        error_log("Violation updated with resolution_notes successfully");

        // Check if sanction already exists for this violation
        $check_stmt = $conn->prepare("SELECT id FROM sanctions WHERE violation_id = ?");
        if (!$check_stmt) {
            error_log("Prepare failed for check query: " . $conn->error);
            throw new Exception("Prepare failed for check query: " . $conn->error);
        }
        
        $check_stmt->bind_param("i", $violation_id);
        if (!$check_stmt->execute()) {
            error_log("Execute failed for check query: " . $check_stmt->error);
            throw new Exception("Execute failed for check query: " . $check_stmt->error);
        }
        
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing = $check_result->fetch_assoc();
            error_log("Sanction already exists for violation ID: $violation_id, sanction_id: " . $existing['id']);
            throw new Exception("A sanction already exists for this violation");
        }
        
        error_log("No existing sanction found, proceeding...");

        // Insert the new sanction WITHOUT counselor_notes
        $insert_stmt = $conn->prepare("
            INSERT INTO sanctions (violation_id, user_id, sanction_type, status, due_date, created_at) 
            VALUES (?, ?, ?, 'pending', ?, NOW())
        ");
        
        if (!$insert_stmt) {
            error_log("Prepare failed for sanction insert: " . $conn->error);
            throw new Exception("Prepare failed for sanction insert: " . $conn->error);
        }

        $insert_stmt->bind_param("iiss", $violation_id, $user_id, $sanction_type, $due_date);
        
        if (!$insert_stmt->execute()) {
            error_log("Execute failed for sanction insert: " . $insert_stmt->error);
            throw new Exception("Execute failed for sanction insert: " . $insert_stmt->error);
        }
        
        $sanction_id = $conn->insert_id;
        error_log("Sanction inserted successfully! ID: $sanction_id");

        // NOTE: Previously we automatically updated the violation status to 'under_review' here when assigning a sanction.
        // That caused violations to move from 'pending' to 'under_review' immediately upon sanction creation.
        // We now defer violation status changes to when the sanction status itself changes (handled in update_sanction_status.php).
        error_log("Sanction assigned; violation status update is deferred until sanction status changes.");

        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully");
        error_log("=== assignSanction FUNCTION END (SUCCESS) ===");
        
        return true;

    } catch (Exception $e) {
        // Rollback on error
        if ($conn) {
            $conn->rollback();
            error_log("Transaction rolled back due to error");
        }
        
        error_log("assignSanction function error: " . $e->getMessage());
        error_log("=== assignSanction FUNCTION END (ERROR) ===");
        return false;
    }
}

// NEW FUNCTION: Resolve violation
function resolveViolation($violation_id, $conn) {
    try {
        $stmt = $conn->prepare("UPDATE violations SET status = 'resolved', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $violation_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// NEW FUNCTION: Get pending violations for student (for sanction assignment)
function getPendingViolationsForStudent($user_id, $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT v.* 
            FROM violations v 
            WHERE v.user_id = ? AND v.status = 'pending' 
            AND NOT EXISTS (SELECT 1 FROM sanctions s WHERE s.violation_id = v.id)
            ORDER BY v.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
    
}
// NEW FUNCTION: Check if sanction can be assigned to violation
function canAssignSanction($violation_id, $conn) {
    try {
        // Get violation status
        $stmt = $conn->prepare("SELECT status FROM violations WHERE id = ?");
        $stmt->bind_param("i", $violation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $violation = $result->fetch_assoc();
        
        // Violation must be pending
        if ($violation['status'] != 'pending') {
            return false;
        }
        
        // Check if sanction already exists
        $stmt2 = $conn->prepare("SELECT id FROM sanctions WHERE violation_id = ?");
        $stmt2->bind_param("i", $violation_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        return $result2->num_rows == 0;
        
    } catch (Exception $e) {
        error_log("Database error in canAssignSanction: " . $e->getMessage());
        return false;
    }
}
function getInProgressSanctions($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sanctions WHERE status = 'in-progress'");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}
?>