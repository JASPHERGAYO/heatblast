<?php
// get_dashboard_stats.php - FIXED VERSION
session_start();
require_once 'database.php';
require_once 'admin_function.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get basic stats
    $total_violations = getTotalViolations($conn);
    $pending_cases = getPendingCases($conn);
    $total_students = getTotalStudents($conn);
    $completed_sanctions = getCompletedSanctions($conn);
    
    // ========== FIXED: Get in-progress cases WITHOUT duplication ==========
    
    // Option A: Count ONLY violations with 'under_review' status (recommended)
    $in_progress_stmt = $conn->prepare("SELECT COUNT(DISTINCT v.id) as total 
                                        FROM violations v 
                                        WHERE v.status = 'under_review'");
    $in_progress_stmt->execute();
    $in_progress_result = $in_progress_stmt->get_result();
    $in_progress_cases = $in_progress_result->fetch_assoc()['total'];
    
    // OR Option B: Count unique cases where EITHER violation is under_review OR sanction is in-progress
    /*
    $in_progress_stmt = $conn->prepare("SELECT COUNT(DISTINCT COALESCE(v.id, s.violation_id)) as total
                                        FROM violations v 
                                        LEFT JOIN sanctions s ON v.id = s.violation_id
                                        WHERE v.status = 'under_review' OR s.status = 'in-progress'");
    $in_progress_stmt->execute();
    $in_progress_result = $in_progress_stmt->get_result();
    $in_progress_cases = $in_progress_result->fetch_assoc()['total'];
    */
    
    echo json_encode([
        'success' => true,
        'total_violations' => $total_violations,
        'pending_cases' => $pending_cases,
        'total_students' => $total_students,
        'completed_sanctions' => $completed_sanctions,
        'in_progress_cases' => $in_progress_cases
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching stats: ' . $e->getMessage()]);
}
?>