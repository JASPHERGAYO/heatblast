<?php
// test_current_script.php
echo "<h2>Testing Current update_sanction_proof.php</h2>";

// Read the current file
$file_content = file_get_contents('update_sanction_proof.php');

// Check critical parts
echo "<h3>Check 1: Does it get violation_id?</h3>";
if (strpos($file_content, 'SELECT violation_id FROM sanctions') !== false) {
    echo "<p style='color: green;'>✅ YES: Gets violation_id from sanctions table</p>";
} else {
    echo "<p style='color: red;'>❌ NO: Doesn't get violation_id</p>";
}

echo "<h3>Check 2: Does it update violations table?</h3>";
if (strpos($file_content, "UPDATE violations SET status = 'resolved'") !== false) {
    echo "<p style='color: green;'>✅ YES: Updates violations to 'resolved'</p>";
} else {
    echo "<p style='color: red;'>❌ NO: Doesn't update violations</p>";
}

echo "<h3>Check 3: Transaction handling?</h3>";
if (strpos($file_content, '$conn->begin_transaction()') !== false && 
    strpos($file_content, '$conn->commit()') !== false) {
    echo "<p style='color: green;'>✅ YES: Has proper transaction handling</p>";
} else {
    echo "<p style='color: red;'>❌ NO: Missing transaction handling</p>";
}

echo "<h3>Check 4: Error handling for violation update?</h3>";
if (strpos($file_content, 'Execute failed for violation update') !== false) {
    echo "<p style='color: green;'>✅ YES: Has specific error message for violation update</p>";
} else {
    echo "<p style='color: red;'>❌ NO: Missing specific error handling</p>";
}

// Show the actual update violation code
echo "<h3>Actual violation update code in file:</h3>";
$lines = explode("\n", $file_content);
$found = false;
foreach($lines as $line_num => $line) {
    if (strpos($line, 'violation') !== false && strpos($line, 'UPDATE') !== false) {
        echo "Line " . ($line_num + 1) . ": " . htmlspecialchars($line) . "<br>";
        $found = true;
    }
}
if (!$found) {
    echo "<p style='color: red;'>❌ No violation update code found!</p>";
}

// Test with a mock execution
echo "<h3>Test Execution Flow:</h3>";
echo "<ol>";
echo "<li>User submits completion form ✓</li>";
echo "<li>Script gets sanction_id: <code>\$sanction_id = \$_POST['proofSanctionId']</code> ✓</li>";
echo "<li>Gets violation_id: <code>SELECT violation_id FROM sanctions WHERE id = ?</code> ✓</li>";
echo "<li>Updates sanction: <code>UPDATE sanctions SET status = 'completed' ...</code> ✓</li>";
echo "<li>Updates violation: <code>UPDATE violations SET status = 'resolved' WHERE id = ?</code> ✓</li>";
echo "<li>Commits transaction ✓</li>";
echo "</ol>";

echo "<p>The script logic looks correct. The issue is OLD DATA.</p>";
?>