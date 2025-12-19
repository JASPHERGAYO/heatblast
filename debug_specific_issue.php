<?php
// debug_specific_issue.php
session_start();
require_once 'database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: Specific Failed Updates</h2>";

// Find all cases where sanction is completed but violation is not resolved
$query = "SELECT s.id as sanction_id, s.violation_id, s.status as sanction_status, 
          v.status as violation_status, s.completion_date, s.completion_proof
          FROM sanctions s 
          JOIN violations v ON s.violation_id = v.id 
          WHERE s.status = 'completed' 
          AND v.status != 'resolved'
          ORDER BY s.id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<h3>Found " . $result->num_rows . " problematic records:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Sanction ID</th><th>Violation ID</th><th>Sanction Status</th><th>Violation Status</th><th>Completion Date</th><th>Has Proof?</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['sanction_id']}</td>";
        echo "<td>{$row['violation_id']}</td>";
        echo "<td>{$row['sanction_status']}</td>";
        echo "<td style='color: red;'>{$row['violation_status']}</td>";
        echo "<td>{$row['completion_date']}</td>";
        echo "<td>" . (!empty($row['completion_proof']) ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test one specific case
    $result->data_seek(0);
    $test_case = $result->fetch_assoc();
    $sanction_id = $test_case['sanction_id'];
    $violation_id = $test_case['violation_id'];
    
    echo "<h3>Test Fix for Sanction ID: $sanction_id, Violation ID: $violation_id</h3>";
    
    // Try to manually fix this one
    echo "<form method='POST' action='debug_manual_fix.php'>";
    echo "<input type='hidden' name='sanction_id' value='$sanction_id'>";
    echo "<input type='hidden' name='violation_id' value='$violation_id'>";
    echo "<button type='submit'>Manually Fix This Record</button>";
    echo "</form>";
    
} else {
    echo "<p>All sanctions and violations are properly synchronized.</p>";
}

// Check if there are any constraints or triggers
echo "<h3>Database Constraints Check:</h3>";

// Check foreign keys
$fk_query = "SELECT 
    TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND TABLE_NAME IN ('sanctions', 'violations')";
$fk_result = $conn->query($fk_query);

echo "<p>Foreign Key Constraints:</p>";
while($fk = $fk_result->fetch_assoc()) {
    echo "Table: {$fk['TABLE_NAME']}, Column: {$fk['COLUMN_NAME']}, References: {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}<br>";
}

// Check triggers
$trigger_query = "SHOW TRIGGERS";
$trigger_result = $conn->query($trigger_query);
echo "<p>Triggers:</p>";
while($trigger = $trigger_result->fetch_assoc()) {
    echo "Trigger: {$trigger['Trigger']}, Table: {$trigger['Table']}, Event: {$trigger['Event']}<br>";
}
?>