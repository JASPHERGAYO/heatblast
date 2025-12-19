<?php
session_start();
require_once 'database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sanction_id = $_POST['sanction_id'];
    $violation_id = $_POST['violation_id'];
    $new_status = $_POST['new_status'];
    
    echo "<h2>Manual Update Test</h2>";
    
    try {
        $conn->begin_transaction();
        
        echo "<h3>Step 1: Update Sanction Status</h3>";
        $stmt1 = $conn->prepare("UPDATE sanctions SET status = ? WHERE id = ?");
        $stmt1->bind_param("si", $new_status, $sanction_id);
        
        if ($stmt1->execute()) {
            echo "<p>✅ Sanction updated to: $new_status</p>";
        } else {
            throw new Exception("Failed to update sanction: " . $stmt1->error);
        }
        
        echo "<h3>Step 2: Determine Violation Status</h3>";
        $violation_status = 'pending';
        
        if ($new_status == 'completed') {
            $violation_status = 'resolved';
        } elseif ($new_status == 'in-progress') {
            $violation_status = 'under_review';
        }
        
        echo "<p>Violation should be: $violation_status</p>";
        
        echo "<h3>Step 3: Update Violation Status</h3>";
        $stmt2 = $conn->prepare("UPDATE violations SET status = ? WHERE id = ?");
        $stmt2->bind_param("si", $violation_status, $violation_id);
        
        if ($stmt2->execute()) {
            echo "<p>✅ Violation updated to: $violation_status</p>";
            echo "<p>Rows affected: " . $stmt2->affected_rows . "</p>";
        } else {
            throw new Exception("Failed to update violation: " . $stmt2->error);
        }
        
        $conn->commit();
        echo "<p style='color: green; font-weight: bold;'>✅ TRANSACTION COMMITTED SUCCESSFULLY</p>";
        
        // Check result
        echo "<h3>Step 4: Verify Update</h3>";
        $check_stmt = $conn->prepare("SELECT status FROM violations WHERE id = ?");
        $check_stmt->bind_param("i", $violation_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $updated = $check_result->fetch_assoc();
        
        echo "<p>Current violation status in database: <strong>" . $updated['status'] . "</strong></p>";
        
        if ($updated['status'] == $violation_status) {
            echo "<p style='color: green;'>✅ VERIFICATION PASSED: Status matches expected value</p>";
        } else {
            echo "<p style='color: red;'>❌ VERIFICATION FAILED: Expected '$violation_status' but got '{$updated['status']}'</p>";
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    echo '<p><a href="debug_status.php?sanction_id=' . $sanction_id . '">Check Status Again</a></p>';
}
?>