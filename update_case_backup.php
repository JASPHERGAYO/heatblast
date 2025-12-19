<?php
session_start();
require_once 'database.php';

// Set error handler to convert errors to exceptions so we can catch them
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
    
    // Sanction-related fields (may not be present in all requests)
    $sanction_id = $_POST['sanction_id'] ?? '';
    $sanction_type = $_POST['sanction_type'] ?? '';
    $sanction_status = $_POST['sanction_status'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $completion_date = $_POST['completion_date'] ?? '';
    $hours_completed = $_POST['hours_completed'] ?? '';
    $counselor_notes = $_POST['counselor_notes'] ?? '';
    $has_sanction = $_POST['has_sanction'] ?? '0';
    
    if (empty($case_id) || empty($violation_type) || empty($violation_category) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        // DEBUG: Log all incoming data
        error_log("=== UPDATE_CASE.PHP DEBUG START ===");
        error_log("case_id: " . $case_id);
        error_log("status: " . $status);
        error_log("sanction_id: '" . $sanction_id . "'");
        error_log("sanction_type: '" . $sanction_type . "'");
        error_log("hours_completed: '" . $hours_completed . "'");
        
        // Start transaction
        $conn->begin_transaction();
        
        // ENFORCEMENT RULE 1: STRICT CHECK - Validate sanction requirement BEFORE any updates
        error_log("Checking if status is pending...");
        if ($status == 'pending') {
            error_log("STATUS IS PENDING - Checking sanctions table...");
            
            // Check if sanctions table exists first
            $check_table = $conn->query("SHOW TABLES LIKE 'sanctions'");
            if ($check_table->num_rows == 0) {
                error_log("ERROR: Sanctions table does NOT exist!");
                // If table is missing, we cannot verify or store sanctions, which are required for pending cases
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Database Error: sanctions table is missing. Cannot assign required sanction for pending case.']);
                exit;
            }
            
            error_log("Sanctions table exists. Checking for existing sanction...");
            
            // Check if this case already has a sanction
            $sanction_check = $conn->prepare("SELECT id FROM sanctions WHERE violation_id = ?");
            if (!$sanction_check) {
                throw new Exception("Prepare failed (sanction_check): " . $conn->error);
            }
            $sanction_check->bind_param("i", $case_id);
            $sanction_check->execute();
            $sanction_result = $sanction_check->get_result();
            
            $has_existing_sanction = ($sanction_result->num_rows > 0);
            error_log("Has existing sanction?: " . ($has_existing_sanction ? 'YES' : 'NO'));
            
            // If no existing sanction AND no new sanction is being added, REJECT the update
            if (!$has_existing_sanction && empty($sanction_type)) {
                error_log("BLOCKING UPDATE: No existing sanction AND no new sanction provided!");
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Cannot set case status to pending without a sanction. Please assign a sanction first.']);
                exit;
            }
            
            error_log("Sanction check passed. Proceeding with update...");
        } else {
            error_log("Status is NOT pending (status = " . $status . "), skipping sanction check");
        }
        
        // ENFORCEMENT RULE 2: Auto-update timestamps
        $current_timestamp = date('Y-m-d H:i:s');
        $formatted_created_at = !empty($created_at) ? date('Y-m-d H:i:s', strtotime($created_at)) : $current_timestamp;
        
        // Map status for violations table (schema uses 'under_review' instead of 'in-progress')
        $violation_status = $status;
        if ($status == 'in-progress') {
            $violation_status = 'under_review';
        }
        
        // Update violations table with ALL fields including resolution_notes
        $check_updated = $conn->query("SHOW COLUMNS FROM violations LIKE 'updated_at'");
        
        // Check if resolution_notes column exists
        $check_resolution_notes = $conn->query("SHOW COLUMNS FROM violations LIKE 'resolution_notes'");
        $has_resolution_notes = ($check_resolution_notes->num_rows > 0);
        
        if ($check_updated->num_rows > 0 && $has_resolution_notes) {
            $stmt = $conn->prepare("UPDATE violations SET violation_type = ?, violation_category = ?, description = ?, status = ?, resolution_notes = ?, created_at = ?, updated_at = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed (update violations): " . $conn->error);
            }
            $stmt->bind_param("sssssssi", $violation_type, $violation_category, $description, $violation_status, $resolution_notes, $formatted_created_at, $current_timestamp, $case_id);
        } elseif ($check_updated->num_rows > 0) {
            // Without resolution_notes column
            $stmt = $conn->prepare("UPDATE violations SET violation_type = ?, violation_category = ?, description = ?, status = ?, created_at = ?, updated_at = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed (update violations): " . $conn->error);
            }
            $stmt->bind_param("ssssssi", $violation_type, $violation_category, $description, $violation_status, $formatted_created_at, $current_timestamp, $case_id);
        } elseif ($has_resolution_notes) {
            // Without updated_at but with resolution_notes
            $stmt = $conn->prepare("UPDATE violations SET violation_type = ?, violation_category = ?, description = ?, status = ?, resolution_notes = ?, created_at = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed (update violations no updated_at): " . $conn->error);
            }
            $stmt->bind_param("ssssssi", $violation_type, $violation_category, $description, $violation_status, $resolution_notes, $formatted_created_at, $case_id);
        } else {
            // Fallback for old schema
            $stmt = $conn->prepare("UPDATE violations SET violation_type = ?, violation_category = ?, description = ?, status = ?, created_at = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed (update violations basic): " . $conn->error);
            }
            $stmt->bind_param("sssssi", $violation_type, $violation_category, $description, $violation_status, $formatted_created_at, $case_id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update violation: " . $stmt->error);
        }
        
        error_log("Violation updated successfully");
        
        // ENFORCEMENT RULE 3: Handle sanction updates if provided
        if (!empty($sanction_type)) {
            error_log("Sanction type provided, processing sanction...");
            // Check if sanctions table exists
            $check_table = $conn->query("SHOW TABLES LIKE 'sanctions'");
            if ($check_table->num_rows == 0) {
                // Cannot handle sanctions if table doesn't exist
                throw new Exception("Database Error: 'sanctions' table is missing. Cannot save sanction.");
            }
            
            // Format dates properly
            $formatted_due_date = !empty($due_date) ? date('Y-m-d H:i:s', strtotime($due_date)) : null;
            $formatted_completion_date = !empty($completion_date) ? date('Y-m-d H:i:s', strtotime($completion_date)) : null;
            
            // Convert hours_completed to float
            $hours_float = !empty($hours_completed) ? floatval($hours_completed) : null;
            
            // Check if columns exist in sanctions table
            $check_hours = $conn->query("SHOW COLUMNS FROM sanctions LIKE 'hours_completed'");
            $check_counselor = $conn->query("SHOW COLUMNS FROM sanctions LIKE 'counselor_notes'");
            $has_hours = ($check_hours->num_rows > 0);
            $has_counselor = ($check_counselor->num_rows > 0);
            
            // Check if sanction exists
            if (!empty($sanction_id)) {
                error_log("Updating existing sanction ID: " . $sanction_id);
                // Update existing sanction by ID
                
                // Check if updated_at exists in sanctions
                $check_updated_sanction = $conn->query("SHOW COLUMNS FROM sanctions LIKE 'updated_at'");
                
                if ($check_updated_sanction->num_rows > 0) {
                    if ($has_hours && $has_counselor) {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ?, hours_completed = ?, counselor_notes = ?, updated_at = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction with hours): " . $conn->error);
                        }
                        $update_stmt->bind_param("ssssdssi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $counselor_notes, $current_timestamp, $sanction_id);
                    } elseif ($has_hours) {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ?, hours_completed = ?, updated_at = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction hours only): " . $conn->error);
                        }
                        $update_stmt->bind_param("ssssdsi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $current_timestamp, $sanction_id);
                    } elseif ($has_counselor) {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ?, counselor_notes = ?, updated_at = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction counselor only): " . $conn->error);
                        }
                        $update_stmt->bind_param("ssssssi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $counselor_notes, $current_timestamp, $sanction_id);
                    } else {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ?, updated_at = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction): " . $conn->error);
                        }
                        $update_stmt->bind_param("sssssi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $current_timestamp, $sanction_id);
                    }
                } else {
                    if ($has_hours && $has_counselor) {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ?, hours_completed = ?, counselor_notes = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction no updated_at with hours): " . $conn->error);
                        }
                        $update_stmt->bind_param("ssssdsi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $counselor_notes, $sanction_id);
                    } elseif ($has_hours) {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ?, hours_completed = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction no updated_at hours only): " . $conn->error);
                        }
                        $update_stmt->bind_param("ssssdi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $sanction_id);
                    } elseif ($has_counselor) {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ?, counselor_notes = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction no updated_at counselor only): " . $conn->error);
                        }
                        $update_stmt->bind_param("sssssi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $counselor_notes, $sanction_id);
                    } else {
                        $update_stmt = $conn->prepare("UPDATE sanctions SET sanction_type = ?, status = ?, due_date = ?, completion_date = ? WHERE id = ?");
                        if (!$update_stmt) {
                            throw new Exception("Prepare failed (update sanction no updated_at): " . $conn->error);
                        }
                        $update_stmt->bind_param("ssssi", $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $sanction_id);
                    }
                }
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update sanction: " . $update_stmt->error);
                }
            } else {
                error_log("Inserting new sanction...");
                // Insert new sanction
                $check_updated_sanction = $conn->query("SHOW COLUMNS FROM sanctions LIKE 'updated_at'");
                
                if ($check_updated_sanction->num_rows > 0) {
                    if ($has_hours && $has_counselor) {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, hours_completed, counselor_notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction with hours): " . $conn->error);
                        }
                        $insert_stmt->bind_param("issssdsss", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $counselor_notes, $current_timestamp, $current_timestamp);
                    } elseif ($has_hours) {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, hours_completed, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction hours only): " . $conn->error);
                        }
                        $insert_stmt->bind_param("issssdss", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $current_timestamp, $current_timestamp);
                    } elseif ($has_counselor) {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, counselor_notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction counselor only): " . $conn->error);
                        }
                        $insert_stmt->bind_param("isssssss", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $counselor_notes, $current_timestamp, $current_timestamp);
                    } else {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction): " . $conn->error);
                        }
                        $insert_stmt->bind_param("issssss", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $current_timestamp, $current_timestamp);
                    }
                } else {
                    if ($has_hours && $has_counselor) {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, hours_completed, counselor_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction no updated_at with hours): " . $conn->error);
                        }
                        $insert_stmt->bind_param("issssdss", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $counselor_notes, $current_timestamp);
                    } elseif ($has_hours) {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, hours_completed, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction no updated_at hours only): " . $conn->error);
                        }
                        $insert_stmt->bind_param("issssds", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $hours_float, $current_timestamp);
                    } elseif ($has_counselor) {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, counselor_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction no updated_at counselor only): " . $conn->error);
                        }
                        $insert_stmt->bind_param("issssss", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $counselor_notes, $current_timestamp);
                    } else {
                        $insert_stmt = $conn->prepare("INSERT INTO sanctions (violation_id, sanction_type, status, due_date, completion_date, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            throw new Exception("Prepare failed (insert sanction no updated_at): " . $conn->error);
                        }
                        $insert_stmt->bind_param("isssss", $case_id, $sanction_type, $sanction_status, $formatted_due_date, $formatted_completion_date, $current_timestamp);
                    }
                }
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to insert sanction: " . $insert_stmt->error);
                }
            }
        }
        
        $conn->commit();
        
        error_log("Transaction committed successfully");
        error_log("=== UPDATE_CASE.PHP DEBUG END ===");
        
        // Return success with updated status
        echo json_encode([
            'success' => true, 
            'message' => 'Case updated successfully',
            'updated_status' => $status,
            'timestamp' => $current_timestamp
        ]);
        
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>