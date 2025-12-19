<?php
// debug_manual_fix.php
session_start();
require_once 'database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sanction_id = $_POST['sanction_id'] ?? '';
    $violation_id = $_POST['violation_id'] ?? '';
    
    echo "<h2>Manual Fix Attempt</h2>";
    echo "<p>Sanction ID: $sanction_id, Violation ID: $violation_id</p>";
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // 1. Check current state
        echo "<h3>Current State:</h3>";
        $check_stmt = $conn->prepare("SELECT status FROM violations WHERE id = ?");
        $check_stmt->bind_param("i", $violation_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $current_violation = $check_result->fetch_assoc();
        
        echo "Violation status before: " . ($current_violation['status'] ?? 'NOT FOUND') . "<br>";
        
        // 2. Try to update
        echo "<h3>Attempting Update...</h3>";
        $update_stmt = $conn->prepare("UPDATE violations SET status = 'resolved' WHERE id = ?");
        $update_stmt->bind_param("i", $violation_id);
        
        if ($update_stmt->execute()) {
            echo "<p style='color: green;'>✅ Update query executed successfully</p>";
            echo "<p>Rows affected: " . $update_stmt->affected_rows . "</p>";
            
            // Check after update
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $updated_violation = $check_result->fetch_assoc();
            
            echo "<p>Violation status after: " . ($updated_violation['status'] ?? 'NOT FOUND') . "</p>";
            
            if ($updated_violation['status'] === 'resolved') {
                echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: Violation updated to 'resolved'</p>";
            } else {
                echo "<p style='color: red;'>❌ FAILED: Violation still not 'resolved'</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Update failed: " . $update_stmt->error . "</p>";
        }
        
        $conn->commit();
        
        echo "<h3>Test other update methods:</h3>";
        
        // Method 2: Direct SQL
        echo "<form method='POST' action='debug_direct_sql.php'>";
        echo "<input type='hidden' name='violation_id' value='$violation_id'>";
        echo "<button type='submit'>Try Direct SQL Update</button>";
        echo "</form>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    echo '<p><a href="debug_specific_issue.php">Back to Diagnostic</a></p>';
}
?>