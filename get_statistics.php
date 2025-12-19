<?php
require_once 'database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$response = [
    'violations_by_category' => [],
    'violations_by_month' => [],
    'sanctions_by_month' => [], // Changed to sanctions completion dates
    'violations_by_course' => [],
    'sanctions_status' => []
];

try {
    // 1. Violations by Category
    $stmt = $conn->prepare("SELECT violation_category, COUNT(*) as count FROM violations GROUP BY violation_category");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['violations_by_category'][] = $row;
    }

    // 2. SANCTIONS by Completion Month (Last 12 months) - USING COMPLETION DATE
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(completion_date, '%Y-%m') as month, COUNT(*) as count 
        FROM sanctions 
        WHERE completion_date IS NOT NULL 
        AND completion_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month 
        ORDER BY month
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['violations_by_month'][] = $row; // Still using same key for chart compatibility
    }

    // 3. Violations by Course
    $stmt = $conn->prepare("
        SELECT sp.course, COUNT(*) as count 
        FROM violations v
        JOIN student_profiles sp ON v.user_id = sp.user_id
        GROUP BY sp.course
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['violations_by_course'][] = $row;
    }

    // 4. Sanctions Status
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM sanctions GROUP BY status");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['sanctions_status'][] = $row;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>