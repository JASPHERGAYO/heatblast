<?php
session_start();
require_once 'database.php';

// Check if user is already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // User is logged in, show dashboard
} else {
    // Check if login form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Hardcoded admin credentials
        if ($email === 'admin@kld.edu.ph' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $email;
        } else {
            $login_error = "Invalid email or password!";
        }
    }
    
    // If not logged in, show login form
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }
}

// Include all functions
require_once 'admin_function.php';

// Get real statistics
$total_violations = getTotalViolations($conn);
$pending_cases = getPendingCases($conn);
$total_students = getTotalStudents($conn);
$completed_sanctions = getCompletedSanctions($conn); 
$recent_violations_result = getRecentViolations($conn);
$all_students_result = getAllStudents($conn);
$all_cases_result = getAllCases($conn);
$all_sanctions_result = getAllSanctions($conn);
$in_progress_sanctions = getInProgressSanctions($conn); 

// Check if student ID is passed via QR code scan
$student_id = $_GET['student_id'] ?? '';

// Get student data if ID is provided
$student_data = null;
$violation_counts = null;
$recent_violation = null;
$student_sanctions = null;
$student_violation_history = null;

if ($student_id) {
    $student_data = getStudentData($student_id, $conn);
    if ($student_data) {
        $violation_counts = getViolationCounts($student_data['user_id'], $conn);
        $recent_violation = getRecentViolation($student_data['user_id'], $conn);
        $student_sanctions = getStudentSanctions($student_data['user_id'], $conn);
        $student_violation_history = getStudentViolationHistory($student_data['user_id'], $conn);
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Violation System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="admin_responsive.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    

    
</head>

<body>
    <!-- NAVBAR INCLUDED -->
    <?php include 'nav.php'; ?>
        <?php if (isset($_GET['account_created'])): ?>
    <?php
    $role = $_GET['role'] ?? 'admin/staff';
    $role_display = ucfirst($role);
    ?>
    <div id="autoHideMessage" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb;">
        <i class="fas fa-check-circle"></i> 
        <strong>Account Created Successfully!</strong><br>
        New <?= $role_display ?> account has been verified and created.
    </div>
    
    <script>
    setTimeout(() => {
        const msg = document.getElementById('autoHideMessage');
        if (msg) {
            msg.remove();
            // Clean URL
            window.history.replaceState({}, '', window.location.pathname);
        }
    }, 3000);
    </script>
<?php endif; ?>

    <div class="dashboard-container">
        <!-- Sidebar - Left Side -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Violation System</h2>
                <p>Admin Dashboard</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active" onclick="showTab('dashboard')">Dashboard</a></li>
                <li><a href="#" onclick="showTab('students')">Student Management</a></li>
                <li><a href="#" onclick="showTab('cases')">Case Management</a></li>
                <li><a href="#" onclick="showTab('sanctions')">Sanctions</a></li>
                <li><a href="#" onclick="showTab('profile')">Profile</a></li>
       
                <li><a href="#" onclick="showTab('statistics')">Statistics</a></li>
                
            </ul>
        </div>
    
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
               
                <div class="header-info">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome to Student Violation System</p>
                </div>
            </div>

            <!-- Stats Grid - REAL DATA -->
 <!-- Stats Grid - REAL DATA with Status Borders -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Violations</h3>
        <div class="stat-number" id="total-violations"><?= $total_violations ?></div>
    </div>
    
    <!-- Pending Cases -->
    <div class="stat-card">
        <h3>Pending Cases</h3>
        <div class="stat-number" id="pending-cases"><?= $pending_cases ?></div>
    </div>
    
    <!-- In Progress Sanctions -->
    <div class="stat-card stat-card-in-progress">
        <h3>In Progress</h3>
        <div class="stat-number" id="in-progress-cases"><?= $in_progress_sanctions ?></div>
        <div class="stat-border in-progress-border"></div>
    </div>
    
    <div class="stat-card">
        <h3>Total Students</h3>
        <div class="stat-number" id="total-students"><?= $total_students ?></div>
    </div>
    
    <!-- Completed Sanctions -->
    <div class="stat-card stat-card-completed">
        <h3>Completed Sanctions</h3>
        <div class="stat-number" id="completed-sanctions"><?= $completed_sanctions ?></div>
        <div class="stat-border completed-border"></div>
    </div>
</div>

            <!-- Tabs Content -->
            <div class="tabs">
                <div class="tab-header">
                    <button class="tab-btn active" onclick="showTab('dashboard')">Dashboard</button>
                    <button class="tab-btn" onclick="showTab('students')">Students</button>
                    <button class="tab-btn" onclick="showTab('cases')">Case Management</button>
                    <button class="tab-btn" onclick="showTab('sanctions')">Sanctions</button>
                    <button class="tab-btn" onclick="showTab('profile')">Profile</button>
              
          
                    <button class="tab-btn" onclick="showTab('statistics')">Statistics</button>
                </div>

                <!-- Dashboard Tab - REAL DATA -->
                <div id="dashboard" class="tab-content active">
                    <h2>Recent Violations</h2>
                    <div class="table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student Number</th>
                                    <th>Violation</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_violations_result && $recent_violations_result->num_rows > 0): ?>
                                    <?php while($violation = $recent_violations_result->fetch_assoc()): ?>
                                        <?php 
                                        $has_sanction = violationHasSanction($violation['id'], $conn);
                                        
                                        // Check if sanction is completed
                                        $sanction_completed = false;
                                        $sanction_id = 0;
                                        if ($has_sanction) {
                                            $sanction_stmt = $conn->prepare("SELECT id, status FROM sanctions WHERE violation_id = ?");
                                            $sanction_stmt->bind_param("i", $violation['id']);
                                            $sanction_stmt->execute();
                                            $sanction_result = $sanction_stmt->get_result();
                                            $sanction_data = $sanction_result->fetch_assoc();
                                            $sanction_completed = ($sanction_data && $sanction_data['status'] == 'completed');
                                            $sanction_id = $sanction_data['id'] ?? 0;
                                        }
                                        
                                        // Case is resolved if violation status is resolved OR sanction is completed
                                        $is_resolved = ($violation['status'] == 'resolved' || $sanction_completed);
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($violation['surname'] . ', ' . $violation['firstname']) ?> (<?= htmlspecialchars($violation['course'] . '-' . $violation['section']) ?>)</td>
                                            <td><?= htmlspecialchars($violation['student_number']) ?></td>
                                            <td><?= htmlspecialchars($violation['violation_type']) ?></td>
                                            <td>
                                                <span class="status-badge violation-<?= $violation['violation_category'] ?>">
                                                    <?= strtoupper($violation['violation_category']) ?>
                                                </span>
                                            </td>
                                            <td><?= date("Y-m-d H:i", strtotime($violation['created_at'])) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $is_resolved ? 'resolved' : $violation['status'] ?>">
                                                    <?= $is_resolved ? 'Resolved' : ucfirst($violation['status']) ?>
                                                </span>
                                                <?php if ($has_sanction): ?>
                                                    <span class="sanction-exists-badge">
                                                        <?= $sanction_completed ? 'Completed' : 'Sanctioned' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-primary" onclick="viewStudentProfile('<?= $violation['user_id'] ?>')">View Profile</button>
                                                    
                                                    <?php if (!$is_resolved): ?>
                                                        <?php if ($has_sanction): ?>
                                                            <!-- Has sanction - can complete sanction -->
                                                            <button class="btn btn-success" onclick="uploadSanctionProof(<?= $sanction_id ?>)">Complete Sanction</button>
                                                        <?php else: ?>
                                                            <!-- No sanction - can assign sanction or remove -->
                                                            <button class="btn btn-warning" onclick="assignSanctionToViolation(<?= $violation['id'] ?>, '<?= $violation['student_number'] ?>')">Assign Sanction</button>
                                                            <button class="btn btn-danger" onclick="removeViolation(<?= $violation['id'] ?>)">Remove</button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <!-- Already resolved, can only remove if no sanction -->
                                                        <?php if (!$has_sanction): ?>
                                                            <button class="btn btn-danger" onclick="removeViolation(<?= $violation['id'] ?>)">Remove</button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 20px;">No violations found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Students Tab - REAL DATA -->
                <div id="students" class="tab-content">
                    <h2>Student Management</h2>
                    
                    <!-- FILTERS SECTION -->
                    <div class="filters-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px;">🔍 Filter Students</h4>
                        
                        <div class="filter-row" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                            <!-- Year Level Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Year Level</label>
                                <select class="form-control" id="yearLevelFilter" style="min-width: 120px;">
                                    <option value="">All Years</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            
                            <!-- Course Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Course</label>
                                <select class="form-control" id="courseFilter" style="min-width: 200px;">
                                    <option value="">All Courses</option>
                                    <option value="bsis">BS Information Systems</option>
                                    <option value="bsn">BS Nursing</option>
                                    <option value="bse">BS Engineering</option>
                                    <option value="bsp">BS Psychology</option>
                                    <option value="bscs">BS Computer Science</option>
                                </select>
                            </div>
                            
                            <!-- Section Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Section</label>
                                <select class="form-control" id="sectionFilter" style="min-width: 120px;">
                                    <option value="">All Sections</option>
                                </select>
                            </div>
                            
                            <!-- Gender Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Gender</label>
                                <select class="form-control" id="genderFilter" style="min-width: 120px;">
                                    <option value="">All Genders</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="filter-group">
                                <button class="btn btn-secondary" onclick="clearFilters()" style="margin-bottom: 0;">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="form-group">
                        <input type="text" class="form-control" placeholder="Search students by name or student number..." id="studentSearch" onkeyup="searchStudents()">
                    </div>
                    
                   

                    <div class="table">
                        <table id="studentsTable">
                            <thead>
                                <tr>
                                    <th onclick="sortTable(0)" style="cursor: pointer;">
                                        Student Number <span class="sort-icon">↕</span>
                                    </th>
                                    <th onclick="sortTable(1)" style="cursor: pointer;">
                                        Name <span class="sort-icon">↕</span>
                                    </th>
                                    <th onclick="sortTable(2)" style="cursor: pointer;">
                                        Course <span class="sort-icon">↕</span>
                                    </th>
                                    <th onclick="sortTable(3)" style="cursor: pointer;">
                                        Year Level <span class="sort-icon">↕</span>
                                    </th>
                                    <th onclick="sortTable(4)" style="cursor: pointer;">
                                        Section <span class="sort-icon">↕</span>
                                    </th>
                                    <th>Gender</th>
                                    <th onclick="sortTable(6)" style="cursor: pointer;">
                                        Active Violations <span class="sort-icon">↕</span>
                                    </th>
                                    <th onclick="sortTable(7)" style="cursor: pointer;">
                                        Pending Sanctions <span class="sort-icon">↕</span>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <?php if ($all_students_result && $all_students_result->num_rows > 0): ?>
                                    <?php while($student = $all_students_result->fetch_assoc()): ?>
                                        <?php 
                                        $has_pending_violations = hasPendingViolations($student['user_id'], $conn);
                                        $pending_sanctions_count = countPendingSanctions($student['user_id'], $conn);
                                        ?>
                                        <tr class="student-row" 
                                            data-year-level="<?= htmlspecialchars($student['year_level'] ?? '') ?>" 
                                            data-course="<?= htmlspecialchars(strtolower($student['course'])) ?>"  
                                            data-section="<?= htmlspecialchars($student['section']) ?>" 
                                            data-gender="<?= htmlspecialchars($student['sex'] ?? '') ?>"
                                            data-student-number="<?= htmlspecialchars($student['student_number']) ?>"
                                            data-name="<?= htmlspecialchars($student['surname'] . ', ' . $student['firstname']) ?>">
                                            <td><?= htmlspecialchars($student['student_number']) ?></td>
                                            <td><?= htmlspecialchars($student['surname'] . ', ' . $student['firstname']) ?></td>
                                            <td><?= htmlspecialchars($student['course']) ?></td>
                                            <td>
                                                <?php if ($student['year_level']): ?>
                                                    <?= htmlspecialchars($student['year_level']) ?> Year
                                                <?php else: ?>
                                                    Not specified
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($student['section']) ?></td>
                                            <td>
                                                <span class="gender-badge gender-<?= strtolower($student['sex'] ?? 'unknown') ?>">
                                                    <?= htmlspecialchars($student['sex'] ?? 'Not specified') ?>
                                                </span>
                                            </td>
                                            <td><?= $student['violation_count'] ?></td>
                                            <td><?= $pending_sanctions_count ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-primary" onclick="viewStudentProfile('<?= $student['user_id'] ?>')">View Profile</button>
                                                    <button class="btn btn-warning" onclick="recordViolation('<?= $student['user_id'] ?>')">Record Violation</button>
                                                    <?php if ($has_pending_violations): ?>
                                                        <button class="btn btn-danger" onclick="assignSanctionToStudent('<?= $student['student_number'] ?>')">Assign Sanction</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-disabled" disabled>No Pending Violations</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 20px;">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cases Tab - REAL DATA -->
                <div id="cases" class="tab-content">
                    <h2>Case Management</h2>
                    
                    <!-- FILTERS SECTION FOR CASES -->
                    <div class="filters-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px;">🔍 Filter Cases</h4>
                        
                        <div class="filter-row" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                            <!-- Category Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Category</label>
                                <select class="form-control" id="caseCategoryFilter" style="min-width: 150px;">
                                    <option value="">All Categories</option>
                                    <option value="minor">Minor</option>
                                    <option value="major">Major</option>
                                </select>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status</label>
                                <select class="form-control" id="caseStatusFilter" style="min-width: 150px;">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="in-progress">In Progress</option>
                                </select>
                            </div>

                            <!-- Violation Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Violation</label>
                                <select class="form-control" id="caseViolationFilter" style="min-width: 150px;">
                                    <option value="">All Violations</option>
                                </select>
                            </div>

                            <!-- Recorded By Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Recorded By</label>
                                <select class="form-control" id="caseRecordedByFilter" style="min-width: 150px;">
                                    <option value="">All Recorders</option>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="filter-group">
                                <button class="btn btn-secondary" onclick="clearCaseFilters()" style="margin-bottom: 0;">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="form-group">
                        <input type="text" class="form-control" placeholder="Search cases by student name or number..." id="caseSearch" onkeyup="searchCases()">
                    </div>
                    
                    <!-- Case Count -->
                    <div style="margin-bottom: 15px; font-weight: 600; color: #2c3e50;">
                        Showing <span id="caseCount"><?= $all_cases_result->num_rows ?? 0 ?></span> cases
                    </div>

                    <div class="table">
                        <table id="casesTable">
                            <thead>
                                <tr>
                                    <th>Case ID</th>
                                    <th>Student</th>
                                    <th>Student Number</th>
                                    <th>Violation</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Recorded By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="casesTableBody">
                                <?php if ($all_cases_result && $all_cases_result->num_rows > 0): ?>
                                    <?php while($case = $all_cases_result->fetch_assoc()): ?>
                                        <?php 
                                        $has_sanction = violationHasSanction($case['id'], $conn);
                                        
                                        // Check sanction status
                                        $sanction_status = 'none';
                                        $sanction_id = 0;
                                        if ($has_sanction) {
                                            $sanction_stmt = $conn->prepare("SELECT id, status FROM sanctions WHERE violation_id = ?");
                                            $sanction_stmt->bind_param("i", $case['id']);
                                            $sanction_stmt->execute();
                                            $sanction_result = $sanction_stmt->get_result();
                                            $sanction_data = $sanction_result->fetch_assoc();
                                            $sanction_status = $sanction_data ? $sanction_data['status'] : 'none';
                                            $sanction_id = $sanction_data['id'] ?? 0;
                                        }
                                        
                                        // Determine case status based on violation AND sanction status
                                        if ($case['status'] == 'resolved') {
                                            $display_status = 'resolved';
                                        } elseif ($sanction_status == 'completed') {
                                            $display_status = 'resolved';
                                        } elseif ($sanction_status == 'in-progress') {
                                            $display_status = 'in-progress';
                                        } else {
                                            $display_status = $case['status'];
                                        }
                                        ?>
                                        <tr class="case-row"
                                            data-category="<?= htmlspecialchars(strtolower($case['violation_category'])) ?>"
                                            data-status="<?= htmlspecialchars($display_status) ?>"
                                            data-student="<?= htmlspecialchars(strtolower($case['surname'] . ', ' . $case['firstname'])) ?>"
                                            data-student-number="<?= htmlspecialchars(strtolower($case['student_number'])) ?>"
                                            data-violation="<?= htmlspecialchars(strtolower($case['violation_type'])) ?>"
                                            data-recorded-by="<?= htmlspecialchars(strtolower($case['recorded_by_name'])) ?>">
                                            <td>V-<?= str_pad($case['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($case['surname'] . ', ' . $case['firstname']) ?></td>
                                            <td><?= htmlspecialchars($case['student_number']) ?></td>
                                            <td><?= htmlspecialchars($case['violation_type']) ?></td>
                                            <td>
                                                <span class="status-badge violation-<?= $case['violation_category'] ?>">
                                                    <?= strtoupper($case['violation_category']) ?>
                                                </span>
                                            </td>
                                            <td><?= date("Y-m-d H:i", strtotime($case['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($case['recorded_by_name']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $display_status ?>">
                                                    <?= ucfirst($display_status) ?>
                                                </span>
                                                <?php if ($has_sanction): ?>
                                                    <span class="sanction-exists-badge">
                                                        <?= $sanction_status == 'completed' ? 'Completed' : ($sanction_status == 'in-progress' ? 'In Progress' : 'Sanctioned') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-primary" onclick="viewCase(<?= $case['id'] ?>)">View Details</button>
                                                    
                                                    <?php if (canAssignSanction($case['id'], $conn)): ?>
                                                        <button class="btn btn-warning" onclick="assignSanctionToViolation(<?= $case['id'] ?>, '<?= $case['student_number'] ?>')">Assign Sanction</button>
                                                        <button class="btn btn-danger" onclick="removeViolation(<?= $case['id'] ?>)">Remove</button>
                                                    <?php elseif ($has_sanction && $sanction_status != 'completed'): ?>
                                                        <button class="btn btn-success" onclick="uploadSanctionProof(<?= $sanction_id ?>)">Complete Sanction</button>
                                                    <?php elseif ($has_sanction && $sanction_status == 'completed'): ?>
                                                        <button class="btn btn-info" onclick="editCaseDetails(<?= $case['id'] ?>)">Edit Details</button>
                                                   <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 20px;">No cases found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sanctions Tab -->
                <div id="sanctions" class="tab-content">
                    <h2>Sanctions Management</h2>
                    
                    <!-- FILTERS SECTION FOR SANCTIONS -->
                    <div class="filters-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px;">🔍 Filter Sanctions</h4>
                        
                        <div class="filter-row" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                            <!-- Category Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Category</label>
                                <select class="form-control" id="sanctionCategoryFilter" style="min-width: 150px;">
                                    <option value="">All Categories</option>
                                    <option value="minor">Minor</option>
                                    <option value="major">Major</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status</label>
                                <select class="form-control" id="sanctionStatusFilter" style="min-width: 150px;">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <!-- Violation Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Violation</label>
                                <select class="form-control" id="sanctionViolationFilter" style="min-width: 150px;">
                                    <option value="">All Violations</option>
                                </select>
                            </div>

                            <!-- Sanction Type Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Sanction Type</label>
                                <select class="form-control" id="sanctionTypeFilter" style="min-width: 150px;">
                                    <option value="">All Types</option>
                                </select>
                            </div>

                            <!-- Due Date Filter -->
                            <div class="filter-group">
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Due Date</label>
                                <input type="date" class="form-control" id="sanctionDueDateFilter" style="min-width: 150px;">
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="filter-group">
                                <button class="btn btn-secondary" onclick="clearSanctionFilters()" style="margin-bottom: 0;">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="form-group">
                        <input type="text" class="form-control" placeholder="Search sanctions by student name or number..." id="sanctionSearch" onkeyup="searchSanctions()">
                    </div>
                    
                    <!-- Sanction Count -->
                    <div style="margin-bottom: 15px; font-weight: 600; color: #2c3e50;">
                        Showing <span id="sanctionCount"><?= $all_sanctions_result->num_rows ?? 0 ?></span> sanctions
                    </div>

                    <div class="table">
                        <table id="sanctionsTable">
                            <thead>
                                <tr>
                                    <th>Sanction ID</th>
                                    <th>Student</th>
                                    <th>Student Number</th>
                                    <th>Violation</th>
                                    <th>Category</th>
                                    <th>Sanction Type</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sanctionsTableBody">
                                <?php if ($all_sanctions_result && $all_sanctions_result->num_rows > 0): ?>
                                    <?php while($sanction = $all_sanctions_result->fetch_assoc()): ?>
                                        <tr class="sanction-row"
                                            data-status="<?= htmlspecialchars($sanction['status']) ?>"
                                            data-student="<?= htmlspecialchars(strtolower($sanction['surname'] . ', ' . $sanction['firstname'])) ?>"
                                            data-student-number="<?= htmlspecialchars(strtolower($sanction['student_number'])) ?>"
                                            data-sanction-type="<?= htmlspecialchars(strtolower($sanction['sanction_type'])) ?>"
                                            data-violation="<?= htmlspecialchars(strtolower($sanction['violation_type'])) ?>"
                                            data-category="<?= htmlspecialchars(strtolower($sanction['violation_category'])) ?>"
                                            data-due-date="<?= date('Y-m-d', strtotime($sanction['due_date'])) ?>">
                                            <td>S-<?= str_pad($sanction['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($sanction['surname'] . ', ' . $sanction['firstname']) ?></td>
                                            <td><?= htmlspecialchars($sanction['student_number']) ?></td>
                                            <td><?= htmlspecialchars($sanction['violation_type']) ?></td>
                                            <td>
                                                <span class="status-badge violation-<?= $sanction['violation_category'] ?>">
                                                    <?= strtoupper($sanction['violation_category']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($sanction['sanction_type']) ?></td>
                                            <td><?= date("F j, Y", strtotime($sanction['due_date'])) ?></td>
                                            <td>
                                                <span class="sanction-status status-<?= $sanction['status'] ?>">
                                                    <?= ucfirst($sanction['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($sanction['status'] == 'pending'): ?>
                                                        <button class="btn btn-primary" onclick="updateSanctionStatus(<?= $sanction['id'] ?>, 'in-progress')">Mark In Progress</button>
                                                        <button class="btn btn-success" onclick="uploadSanctionProof(<?= $sanction['id'] ?>)">Complete</button>
                                                    <?php elseif ($sanction['status'] == 'in-progress'): ?>
                                                        <button class="btn btn-warning" onclick="undoSanctionStatus(<?= $sanction['id'] ?>)">Undo to Pending</button>
                                                        <button class="btn btn-success" onclick="uploadSanctionProof(<?= $sanction['id'] ?>)">Complete</button>
                                                    <?php elseif ($sanction['status'] == 'completed'): ?>
                                                        <button class="btn btn-info" onclick="viewSanctionDetails(<?= $sanction['id'] ?>)">View Details</button>
                                                      
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 20px;">No sanctions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Profile Tab -->
                <div id="profile" class="tab-content">
                    <?php if ($student_id && $student_data): ?>
                        <!-- Show student profile when ID is provided via QR -->
                        <div class="profile-section">
                            <div class="profile-header">
                                <div class="profile-avatar" id="profileAvatar">
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
                                    <h2 id="profileName">
                                        <?php 
                                        if ($student_data['surname'] && $student_data['firstname']) {
                                            echo htmlspecialchars($student_data['surname'] . ', ' . $student_data['firstname']);
                                        } else {
                                            echo 'Student Profile';
                                        }
                                        ?>
                                    </h2>
                                    <p id="profileCourse">
                                        <?php 
                                        if ($student_data['course']) {
                                            echo htmlspecialchars($student_data['course']);
                                            if ($student_data['section']) {
                                                echo ' - Section: ' . htmlspecialchars($student_data['section']);
                                            }
                                        } else {
                                            echo 'Course information not available';
                                        }
                                        ?>
                                    </p>
                                    <p id="profileSection">Student Number: <?php echo htmlspecialchars($student_data['student_number']); ?></p>
                                </div>
                            </div>

                            <div class="profile-details">
                                <div class="detail-group">
                                    <h4>Personal Information</h4>
                                    <p><strong>Student Number:</strong> <span id="profileId"><?php echo htmlspecialchars($student_data['student_number']); ?></span></p>
                                    <p><strong>Name:</strong> 
                                        <span id="profileNameFull">
                                            <?php 
                                            if ($student_data['surname'] && $student_data['firstname']) {
                                                echo htmlspecialchars($student_data['surname'] . ', ' . $student_data['firstname']);
                                                if (!empty($student_data['middle_initial'])) {
                                                    echo ' ' . htmlspecialchars($student_data['middle_initial']) . '.';
                                                }
                                            } else {
                                                echo 'Name not set';
                                            }
                                            ?>
                                        </span>
                                    </p>
                                    <p><strong>Sex:</strong> <span id="profileSex"><?php echo htmlspecialchars($student_data['sex'] ?? 'Not specified'); ?></span></p>
                                    <p><strong>Year Level:</strong> <span id="profileYearLevel"><?php echo htmlspecialchars($student_data['year_level'] ?? 'Not specified'); ?></span></p>
                                </div>
                                
                                <div class="detail-group">
                                    <h4>Academic Information</h4>
                                    <p><strong>Course:</strong> <span id="profileCourseFull"><?php echo htmlspecialchars($student_data['course'] ?? 'Not specified'); ?></span></p>
                                    <p><strong>Section:</strong> <span id="profileSectionFull"><?php echo htmlspecialchars($student_data['section'] ?? 'Not specified'); ?></span></p>
                                    <p><strong>Email:</strong> <span id="profileEmail"><?php echo htmlspecialchars($student_data['email'] ?? 'Not specified'); ?></span></p>
                                </div>
                            </div>

                            <!-- Student Stats with Minor/Major Separation -->
                            <div class="profile-details">
                                <div class="detail-group">
                                    <h4>Violation Statistics</h4>
                                    
                                    <div class="violation-stats">
                                        <div class="stat-box minor-count">
                                            <div class="stat-value"><?php echo $violation_counts['minor_total']; ?></div>
                                            <div>Minor Violations</div>
                                        </div>
                                        <div class="stat-box major-count">
                                            <div class="stat-value"><?php echo $violation_counts['major_total']; ?></div>
                                            <div>Major Violations</div>
                                        </div>
                                        <div class="stat-box total-count">
                                            <div class="stat-value"><?php echo $violation_counts['total_violations']; ?></div>
                                            <div>Total Offense</div>
                                        </div>
                                    </div>

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
                                </div>
                                
                                <div class="detail-group">
                                    <h4>Recent Violation</h4>
                                    <?php if ($recent_violation): ?>
                                        <p><strong>Type:</strong> <?php echo htmlspecialchars($recent_violation['violation_type']); ?></p>
                                        <p><strong>Category:</strong> 
                                            <span class="violation-category-badge violation-category-<?= $recent_violation['violation_category'] ?>">
                                                <?= strtoupper($recent_violation['violation_category']) ?>
                                            </span>
                                        </p>
                                        <p><strong>Date:</strong> <?php echo date("F j, Y g:i A", strtotime($recent_violation['created_at'])); ?></p>
                                    <?php else: ?>
                                        <p>No violations recorded</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Record Violation Button -->
                            <div class="violation-actions">
                                <button class="btn btn-warning" onclick="recordViolation('<?php echo $student_data['user_id']; ?>')">
                                    📝 Record Violation
                                </button>
                                <button class="btn btn-danger" onclick="viewViolationHistory('<?php echo $student_data['user_id']; ?>')">
                                    📋 View Violation History
                                </button>
                            </div>
                        </div>
                    <?php elseif ($student_id && !$student_data): ?>
                        <!-- Student not found -->
                        <div class="no-student-message">
                            <h3>Student Not Found</h3>
                            <p>Student with ID "<?php echo htmlspecialchars($student_id); ?>" was not found in the database.</p>
                            <button class="btn btn-primary" onclick="showTab('students')">Search Students</button>
                        </div>
                    <?php else: ?>
                        <!-- Show message when no student is selected -->
                        <div class="no-student-message">
                            <h3>No Student Selected</h3>
                            <p>Scan a student QR code or select a student from the Students tab to view their profile.</p>
                            <p>You can also manually enter a student ID:</p>
                            <div style="margin-top: 20px;">
                                <input type="text" id="manualStudentId" placeholder="Enter Student Number" class="form-control" style="width: 200px; display: inline-block; margin-right: 10px;">
                                <button class="btn btn-primary" onclick="loadStudentProfile()">Load Profile</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Handbook Tab -->
                 

                

                <!-- Statistics Tab -->
                <div id="statistics" class="tab-content">
                    <h2>Statistics Overview</h2>
                    <div class="charts-grid">
                        <div class="chart-container">
                            <h3>Violations by Category</h3>
                            <canvas id="violationsCategoryChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3>Violations Trend (Last 12 Months)</h3>
                            <canvas id="violationsTrendChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3>Violations by Course</h3>
                            <canvas id="violationsCourseChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3>Sanctions Status</h3>
                            <canvas id="sanctionsStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <!-- Sanction Assignment Modal (UPDATED with Calendar in Input) -->
<div id="sanctionModal" class="modal">
    <div class="modal-content">
        <h2>Assign Sanction</h2>
        <form id="sanctionForm" enctype="multipart/form-data">
            <input type="hidden" id="sanctionViolationId">
            <div class="form-group">
                <label>Student Number</label>
                <input type="text" class="form-control" id="sanctionStudentNumber" required readonly 
                       style="background-color: #f5f5f5; cursor: not-allowed;">
            </div>
            <div class="form-group">
                <label>Sanction Type</label>
                <select class="form-control" id="sanctionType" required>
                    <option value="">Select Sanction</option>
                    <optgroup label="Minor Offenses">
                        <option value="verbal_reprimand">1st Offense: Verbal Reprimand</option>
                        <option value="written_warning_1">1st Offense: Written Warning</option>
                        <option value="written_warning_2">2nd Offense: Written Warning + 3hrs Community Service</option>
                        <option value="written_warning_3">3rd Offense: Written Warning + 6hrs Community Service + Counseling</option>
                    </optgroup>
                    <optgroup label="Major Offenses">
                        <option value="suspension_6_days">A: Suspension for 6 Days</option>
                        <option value="suspension_10_20_days">B: Suspension for 10-20 Days</option>
                        <option value="non_readmission">C: Non-readmission to the College</option>
                        <option value="dismissal">D: Dismissal from the College</option>
                        <option value="expulsion">E: Expulsion</option>
                        <!-- REMOVED: "Mandatory Counseling" and "Extended Community Service" -->
                    </optgroup>
                </select>
            </div>
            <div class="form-group">
                <label>Due Date *</label>
                <div class="input-group">
                    <input type="date" class="form-control" id="sanctionDueDate" required 
                           min="2025-01-01" max="2026-12-31"
                           style="padding-right: 40px;">
               
                </div>
                <small class="form-text text-muted">Click the calendar icon or field. Only years 2025-2026 allowed.</small>
            </div>
            <div class="form-group">
                <label>Notes (Optional)</label>
                <textarea class="form-control" id="sanctionNotes" rows="3" placeholder="Additional notes..."></textarea>
            </div>
            <!-- ADDED: Violation Proof Upload (Optional) -->
            <div class="form-group">
                <label>Violation Proof (Optional)</label>
                <input type="file" class="form-control" id="violationProof" name="violationProof" 
                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                <small class="form-text text-muted">Optional: Upload evidence of violation (Max 5MB)</small>
            </div>
            <button type="submit" class="btn btn-primary">Assign Sanction</button>
            <button type="button" class="btn btn-danger" onclick="closeModal('sanctionModal')">Cancel</button>
        </form>
    </div>
</div>


    <!-- Sanction Proof Upload Modal -->
    <div id="proofModal" class="modal">
        <div class="modal-content">
            <h2>Complete Sanction</h2>
            <form id="proofForm">
                <input type="hidden" id="proofSanctionId">
                <div class="form-group">
                    <label>Completion Evidence (Optional)</label>
                    <input type="file" class="form-control" id="completionProof" accept="image/*,.pdf,.doc,.docx">
                    <small>Optional: Upload proof of completion</small>
                </div>
                <div class="form-group">
                    <label>Completion Date</label>
                    <input type="date" class="form-control" id="completionDate" required>
                </div>
                <div class="form-group">
                    <label>Counselor Notes</label>
                    <textarea class="form-control" id="counselorNotes" rows="3" placeholder="Counselor assessment and notes..."></textarea>
                </div>
                <div class="form-group">
                    <label>Hours Completed or Number of Days</label>
                    <input type="number" class="form-control" id="hoursCompleted" min="0" step="0.5" placeholder="e.g., 3.0">
                </div>
                <button type="submit" class="btn btn-primary">Complete Sanction</button>
                <button type="button" class="btn btn-danger" onclick="closeModal('proofModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Case Details Modal -->
    <div id="caseModal" class="modal">
        <div class="modal-content">
            <h2>Case Details</h2>
            <div id="caseDetailsContent">
                <!-- Case details will be loaded here via AJAX -->
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal('caseModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Violation History Modal -->
    <div id="violationHistoryModal" class="modal">
        <div class="modal-content">
            <h2>Violation History</h2>
            <div id="violationHistoryContent">
                <!-- Violation history will be loaded here via AJAX -->
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal('violationHistoryModal')">Close</button>
            </div>
        </div>
    </div>
           <!-- Sanction Details Modal -->
    <div id="viewSanctionModal" class="modal">
        <div class="modal-content">
            <h2>Sanction Details</h2>
            <div id="sanctionDetailsContent">
                <!-- Sanction details will be loaded here via AJAX -->
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal('viewSanctionModal')">Close</button>
            </div>
        </div>
    </div>                     
    <script src="admin_scripts.js"></script>
    
</body>
</html>

    <div id="violationHistoryModal" class="modal">
        <div class="modal-content">
            <h2>Violation History</h2>
            <div id="violationHistoryContent">
                <!-- Violation history will be loaded here via AJAX -->
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal('violationHistoryModal')">Close</button>
            </div>
        </div>
    </div>
           <!-- Sanction Details Modal -->
    <div id="viewSanctionModal" class="modal">
        <div class="modal-content">
            <h2>Sanction Details</h2>
            <div id="sanctionDetailsContent">
                <!-- Sanction details will be loaded here via AJAX -->
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="button" class="btn btn-danger" onclick="closeModal('viewSanctionModal')">Close</button>
            </div>
        </div>
    </div>       
                  
    <script src="admin_scripts.js">
        
    </script>
    <?php include 'footer.php'; ?>        
</body>
</html>