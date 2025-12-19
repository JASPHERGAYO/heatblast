<?php
session_start();

// Include database connection
require_once 'database.php';

// Check if user is logged in as staff
if (!isset($_SESSION['staff_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Check if student ID is passed via QR code scan
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

// Function to get student data from database
function getStudentData($student_id, $conn) {
    // Try to find by student_number first
    $sql = "SELECT sp.*, u.email 
            FROM student_profiles sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE sp.student_number = ? 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    // If not found by student_number, try by user_id
    $sql = "SELECT sp.*, u.email 
            FROM student_profiles sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE sp.user_id = ? 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Function to get violation counts by category - UPDATED to exclude completed sanctions
function getViolationCounts($user_id, $conn) {
    // Count minor violations that DON'T have completed sanctions
    $sql = "SELECT COUNT(DISTINCT v.id) AS total 
            FROM violations v 
            LEFT JOIN sanctions s ON v.id = s.violation_id 
            WHERE v.user_id = ? 
            AND v.violation_category = 'minor' 
            AND (s.status IS NULL OR s.status != 'completed')";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $minor_row = mysqli_fetch_assoc($result);
    $minor_total = $minor_row ? $minor_row['total'] : 0;

    // Count major violations that DON'T have completed sanctions
    $sql = "SELECT COUNT(DISTINCT v.id) AS total 
            FROM violations v 
            LEFT JOIN sanctions s ON v.id = s.violation_id 
            WHERE v.user_id = ? 
            AND v.violation_category = 'major' 
            AND (s.status IS NULL OR s.status != 'completed')";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $major_row = mysqli_fetch_assoc($result);
    $major_total = $major_row ? $major_row['total'] : 0;

    // Count CLEARED/COMPLETED violations
    $sql = "SELECT COUNT(DISTINCT v.id) AS total 
            FROM violations v 
            JOIN sanctions s ON v.id = s.violation_id 
            WHERE v.user_id = ? 
            AND s.status = 'completed'";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cleared_row = mysqli_fetch_assoc($result);
    $cleared_total = $cleared_row ? $cleared_row['total'] : 0;

    // Calculate conversions (every 4 minors = 1 major)
    $converted_majors = floor($minor_total / 4);
    $effective_major_total = $major_total + $converted_majors;
    $remaining_minors = $minor_total % 4;

    return [
        'minor_total' => $minor_total,
        'major_total' => $major_total,
        'cleared_total' => $cleared_total,
        'converted_majors' => $converted_majors,
        'effective_major_total' => $effective_major_total,
        'remaining_minors' => $remaining_minors,
        'total_violations' => $minor_total + $major_total,
        'active_violations' => $minor_total + $major_total
    ];
}

// Function to get recent violation - UPDATED to exclude completed sanctions
function getRecentViolation($user_id, $conn) {
    $sql = "SELECT v.violation_type, v.violation_category, v.created_at, v.status
            FROM violations v 
            LEFT JOIN sanctions s ON v.id = s.violation_id 
            WHERE v.user_id = ? 
            AND (s.status IS NULL OR s.status != 'completed')
            ORDER BY v.created_at DESC 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Function to get total students count
function getTotalStudents($conn) {
    $sql = "SELECT COUNT(*) AS total FROM student_profiles";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    return 0;
}

// Function to get monthly violations count
function getMonthlyViolations($conn) {
    $sql = "SELECT COUNT(*) AS total 
            FROM violations 
            WHERE MONTH(created_at) = MONTH(CURDATE()) 
            AND YEAR(created_at) = YEAR(CURDATE())";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    return 0;
}

// Function to get pending cases count - UPDATED to exclude completed sanctions
function getPendingCases($conn) {
    $sql = "SELECT COUNT(DISTINCT v.id) AS total 
            FROM violations v 
            LEFT JOIN sanctions s ON v.id = s.violation_id 
            WHERE (s.status IS NULL OR s.status != 'completed')";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    return 0;
}

// Get dashboard statistics
$total_students = getTotalStudents($conn);
$monthly_violations = getMonthlyViolations($conn);
$pending_cases = getPendingCases($conn);

// Get student data if ID is provided
$student_data = null;
$violation_counts = null;
$recent_violation = null;

if ($student_id) {
    $student_data = getStudentData($student_id, $conn);
    if ($student_data) {
        $violation_counts = getViolationCounts($student_data['user_id'], $conn);
        $recent_violation = getRecentViolation($student_data['user_id'], $conn);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - Student Violation System</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --danger: #e74c3c;
            --warning: #f39c12;
            --success: #2ecc71;
            --light: #ecf0f1;
            --dark: #34495e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }

        .staff-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Main Content */
        .staff-main {
            flex: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Welcome Section - Shown when no QR is scanned */
        .welcome-section {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 800px;
            width: 100%;
        }

        .welcome-icon {
            font-size: 5em;
            color: var(--primary);
            margin-bottom: 30px;
        }

        .welcome-section h2 {
            color: var(--secondary);
            margin-bottom: 20px;
            font-size: 2.2em;
        }

        .welcome-section p {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
            line-height: 1.6;
        }

        .scan-instructions {
            background: var(--light);
            padding: 30px;
            border-radius: 15px;
            margin: 40px 0;
            text-align: left;
        }

        .scan-instructions h3 {
            color: var(--secondary);
            margin-bottom: 20px;
            text-align: center;
        }

        .scan-instructions ol {
            padding-left: 25px;
        }

        .scan-instructions li {
            margin-bottom: 15px;
            color: #555;
            font-size: 16px;
            line-height: 1.5;
        }

        .scan-instructions strong {
            color: var(--primary);
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid var(--primary);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Student Profile Section - Shown when QR is scanned */
        .student-profile {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary), #2980b9);
            color: white;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5em;
            font-weight: bold;
            border: 3px solid white;
        }

        .profile-info h2 {
            margin-bottom: 10px;
            font-size: 1.8em;
        }

        .profile-info p {
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .profile-details {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .detail-group {
            background: var(--light);
            padding: 25px;
            border-radius: 15px;
        }

        .detail-group h4 {
            color: var(--secondary);
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        .detail-group p {
            margin-bottom: 10px;
            color: #555;
        }

        .detail-group strong {
            color: var(--secondary);
        }

        /* UPDATED STYLES FOR VIOLATION COUNTS */
        .violation-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .stat-box {
            text-align: center;
            flex: 1;
            padding: 15px;
            border-radius: 10px;
            background: white;
            border: 2px solid transparent;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .minor-count .stat-value {
            color: var(--warning);
        }

        .major-count .stat-value {
            color: var(--danger);
        }

        .total-count .stat-value {
            color: var(--secondary);
        }

        .cleared-count .stat-value {
            color: var(--success);
        }

        .conversion-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.9em;
            text-align: center;
        }

        .conversion-warning strong {
            color: #856404;
        }

        .critical-warning {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.9em;
            text-align: center;
        }

        .critical-warning strong {
            color: #721c24;
        }

        .cleared-notice {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 0.9em;
            text-align: center;
            color: #155724;
        }

        .violation-category-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 8px;
        }

        .violation-category-minor {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .violation-category-major {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .violation-actions {
            padding: 30px;
            background: var(--light);
            text-align: center;
            border-top: 1px solid #ddd;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }

        /* ADD THIS NEW STYLE FOR COMPLETED INDICATOR */
        .completed-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 8px;
            border: 1px solid #c3e6cb;
        }

        /* Modal Styles for Violation History */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .violation-history-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }

        .violation-history-item.minor {
            border-left: 4px solid var(--warning);
        }

        .violation-history-item.major {
            border-left: 4px solid var(--danger);
        }

        .violation-history-item.cleared {
            border-left: 4px solid var(--success);
            opacity: 0.7;
        }

        .violation-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .violation-history-date {
            color: #666;
            font-size: 0.9em;
        }

        /* Responsive */
       /* RESPONSIVE STAFF PORTAL */
@media (max-width: 768px) {
    .staff-main {
        padding: 20px 10px;
    }

    .welcome-section {
        padding: 30px 20px;
    }

    .quick-stats {
        grid-template-columns: 1fr;
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }

    .profile-avatar {
        margin-bottom: 15px;
    }

    .profile-details {
        grid-template-columns: 1fr;
        padding: 20px;
    }

    .violation-stats {
        flex-direction: column;
        gap: 10px;
    }

    .violation-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 20px;
    }

    .btn {
        width: 100%;
        margin: 0;
    }

    .modal-content {
        width: 95%;
        margin: 20% auto;
    }
}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'nav.php'; ?>

    <div class="staff-container">
        <!-- Main Content -->
        <div class="staff-main">
            <?php if (!$student_id || !$student_data): ?>
                <!-- Welcome Section - Shown when no QR is scanned -->
                <div class="welcome-section">
                    <div class="welcome-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h2>Ready to Scan Student QR Code</h2>
                    <p>Scan a student's QR code to view their profile and record violations</p>
                    
                    <div class="quick-stats">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_students; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $monthly_violations; ?></div>
                            <div class="stat-label">Violations This Month</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $pending_cases; ?></div>
                            <div class="stat-label">Active Cases</div>
                        </div>
                    </div>

                    <div class="scan-instructions">
                        <h3>How to Record a Violation</h3>
                        <ol>
                            <li><strong>Scan QR Code:</strong> Use your device camera to scan the student's QR code</li>
                            <li><strong>View Profile:</strong> Student information will automatically appear</li>
                            <li><strong>Record Violation:</strong> Click "Record Violation" button</li>
                            <li><strong>Fill Details:</strong> Enter violation type, description, and evidence</li>
                            <li><strong>Submit:</strong> Save the violation record to the system</li>
                        </ol>
                    </div>  
                
                    <div style="color: #666; font-style: italic; margin-top: 30px;">
                        <i class="fas fa-info-circle"></i>
                        Waiting for QR code scan...
                    </div>
                </div>
            <?php else: ?>
                <!-- Student Profile - Shown when QR is scanned -->
                <div class="student-profile">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php 
                            $firstname = $student_data['firstname'] ?? '';
                            $surname = $student_data['surname'] ?? '';
                            if ($firstname && $surname) {
                                echo strtoupper(substr($firstname, 0, 1) . substr($surname, 0, 1));
                            } else {
                                echo 'ST';
                            }
                            ?>
                        </div>
                        <div class="profile-info">
                            <h2>
                                <?php 
                                if ($student_data['surname'] && $student_data['firstname']) {
                                    echo htmlspecialchars($student_data['surname'] . ', ' . $student_data['firstname']);
                                } else {
                                    echo 'Student Profile';
                                }
                                ?>
                            </h2>
                            <p><?php echo htmlspecialchars($student_data['course'] ?? 'No course info'); ?></p>
                            <p>Section: <?php echo htmlspecialchars($student_data['section'] ?? 'N/A'); ?></p>
                            <p>Student No: <?php echo htmlspecialchars($student_data['student_number']); ?></p>
                        </div>
                    </div>

                    <div class="profile-details">
                        <div class="detail-group">
                            <h4>Personal Information</h4>
                            <p><strong>Student Number:</strong> <?php echo htmlspecialchars($student_data['student_number']); ?></p>
                            <p><strong>Full Name:</strong> 
                                <?php 
                                if ($student_data['surname'] && $student_data['firstname']) {
                                    echo htmlspecialchars($student_data['surname'] . ', ' . $student_data['firstname']);
                                    if (!empty($student_data['middle_initial'])) {
                                        echo ' ' . htmlspecialchars($student_data['middle_initial']) . '.';
                                    }
                                }
                                ?>
                            </p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($student_data['email'] ?? 'N/A'); ?></p>
                            <p><strong>Sex:</strong> <?php echo htmlspecialchars($student_data['sex'] ?? 'Not specified'); ?></p>
                        </div>
                        
                        <div class="detail-group">
                            <h4>Academic Information</h4>
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($student_data['course'] ?? 'Not specified'); ?></p>
                            <p><strong>Section:</strong> <?php echo htmlspecialchars($student_data['section'] ?? 'Not specified'); ?></p>
                            <p><strong>Year Level:</strong> <?php echo htmlspecialchars($student_data['year_level'] ?? 'N/A'); ?></p>
                        </div>

                        <div class="detail-group">
                            <h4>Violation Statistics</h4>
                            
                            <!-- UPDATED VIOLATION STATS - EXCLUDES COMPLETED SANCTIONS -->
                            <div class="violation-stats">
                                <div class="stat-box minor-count">
                                    <div class="stat-value"><?php echo $violation_counts['minor_total']; ?></div>
                                    <div>Active Minor</div>
                                </div>
                                <div class="stat-box major-count">
                                    <div class="stat-value"><?php echo $violation_counts['major_total']; ?></div>
                                    <div>Active Major</div>
                                </div>
                                <div class="stat-box total-count">
                                    <div class="stat-value"><?php echo $violation_counts['total_violations']; ?></div>
                                    <div>Total Active</div>
                                </div>
                            </div>

                            <!-- Show completed/cleared violations count -->
    
                            <!-- VIOLATION CONVERSION WARNINGS -->
                            <?php if ($violation_counts['converted_majors'] > 0): ?>
                            <div class="conversion-warning">
                                <strong>⚠️ Automatic Conversion:</strong><br>
                                <?= $violation_counts['converted_majors'] ?> set(s) of 4 minor violations converted to major offense(s)
                            </div>
                            <?php endif; ?>

                            <?php if ($violation_counts['remaining_minors'] == 3): ?>
                            <div class="critical-warning">
                                <strong>🚨 CRITICAL WARNING:</strong><br>
                                Next minor violation will be automatically converted to a MAJOR offense!
                            </div>
                            <?php elseif ($violation_counts['remaining_minors'] == 2): ?>
                            <div class="conversion-warning">
                                <strong>⚠️ Warning:</strong><br>
                                2 more minor violations will trigger automatic major offense
                            </div>
                            <?php endif; ?>

                            <?php if ($recent_violation): ?>
                                <p style="margin-top: 15px;"><strong>Most Recent Active Violation:</strong> 
                                    <?php echo htmlspecialchars($recent_violation['violation_type']); ?>
                                    <span class="violation-category-badge violation-category-<?= $recent_violation['violation_category'] ?>">
                                        <?= strtoupper($recent_violation['violation_category']) ?>
                                    </span>
                                    <br><small><?php echo date("M j, Y g:i A", strtotime($recent_violation['created_at'])); ?></small>
                                </p>
                            <?php else: ?>
                                <p style="margin-top: 15px; color: var(--success);"><strong>No active violations</strong></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Record Violation Button -->
                    <div class="violation-actions">
                        <button class="btn btn-warning" onclick="recordViolation('<?php echo $student_data['user_id']; ?>')">
                            <i class="fas fa-plus-circle"></i> Record Violation
                        </button>
                        <button class="btn btn-primary" onclick="viewViolationHistory('<?php echo $student_data['user_id']; ?>')">
                            <i class="fas fa-history"></i> View All Violations
                        </button>
                        <?php if ($violation_counts['cleared_total'] > 0): ?>
                        <button class="btn btn-success" onclick="viewCompletedSanctions('<?php echo $student_data['user_id']; ?>')">
                            <i class="fas fa-check-circle"></i> View Completed Sanctions
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Violation History Modal -->
    <div id="violationHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('violationHistoryModal')">&times;</span>
            <h2>Violation History</h2>
            <div id="violationHistoryContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

    <script>
        function recordViolation(studentId) {
            window.location.href = 'insert_violation.php?id=' + studentId;
        }

        function viewViolationHistory(studentId) {
            // Show loading state
            document.getElementById('violationHistoryContent').innerHTML = '<div style="text-align: center; padding: 20px;">Loading violation history...</div>';
            
            // Show modal
            document.getElementById('violationHistoryModal').style.display = 'block';
            
            // Fetch ALL violation history (including completed)
            fetch(`get_violation_history.php?student_id=${studentId}&all=true`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('violationHistoryContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('violationHistoryContent').innerHTML = 
                        '<div style="text-align: center; padding: 20px; color: #e74c3c;">Error loading violation history</div>';
                });
        }

        function viewCompletedSanctions(studentId) {
            // Show loading state
            document.getElementById('violationHistoryContent').innerHTML = '<div style="text-align: center; padding: 20px;">Loading completed sanctions...</div>';
            
            // Show modal
            document.getElementById('violationHistoryModal').style.display = 'block';
            
            // Fetch completed sanctions
            fetch(`get_violation_history.php?student_id=${studentId}&completed=true`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('violationHistoryContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('violationHistoryContent').innerHTML = 
                        '<div style="text-align: center; padding: 20px; color: #e74c3c;">Error loading completed sanctions</div>';
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>

<?php
// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>