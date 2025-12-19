<?php
session_start();
require_once 'database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: Violation Status Update Check</h2>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Test a specific sanction ID (change this to a real ID from your database)
$test_sanction_id = isset($_GET['sanction_id']) ? $_GET['sanction_id'] : 1;

echo "<h3>Testing Sanction ID: $test_sanction_id</h3>";

try {
    // 1. Check if sanction exists
    $stmt = $conn->prepare("SELECT * FROM sanctions WHERE id = ?");
    $stmt->bind_param("i", $test_sanction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p style='color: red;'>❌ Sanction not found with ID: $test_sanction_id</p>";
        
        // List available sanctions
        echo "<h4>Available Sanctions:</h4>";
        $all_sanctions = $conn->query("SELECT id, violation_id, status FROM sanctions ORDER BY id LIMIT 10");
        while($row = $all_sanctions->fetch_assoc()) {
            echo "Sanction ID: {$row['id']} | Violation ID: {$row['violation_id']} | Status: {$row['status']}<br>";
        }
        exit;
    }
    
    $sanction = $result->fetch_assoc();
    echo "<p>✅ Found sanction:</p>";
    echo "<pre>" . print_r($sanction, true) . "</pre>";
    
    $violation_id = $sanction['violation_id'];
    
    // 2. Check current violation status
    $stmt2 = $conn->prepare("SELECT * FROM violations WHERE id = ?");
    $stmt2->bind_param("i", $violation_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($result2->num_rows === 0) {
        echo "<p style='color: red;'>❌ Violation not found with ID: $violation_id</p>";
        exit;
    }
    
    $violation = $result2->fetch_assoc();
    echo "<p>✅ Found violation:</p>";
    echo "<pre>" . print_r($violation, true) . "</pre>";
    
    // 3. Test the update logic
    echo "<h3>Testing Update Logic:</h3>";
    
    $new_status = 'in-progress'; // Try changing this to test
    $violation_status = 'pending'; // Default
    
    if ($new_status == 'completed') {
        $violation_status = 'resolved';
    } elseif ($new_status == 'in-progress') {
        $violation_status = 'under_review'; // This is the database value
    } else {
        $violation_status = 'pending';
    }
    
    echo "<p>If we update sanction to: <strong>$new_status</strong></p>";
    echo "<p>Violation should update to: <strong>$violation_status</strong></p>";
    
    // 4. Test manual update
    echo "<h3>Test Manual Update:</h3>";
    echo "<form method='POST' action='debug_update_test.php'>";
    echo "<input type='hidden' name='sanction_id' value='$test_sanction_id'>";
    echo "<input type='hidden' name='violation_id' value='$violation_id'>";
    echo "<label>New Sanction Status:</label>";
    echo "<select name='new_status'>";
    echo "<option value='pending'>Pending</option>";
    echo "<option value='in-progress'>In Progress</option>";
    echo "<option value='completed'>Completed</option>";
    echo "</select>";
    echo "<button type='submit'>Test Update</button>";
    echo "</form>";
    
    // 5. Check if update_sanction_status.php exists
    echo "<h3>File Check:</h3>";
    $files_to_check = [
        'update_sanction_status.php',
        'assign_sanction.php',
        'update_case.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            echo "<p>✅ $file exists</p>";
            
            // Check file content
            $content = file_get_contents($file);
            if (strpos($content, 'violation') !== false) {
                echo "<p>  - Contains 'violation' keyword</p>";
            }
            if (strpos($content, 'UPDATE violations') !== false) {
                echo "<p>  - Contains 'UPDATE violations' query</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ $file NOT FOUND</p>";
        }
    }
    
    // 6. Check database schema
    echo "<h3>Database Schema Check:</h3>";
    
    // Check violations table columns
    $columns_result = $conn->query("DESCRIBE violations");
    echo "<p>Violations table columns:</p>";
    echo "<ul>";
    while($col = $columns_result->fetch_assoc()) {
        echo "<li>{$col['Field']} - {$col['Type']} - Default: {$col['Default']}</li>";
    }
    echo "</ul>";
    
    // Check status column values
    $status_result = $conn->query("SELECT DISTINCT status FROM violations");
    echo "<p>Existing violation status values:</p>";
    echo "<ul>";
    while($status = $status_result->fetch_assoc()) {
        echo "<li>{$status['status']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>