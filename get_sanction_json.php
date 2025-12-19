<?php
session_start();
require_once 'database.php';
require_once 'admin_function.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$case_id = $_GET['case_id'] ?? null;

if (!$case_id) {
    echo json_encode(['success' => false, 'message' => 'No case ID provided']);
    exit;
}

try {
    // Get complete case details with sanction information
    $stmt = $conn->prepare("
        SELECT 
            v.*, 
            sp.firstname, 
            sp.surname, 
            sp.student_number, 
            sp.course, 
            sp.section,
            s.sanction_type,
            s.status as sanction_status,
            s.due_date,
            s.completion_date,
            CASE 
                WHEN v.user_type = 'admin' THEN 'Administrator'
                WHEN v.user_type = 'staff' THEN st.fullname
                ELSE 'Unknown'
            END as recorded_by_name
        FROM violations v 
        JOIN student_profiles sp ON v.user_id = sp.user_id 
        LEFT JOIN sanctions s ON v.id = s.violation_id
        LEFT JOIN staff st ON v.recorded_by = st.id AND v.user_type = 'staff'
        WHERE v.id = ?
    ");
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $case = $result->fetch_assoc();
    
    if ($case) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $case['id'],
                'student_name' => $case['surname'] . ', ' . $case['firstname'],
                'student_number' => $case['student_number'],
                'course_section' => $case['course'] . ' - ' . $case['section'],
                'violation_type' => $case['violation_type'],
                'violation_category' => $case['violation_category'],
                'description' => $case['description'] ?? '',
                'status' => $case['status'],
                'created_at' => $case['created_at'],
                'recorded_by_name' => $case['recorded_by_name'],
                'sanction_type' => $case['sanction_type'] ?? '',
                'sanction_status' => $case['sanction_status'] ?? 'pending',
                'due_date' => $case['due_date'] ?? '',
                'completion_date' => $case['completion_date'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Case not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>