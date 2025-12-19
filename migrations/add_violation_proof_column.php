<?php
// Migration: add_violation_proof_column.php
// Adds the proof_filename column to violations table if it does not exist.

require_once __DIR__ . '/../database.php';

try {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM violations LIKE 'proof_filename'");
    if ($check && $check->num_rows > 0) {
        echo "Column proof_filename already exists in violations table.\n";
        exit;
    }

    $sql = "ALTER TABLE violations ADD COLUMN proof_filename VARCHAR(500) DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Added column proof_filename to violations table successfully.\n";
    } else {
        echo "Error adding column proof_filename: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
