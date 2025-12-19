<?php
// remove_violation.php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $violation_id = $_POST['violation_id'] ?? null;
    
    if ($violation_id) {
        try {
            // First check if violation has sanctions
            $check_sanctions = $conn->prepare("SELECT id FROM sanctions WHERE violation_id = ?");
            $check_sanctions->bind_param("i", $violation_id);
            $check_sanctions->execute();
            $has_sanctions = $check_sanctions->get_result()->num_rows > 0;
            
            if ($has_sanctions) {
                echo json_encode(['success' => false, 'message' => 'Cannot remove violation that has sanctions assigned. Remove sanctions first.']);
            } else {
                // Delete the violation
                $stmt = $conn->prepare("DELETE FROM violations WHERE id = ?");
                $stmt->bind_param("i", $violation_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Violation removed successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to remove violation']);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid violation ID']);
    }
}
?>