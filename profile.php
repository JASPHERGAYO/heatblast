<?php
session_start();
include 'database.php';

// Enable SQL error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ---------------------------------------------------
   CHECK IF ADMIN OR STAFF IS LOGGED IN
--------------------------------------------------- */


$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isStaff = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;
$admin = null;
$staff = null;

// If admin is logged in and viewing their own profile, redirect to admin dashboard
if ($isAdmin && !isset($_GET['id'])) {
    header("Location: admin_db.php");
    exit();
}
if ($isAdmin) {
    // Since you're using hardcoded admin login, create admin data manually
    $admin = [
        'fullname' => 'Administrator',
        'position' => 'System Administrator', 
        'building_number' => 'Main Building',
        'phone' => 'N/A',
        'sex' => 'N/A',
        'member_since' => date('Y-m-d'),
        'qr_code' => 'default_qr.png'
    ];
}


if ($isStaff) {
    $staff_email = $_SESSION['staff_email'] ?? 'staff@kld.edu.ph';
    $stf = $conn->prepare("SELECT * FROM staff WHERE email=? LIMIT 1");
    $stf->bind_param("s", $staff_email);
    $stf->execute();
    $staff = $stf->get_result()->fetch_assoc();
}

/* ---------------------------------------------------
   STUDENT PROFILE DATA
--------------------------------------------------- */
$user_id = null;
$profile = null;
$recent_violation = null;
$vio_total = 0;
$clear_percent = 100;

// VIOLATION COUNTS - ADDED MINOR/MAJOR SEPARATION
$minor_total = 0;
$major_total = 0;
$converted_majors = 0;
$remaining_minors = 0;
$effective_major_total = 0;

// Load student profile data if:
// 1. Student is logged in OR 
// 2. Admin/Staff is viewing a student profile via ?id= parameter
if ((!$isAdmin && !$isStaff) || (($isAdmin || $isStaff) && isset($_GET['id']))) {
    
    if ((!$isAdmin && !$isStaff) && isset($_SESSION['user_id'])) {
        // Student viewing their own profile
        $user_id = $_SESSION['user_id'];
    } else if (isset($_GET['id'])) {
        // Admin/Staff viewing student profile via ?id= parameter
        $user_id = intval($_GET['id']);
    }

    if (!$user_id) die("Profile not found.");

    // Load student profile data
    $q = $conn->prepare("
        SELECT sp.*, u.email, u.created_at 
        FROM student_profiles sp
        JOIN users u ON sp.user_id = u.id
        WHERE sp.user_id = ?
        LIMIT 1
    ");
    $q->bind_param("i", $user_id);
    $q->execute();
    $profile = $q->get_result()->fetch_assoc();

    if (!$profile) die("Profile does not exist.");

    // Load violation data WITH CATEGORY SEPARATION - ONLY PENDING VIOLATIONS
    $viol_q = $conn->prepare("
        SELECT violation_type, violation_category, created_at 
        FROM violations 
        WHERE user_id = ? AND status = 'pending'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $viol_q->bind_param("i", $user_id);
    $viol_q->execute();
    $recent_violation = $viol_q->get_result()->fetch_assoc();

    // Count violations by category - ONLY PENDING VIOLATIONS
 // Count violations by category - ACTIVE violations (pending OR under_review)
$minor_count_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'minor' AND status IN ('pending', 'under_review')");
$minor_count_q->bind_param("i", $user_id);
$minor_count_q->execute();
$minor_total = $minor_count_q->get_result()->fetch_assoc()['total'];

$major_count_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'major' AND status IN ('pending', 'under_review')");
$major_count_q->bind_param("i", $user_id);
$major_count_q->execute();
$major_total = $major_count_q->get_result()->fetch_assoc()['total'];

    // Calculate converted majors (every 4 minors = 1 major)
    $converted_majors = floor($minor_total / 4);
    $effective_major_total = $major_total + $converted_majors;
    $remaining_minors = $minor_total % 4;

    // Total violations count - ONLY PENDING
    $vio_total = $minor_total + $major_total;
    $clear_percent = max(0, 100 - ($vio_total * 10));
}

    // Helper to normalize QR image src paths so templates don't double-prefix
   function normalize_qr_src($qr) {
    // If empty or null, use default
    if (empty($qr) || $qr === null || $qr === '') {
        return 'qrcodes/default_qr.png';
    }
    
    // Already has full URL (unlikely but handle it)
    if (strpos($qr, 'http://') === 0 || strpos($qr, 'https://') === 0 || strpos($qr, 'data:image') === 0) {
        return $qr;
    }
    
    // Already has qrcodes/ prefix
    if (strpos($qr, 'qrcodes/') === 0) {
        return $qr;
    }
    
    // Has pattern like qr_user_42.png (most common case based on your debug output)
    if (strpos($qr, 'qr_user_') === 0 && strpos($qr, '.png') !== false) {
        return 'qrcodes/' . $qr;
    }
    
    // Has .png extension but missing qrcodes/ prefix
    if (strpos($qr, '.png') !== false) {
        return 'qrcodes/' . $qr;
    }
    
    // Anything else, add qrcodes/ prefix
    return 'qrcodes/' . ltrim($qr, '/');
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>
        <?php 
            if ($isAdmin && !isset($_GET['id'])) {
                // Admin viewing their own profile
                echo htmlspecialchars($admin['fullname']) . " (Admin)";
            } else if ($isStaff && !isset($_GET['id'])) {
                // Staff viewing their own profile
                echo htmlspecialchars($staff['fullname'] ?? 'Staff') . " (Staff)";
            } else if (($isAdmin || $isStaff) && isset($_GET['id']) && $profile) {
                // Admin/Staff viewing student profile
                echo htmlspecialchars($profile['surname'] . ', ' . $profile['firstname'] . "'s Profile");
            } else if (!$isAdmin && !$isStaff && $profile) {
                // Student viewing their own profile
                echo htmlspecialchars($profile['firstname']) . "'s Profile";
            } else {
                echo "Profile";
            }
        ?>
    </title>

    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    width: 100%;
}
        .profile-btn, .logout-btn, .action-btn {
            background-color: #2e7d32 !important;
            color: #000 !important;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            text-align: center;
        }

        .profile-btn:hover, .logout-btn:hover, .action-btn:hover {
            background-color: #1b5e20 !important;
            color: #fff !important;
        }

        .btn-danger {
            background-color: #e74c3c !important;
        }

        .btn-danger:hover {
            background-color: #c0392b !important;
        }

        .btn-primary {
            background-color: #3498db !important;
        }

        .btn-primary:hover {
            background-color: #2980b9 !important;
        }

        .resources-section {
            margin-top: 20px;
        }

        .resources-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin: 15px 0;
        }

        .resource-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #2e7d32;
        }

        .resource-item i {
            margin-right: 10px;
            color: #2e7d32;
            width: 20px;
        }

        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 15px 0;
        }

        .action-btn {
            background: #e8f5e8 !important;
            border: 1px solid #2e7d32;
            color: #000;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #2e7d32 !important;
            color: white;
        }

        .reminders-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }

        .reminders-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .admin-info-box {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }

        .staff-info-box {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }

        /* NEW STYLES FOR VIOLATION COUNTS */
        .stats-box {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }

        .stats-box div {
            flex: 1;
            padding: 10px;
        }

        .stats-box h2 {
            margin: 0;
            font-size: 2em;
            color: #2c3e50;
            font-weight: bold;
        }

        .stats-box p {
            margin: 5px 0 0 0;
            font-size: 0.9em;
            color: #7f8c8d;
            font-weight: 600;
        }

        .stats-box .minor-count h2 {
            color: #f39c12; /* Orange for minor */
        }

        .stats-box .major-count h2 {
            color: #e74c3c; /* Red for major */
        }

        .stats-box .total-count h2 {
            color: #2c3e50; /* Dark for total */
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
        /* RESPONSIVE PROFILE */
        /* ================= RESPONSIVE STYLES FOR PROFILE ================= */

/* Large Tablets and Small Laptops (1024px and below) */
@media (max-width: 1024px) {
    .profile-container {
        padding: 20px;
    }
    
    .profile-grid {
        grid-template-columns: 1fr !important;
        gap: 25px;
    }
    
    .left-card, .right-card {
        width: 100%;
        margin-bottom: 20px;
    }
    
    .stats-box {
        padding: 20px;
    }
    
    .stats-box h2 {
        font-size: 1.8em;
    }
    
    h1 {
        font-size: 2em;
        line-height: 1.3;
    }
}

/* Tablets (768px and below) */
@media (max-width: 768px) {
    .profile-container {
        padding: 15px;
    }
    
    .left-card, .right-card {
        padding: 20px;
        margin-bottom: 15px;
    }
    
    .qr-img {
        width: 180px;
        height: 180px;
    }
    
    .stats-box {
        flex-direction: column;
        gap: 15px;
    }
    
    .stats-box div {
        flex: none;
        width: 100%;
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .stats-box div:last-child {
        border-bottom: none;
    }
    
    .stats-box h2 {
        font-size: 2em;
    }
    
    .info-list p {
        padding: 10px 0;
        font-size: 0.95em;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
        padding: 15px;
    }
    
    .resources-grid {
        grid-template-columns: 1fr;
    }
    
    .resource-item {
        padding: 15px;
    }
}

/* Mobile Phones (480px and below) */
@media (max-width: 480px) {
    .profile-container {
        padding: 10px;
    }
    
    h1 {
        font-size: 1.6em;
        margin-bottom: 15px;
        text-align: center;
    }
    
    p {
        font-size: 0.9em;
        text-align: center;
    }
    
    .left-card, .right-card {
        padding: 15px;
        border-radius: 8px;
    }
    
    h3 {
        font-size: 1.2em;
        margin-bottom: 15px;
    }
    
    .qr-img {
        width: 150px;
        height: 150px;
    }
    
    .info-list p {
        flex-direction: column;
        align-items: flex-start;
        padding: 8px 0;
    }
    
    .info-list strong {
        display: block;
        margin-bottom: 3px;
        width: 100%;
    }
    
    .stats-box {
        padding: 15px;
        margin: 15px 0;
    }
    
    .stats-box h2 {
        font-size: 1.8em;
    }
    
    .stats-box p {
        font-size: 0.85em;
    }
    
    .conversion-warning, 
    .critical-warning,
    .admin-info-box,
    .staff-info-box {
        padding: 12px;
        font-size: 0.85em;
        margin: 12px 0;
    }
    
    .extra-list li,
    .reminders li,
    .info-ul li {
        padding: 8px 0;
        font-size: 0.9em;
    }
    
    .recent-viol-box {
        padding: 15px;
    }
    
    .violation-category-badge {
        font-size: 0.7em;
        padding: 2px 6px;
        margin-left: 5px;
    }
    
    .action-btn {
        padding: 12px;
        font-size: 0.9em;
    }
}

/* Small Mobile Phones (320px and below) */
@media (max-width: 320px) {
    .profile-container {
        padding: 8px;
    }
    
    h1 {
        font-size: 1.4em;
    }
    
    .left-card, .right-card {
        padding: 12px;
    }
    
    .qr-img {
        width: 130px;
        height: 130px;
    }
    
    .info-list p {
        font-size: 0.85em;
        padding: 6px 0;
    }
    
    .stats-box h2 {
        font-size: 1.6em;
    }
    
    .stats-box p {
        font-size: 0.8em;
    }
    
    h3 {
        font-size: 1.1em;
    }
    
    .action-btn {
        padding: 10px;
        font-size: 0.85em;
    }
}

/* Landscape Mode for Mobile */
@media (max-height: 600px) and (orientation: landscape) {
    .profile-container {
        padding: 10px;
    }
    
    .left-card, .right-card {
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .qr-img {
        width: 120px;
        height: 120px;
    }
    
    .info-list {
        font-size: 0.9em;
    }
}

/* Print Styles */
@media print {
    .profile-container {
        width: 100%;
        margin: 0;
        padding: 0;
    }
    
    .profile-grid {
        grid-template-columns: 1fr !important;
    }
    
    .qr-img {
        width: 150px !important;
        height: 150px !important;
        filter: grayscale(100%);
    }
    
    .action-btn, 
    .open-calendar-btn,
    .resource-item,
    .quick-actions {
        display: none !important;
    }
    
    h1, h3 {
        color: black !important;
    }
    
    .left-card, .right-card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
}

/* Touch Device Improvements */
@media (hover: none) and (pointer: coarse) {
    .action-btn,
    .resource-item,
    .violation-option {
        min-height: 44px;
    }
    
    .qr-img {
        cursor: pointer;
    }
    
    .info-list p {
        padding: 12px 0;
    }
    
    /* Increase tap targets */
    .extra-list li a,
    .reminders li {
        padding: 12px 0;
    }
}

    </style>
</head>

<body>

<?php include 'nav.php'; ?>

<div class="profile-container">

<!-- =======================
      ADMIN VIEWING THEIR OWN PROFILE
=========================== -->
<?php if ($isAdmin && !isset($_GET['id'])): ?>
    
    <h1>Welcome back, <?= htmlspecialchars($admin['fullname']); ?> (Admin)</h1>
    <p>Manage violations, oversee users, and access the admin dashboard.</p>

    <div class="profile-grid">

        <!-- LEFT ADMIN CARD -->
        <div class="left-card">

            <h3>🛡 Admin Profile</h3>

            <img src="<?= htmlspecialchars($admin['qr_code'] ?? 'qrcodes/default_qr.png'); ?>" class="qr-img">

            <p class="qr-caption">Admin QR Code</p>

            <div class="info-list">
                <p><strong>Full Name:</strong> <?= htmlspecialchars($admin['fullname']); ?></p>
                <p><strong>Position:</strong> <?= htmlspecialchars($admin['position']); ?></p>
                <p><strong>Building #:</strong> <?= htmlspecialchars($admin['building_number']); ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($admin['phone']); ?></p>
                <p><strong>Sex:</strong> <?= htmlspecialchars($admin['sex']); ?></p>
                <p><strong>Member Since:</strong> <?= htmlspecialchars($admin['member_since']); ?></p>
            </div>

            <!-- ADMIN QUICK ACTIONS -->
            <h3>🛠 Quick Actions</h3>
            <div class="quick-actions">
                <a href="download_qr.php" class="action-btn">
                    <i class="fas fa-download"></i>
                    Download QR Code
                </a>
                <a href="insert_violation.php" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    Insert Violation
                </a>
                <a href="admin_dashboard.php" class="action-btn">
                    <i class="fas fa-tachometer-alt"></i>
                    Admin Dashboard
                </a>
                <a href="admin_db.php" class="action-btn">
                    <i class="fas fa-user-shield"></i>
                    Admin Portal
                </a>
                <a href="logout.php" class="action-btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>

        </div>

        <!-- RIGHT ADMIN FEATURES -->
        <div class="right-card">

            <h3>⚙️ Admin Tools</h3>

            <ul class="extra-list">
                <li><a href="violations_list.php">📄 View All Violations</a></li>
                <li><a href="manage_students.php">👥 Manage Students</a></li>
                <li><a href="system_settings.php">🛠 System Settings</a></li>
            </ul>

            <!-- STUDENT RESOURCES SECTION -->
            <div class="resources-section">
                <h3>📚 Student Resources</h3>
                <div class="resources-grid">
          
                        <a href="student_resources/KLD_Student_Manual_Violations_Only.pdf" class="resource-item" target="_blank">
                            <i class="fas fa-book"></i>
                            <span>Student Handbook (PDF)</span>
                        </a>

                   <!-- Add this to your resources section where you want the calendar button -->
<a href="#" class="resource-item open-calendar-btn">
    <i class="fas fa-calendar-alt"></i>
    <span>School Calendar</span>
</a>
                
                </div>
            </div>

            <h3>📌 Admin Reminders</h3>
            <ul class="reminders">
                <li>• Always verify student identity before recording violations</li>
                <li>• Maintain fairness and accuracy in reports</li>
                <li>• Report system issues immediately</li>
            </ul>

        </div>

    </div>

<!-- =======================
      STAFF VIEWING THEIR OWN PROFILE
=========================== -->
<?php elseif ($isStaff && !isset($_GET['id'])): ?>
    
    <h1>Welcome back, <?= htmlspecialchars($staff['fullname'] ?? 'Staff Member'); ?> (Staff)</h1>
    <p>Record student violations and manage student profiles.</p>

    <div class="profile-grid">

        <!-- LEFT STAFF CARD -->
        <div class="left-card">

            <h3>👨‍💼 Staff Profile</h3>

            <img src="<?= htmlspecialchars($staff['qr_code'] ?? 'qrcodes/default_qr.png'); ?>" class="qr-img">

            <p class="qr-caption">Staff QR Code</p>

            <div class="info-list">
                <p><strong>Full Name:</strong> <?= htmlspecialchars($staff['fullname'] ?? 'N/A'); ?></p>
                <p><strong>Position:</strong> <?= htmlspecialchars($staff['position'] ?? 'N/A'); ?></p>
                <p><strong>Department:</strong> <?= htmlspecialchars($staff['department'] ?? 'N/A'); ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($staff['email'] ?? 'N/A'); ?></p>
                <p><strong>Member Since:</strong> <?= htmlspecialchars($staff['created_at'] ?? 'N/A'); ?></p>
            </div>

            <!-- STAFF QUICK ACTIONS -->
            <h3>🛠 Quick Actions</h3>
            <div class="quick-actions">
                <a href="download_qr.php" class="action-btn">
                    <i class="fas fa-download"></i>
                    Download QR Code
                </a>
                <a href="insert_violation.php" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    Record Violation
                </a>
                <a href="staff_db.php" class="action-btn">
                    <i class="fas fa-tachometer-alt"></i>
                    Staff Portal
                </a>
                <a href="logout.php" class="action-btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>

        </div>

        <!-- RIGHT STAFF FEATURES -->
        <div class="right-card">

            <h3>⚙️ Staff Tools</h3>

            <ul class="extra-list">
                <li><a href="scan_qr.php">📱 Scan Student QR</a></li>
                <li><a href="my_violations.php">📄 My Recorded Violations</a></li>
                <li><a href="student_search.php">🔍 Search Students</a></li>
            </ul>

            <div class="resources-section">
                <h3>📚 Student Resources</h3>
                <div class="resources-grid">

                        <a href="student_resources/KLD_Student_Manual_Violations_Only.pdf" class="resource-item" target="_blank">
                        <i class="fas fa-book"></i>
                        <span>Student Handbook (PDF)</span>
                    </a>
 
                    <!-- Add this to your resources section where you want the calendar button -->
<a href="#" class="resource-item open-calendar-btn">
    <i class="fas fa-calendar-alt"></i>
    <span>School Calendar</span>
</a>
                    
                </div>
            </div>

            <h3>📌 Staff Reminders</h3>
            <ul class="reminders">
                <li>• Always scan student QR codes before recording violations</li>
                <li>• Verify student information matches the profile</li>
                <li>• Be accurate and fair in violation reporting</li>
            </ul>

        </div>

    </div>

<?php else: ?>

<!-- =======================
      STUDENT VIEW (or Admin/Staff viewing student profile)
=========================== -->
    <?php if (!$profile): ?>
        <div class="error-message">Profile not found.</div>
    <?php else: ?>
        <?php if (($isAdmin || $isStaff) && isset($_GET['id'])): ?>
            <!-- Admin/Staff viewing student profile -->
            <h1><?= htmlspecialchars($profile['surname'] . ', ' . $profile['firstname'] . ' ' . $profile['middle_initial'] . '.'); ?>'s Profile</h1>
            <p>Student Profile - <?= $isAdmin ? 'Admin' : 'Staff' ?> View</p>
            
            <!-- Admin/Staff info box -->
            <div class="<?= $isAdmin ? 'admin-info-box' : 'staff-info-box' ?>">
                <h4>👮 <?= $isAdmin ? 'Admin' : 'Staff' ?> Mode</h4>
                <p>You are viewing this student's profile as <?= $isAdmin ? 'an administrator' : 'staff' ?>.</p>
                <p><strong>Scanned Student ID:</strong> <?= $user_id ?></p>
            </div>
           
        <?php else: ?>
            <!-- Student viewing their own profile -->
            <h1>Welcome back, <?= htmlspecialchars($profile['firstname']); ?>!</h1>
            <p>Manage your profile and access your QR code for violation recording</p>
        <?php endif; ?>

        <div class="profile-grid">

            <!-- LEFT CARD -->
            <div class="left-card">

                <h3>📁 Student Profile</h3>

                <img src="<?= htmlspecialchars(normalize_qr_src($profile['qr_code'])); ?>" class="qr-img">
                <p class="qr-caption">Student QR Code</p>

                <!-- UPDATED STATS BOX WITH MINOR/MAJOR SEPARATION - ONLY PENDING VIOLATIONS -->
                <div class="stats-box">
                    <div class="minor-count">
                        <h2><?= $minor_total; ?></h2>
                        <p>Minor Offense</p>
                    </div>
                    <div class="major-count">
                        <h2><?= $major_total; ?></h2>
                        <p>Major Offense</p>
                    </div>
                    <div class="total-count">
                        <h2><?= $vio_total; ?></h2>
                        <p>Total Offense</p>
                    </div>
                </div>

                <!-- VIOLATION CONVERSION WARNINGS -->
                <?php if ($converted_majors > 0): ?>
                <div class="conversion-warning">
                    <strong>⚠️ Automatic Conversion:</strong><br>
                    <?= $converted_majors ?> set(s) of 4 minor violations converted to major offense(s)
                </div>
                <?php endif; ?>

                <?php if ($remaining_minors == 3): ?>
                <div class="critical-warning">
                    <strong>🚨 CRITICAL WARNING:</strong><br>
                    Next minor violation will be automatically converted to a MAJOR offense!
                </div>
                <?php elseif ($remaining_minors == 2): ?>
                <div class="conversion-warning">
                    <strong>⚠️ Warning:</strong><br>
                    2 more minor violations will trigger automatic major offense
                </div>
                <?php endif; ?>

                <div class="recent-viol-box">
                    <p class="label">Last Pending Violation:</p>

                    <?php if ($recent_violation): ?>
                        <p class="viol-name">
                            <?= htmlspecialchars($recent_violation['violation_type']); ?>
                            <span class="violation-category-badge violation-category-<?= $recent_violation['violation_category'] ?>">
                                <?= strtoupper($recent_violation['violation_category']) ?>
                            </span>
                        </p>
                        <p class="viol-date"><?= date("F j, Y g:i A", strtotime($recent_violation['created_at'])); ?></p>
                    <?php else: ?>
                        <p>No pending violations</p>
                    <?php endif; ?>
                </div>

                <!-- STUDENT QUICK ACTIONS -->
                <h3>🛠 Quick Actions</h3>
                <div class="quick-actions">
                    <?php if ($isAdmin && isset($_GET['id'])): ?>
                        <!-- Admin viewing student profile -->
                        <a href="insert_violation.php?id=<?= $user_id ?>" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                            Record Violation
                        </a>
                        <a href="admin_db.php?id=<?= $user_id ?>" class="action-btn">
                            <i class="fas fa-arrow-left"></i>
                            Back to Admin Portal
                        </a>
                    <?php elseif ($isStaff && isset($_GET['id'])): ?>
                        <!-- Staff viewing student profile -->
                        <a href="insert_violation.php?id=<?= $user_id ?>" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                            Record Violation
                        </a>
                        <a href="staff_db.php?student_id=<?= $user_id ?>" class="action-btn">
                            <i class="fas fa-arrow-left"></i>
                            Back to Staff Portal
                        </a>
                    <?php else: ?>
                        <!-- Student viewing own profile -->
                        <a href="download_qr.php" class="action-btn">
                            <i class="fas fa-download"></i>
                            Download QR Code
                        </a>
                        <a href="change_password.php" class="action-btn">
                            <i class="fas fa-key"></i>
                            Change Password
                        </a>
                        <a href="logout.php" class="action-btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    <?php endif; ?>
                </div>

            </div>

            <!-- RIGHT STUDENT CARD -->
            <div class="right-card">

                <h3>📘 Account Details</h3>

                <div class="info-list">

                    <p><strong>Full Name:</strong>
                        <?= htmlspecialchars($profile['surname'] . ', ' . $profile['firstname'] . ' ' . $profile['middle_initial'] . '.'); ?>
                    </p>

                    <p><strong>Email Address:</strong>
                        <?= htmlspecialchars($profile['email']); ?>
                    </p>

                    <p><strong>Section:</strong>
                        <?= htmlspecialchars($profile['course'] . " " . $profile['section']); ?>
                    </p>

                    <p><strong>Year Level:</strong>
                        <?= htmlspecialchars($profile['year_level'] ?? 'Not specified'); ?>
                    </p>

                    <p><strong>Student Number:</strong>
                        <?= htmlspecialchars($profile['student_number']); ?>
                    </p>

                    <p><strong>Sex:</strong>
                        <?= htmlspecialchars($profile['sex']); ?>
                    </p>

                    <p><strong>Member Since:</strong>
                        <?= date("F j, Y", strtotime($profile['created_at'])); ?>
                    </p>

                </div>

                <h3>ℹ️ About Your QR Code</h3>

                <ul class="info-ul">
                    <li>Your QR code is unique to you and secure</li>
                    <li>Authorized personnel can scan to record violations</li>
                    <li>Your personal information is protected</li>
                </ul>

                <!-- STUDENT RESOURCES SECTION -->
                <div class="resources-section">
                    <h3>📚 Student Resources</h3>
                    <div class="resources-grid">
           
                           <a href="student_resources/KLD_Student_Manual_Violations_Only.pdf" class="resource-item" target="_blank">
                            <i class="fas fa-book"></i>
                            <span>Student Handbook (PDF)</span>
                        </a>

                      <!-- Add this to your resources section where you want the calendar button -->
<a href="#" class="resource-item open-calendar-btn">
    <i class="fas fa-calendar-alt"></i>
    <span>School Calendar</span>
</a>
                        
                    </div>
                </div>

            </div>

        </div>
    <?php endif; ?>

<?php endif; ?>

</div>


<!-- Add this modal at the bottom of the page, before the closing body tag -->
<div id="calendarModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
    <div style="background: white; padding: 25px; border-radius: 10px; width: 95%; max-width: 1200px; max-height: 90vh; overflow-y: auto; position: relative;">
        <!-- Close button -->
        <button onclick="closeCalendar()" style="position: absolute; top: 15px; right: 15px; background: #e74c3c; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 14px; z-index: 10;">
            <i class="fas fa-times"></i> Close
        </button>
        
        <!-- Calendar Content -->
        <div style="margin-bottom: 20px;">
            <h1 style="color: #2e7d32; margin-bottom: 5px;">Academic Calendar A Y 2023 – 2024</h1>
            <p style="color: #666; margin-top: 0;"><strong>Main Page > Academic Calendar</strong></p>
        </div>
        
        <!-- Calendar Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead style="background: #2e7d32; color: white;">
                    <tr>
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">NAME OF EVENTS</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">FIRST TERM</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">SECOND TERM</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">MIDYEAR TERM</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Enrollment Period -->
                    <tr style="background: #f8f9fa;">
                        <td colspan="4" style="padding: 10px; font-weight: bold; border: 1px solid #ddd;">Enrollment Period</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; padding-left: 30px;">1st Year</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 3 – August 9</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 12</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 14</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; padding-left: 30px;">2nd Year</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 17 – July 31</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 11</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 13</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; padding-left: 30px;">3rd Year</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 24 – July 31</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 10</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 11</td>
                    </tr>
                    
                    <!-- Late Enrollment -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Late Enrollment (All Year Levels)</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 11</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 16</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 16</td>
                    </tr>
                    
                    <!-- Faculty Integration Day -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Faculty Integration Day</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 16</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 2</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 20</td>
                    </tr>
                    
                    <!-- Pre-Opening Activities -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Pre – Opening Activities (Faculty)</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 17 – 18</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 8</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 17</td>
                    </tr>
                    
                    <!-- Start of Classes -->
                    <tr style="background: #e8f5e8;">
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Start of Classes</td>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">August 22</td>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">January 15</td>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">June 10</td>
                    </tr>
                    
                    <!-- Adding/Dropping -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Adding/Dropping of Subjects</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 23-24</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 16 – 17</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 19</td>
                    </tr>
                    
                    <!-- Freshmen Onboarding -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Freshmen Students' Onboarding</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 24-25</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                    </tr>
                    
                    <!-- Last day of withdrawal -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Last day of withdrawal of enlistment</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 25</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 18</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 20</td>
                    </tr>
                    
                    <!-- Graduation Application Deadline -->
                    <tr style="background: #f8f9fa;">
                        <td colspan="4" style="padding: 10px; font-weight: bold; border: 1px solid #ddd;">Deadline for Students to file application for graduation</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; padding-left: 30px;">Midyear Term 2023</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 17</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; padding-left: 30px;">1st Term AY 2023 – 2024</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 17</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; padding-left: 30px;">2nd Term AY 2023 – 2024</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 12</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                    </tr>
                    
                    <!-- Submission of Term Grades -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Submission of Term Grade for the College Registrar</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">December 29</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">May 31</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 26</td>
                    </tr>
                    
                    <!-- Release of Official Grades -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Release of Official Grades</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 9</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 10</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 5</td>
                    </tr>
                    
                    <!-- KLD Foundation days -->
                    <tr style="background: #e8f5e8;">
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">KLD Foundation days</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">September 18 – 22</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                    </tr>
                    
                    <!-- Midterm Examination -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Midterm Examination (Departmental)</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">October 9 – 12</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">March 11-16</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 27-28</td>
                    </tr>
                    
                    <!-- Institute Days/College Fair -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Institute Days/College Fair / Sports Week</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">November 23-24</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">March 21-22</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                    </tr>
                    
                    <!-- Alternative Classroom Exercises -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Alternative Classroom Exercises</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">October 20</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">April 8</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 28</td>
                    </tr>
                    
                    <!-- Commencement Exercises -->
                    <tr style="background: #f0f8ff;">
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Commencement Exercises</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 30</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 26</td>
                    </tr>
                    
                    <!-- Final Examination -->
                    <tr style="background: #fff3cd;">
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Final Examination (Departmental)</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">December 11-16</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">May 13-18</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 19</td>
                    </tr>
                    
                    <!-- Removal Examination Period -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Removal Examination Period</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">December 18-20</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">May 21-23</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 22</td>
                    </tr>
                    
                    <!-- Consultation of Grades -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Consultation of Grades</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">December 18</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">May 20</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 23</td>
                    </tr>
                    
                    <!-- Christmas/Lenten Break -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Christmas Break / Lenten Break (for Students Only)</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">December 19</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">March 25-31</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">-</td>
                    </tr>
                    
                    <!-- Academic Affairs Council -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Academic Affairs Council (Graduation and Academic Appeals)</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 23</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">February 7</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 17</td>
                    </tr>
                    
                    <!-- End of Classes -->
                    <tr style="background: #f8d7da;">
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">End of Classes</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">December 20</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">May 21</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 24</td>
                    </tr>
                    
                    <!-- Header for Institutes and Committees -->
                    <tr style="background: #2e7d32; color: white;">
                        <td colspan="4" style="padding: 15px; text-align: center; border: 1px solid #ddd; font-size: 16px;">
                            DATES TO REMEMBER FOR INSTITUTES AND COMMITTEES
                        </td>
                    </tr>
                    
                    <!-- Change Grade Deadline -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Deadline of Change Grade</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 5</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">June 5</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 5</td>
                    </tr>
                    
                    <!-- CSAPG Meetings -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Committee on Student Admission, Progress and Graduation (CSAPG) Meetings</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">September 6</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">January 17</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">May 8</td>
                    </tr>
                    
                    <!-- Curriculum Committee Meeting -->
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Curriculum Committee Meeting</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">September 15</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">February 21</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">April 17</td>
                    </tr>
                    
                    <!-- Submission of Graduation Candidates -->
                    <tr style="background: #f0f8ff;">
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Submission of Final List of Candidates for Graduation</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 23</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">July 17</td>
                        <td style="padding: 10px; border: 1px solid #ddd;">August 14</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Legend -->
        <div style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 13px;">
            <h4 style="margin-top: 0; color: #2e7d32;">Legend:</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                <div><span style="display: inline-block; width: 15px; height: 15px; background: #e8f5e8; border: 1px solid #ddd; margin-right: 5px;"></span> Important Academic Dates</div>
                <div><span style="display: inline-block; width: 15px; height: 15px; background: #fff3cd; border: 1px solid #ddd; margin-right: 5px;"></span> Examination Periods</div>
                <div><span style="display: inline-block; width: 15px; height: 15px; background: #f0f8ff; border: 1px solid #ddd; margin-right: 5px;"></span> Graduation Related</div>
                <div><span style="display: inline-block; width: 15px; height: 15px; background: #f8d7da; border: 1px solid #ddd; margin-right: 5px;"></span> End of Term</div>
            </div>
        </div>
        
        <!-- Print/Download Button -->
        <div style="margin-top: 20px; text-align: center;">
            <button onclick="printCalendar()" style="background: #2e7d32; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                <i class="fas fa-print"></i> Print Calendar
            </button>
            <button onclick="downloadCalendarPDF()" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-download"></i> Download as PDF
            </button>
        </div>
    </div>
</div>

<script>
// Simple JavaScript for modal functionality
function openCalendar() {
    document.getElementById('calendarModal').style.display = 'flex';
}

function closeCalendar() {
    document.getElementById('calendarModal').style.display = 'none';
}

function printCalendar() {
    alert('Print functionality would open print dialog here');
    // Uncomment for actual print:
    // window.print();
}

function downloadCalendarPDF() {
    alert('PDF download would be implemented here');
    // You would need a PDF generation library or server-side script
}

// Add click event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Listen for calendar button clicks
    const calendarBtns = document.querySelectorAll('.open-calendar-btn');
    calendarBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openCalendar();
        });
    });
    
    // Close modal when clicking outside
    document.getElementById('calendarModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCalendar();
        }
    });
    
    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCalendar();
        }
    });
});

// Add hover effects for table rows
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('#calendarModal tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            if (!this.style.backgroundColor.includes('rgb')) {
                this.style.backgroundColor = '#f0f8ff';
            }
        });
        row.addEventListener('mouseleave', function() {
            if (this.style.backgroundColor === 'rgb(240, 248, 255)') {
                this.style.backgroundColor = '';
            }
        });
    });
});
</script>

<style>
/* Add these styles to your existing CSS */
.open-calendar-btn {
    cursor: pointer;
    text-decoration: none !important;
    color: inherit !important;
    transition: all 0.2s ease;
}

.open-calendar-btn:hover {
    background-color: #e8f5e8 !important;
    transform: translateY(-2px);
}

#calendarModal {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Responsive table */
@media (max-width: 768px) {
    #calendarModal > div {
        width: 98%;
        padding: 15px;
    }
    
    table {
        font-size: 12px;
    }
    
    th, td {
        padding: 6px !important;
    }
}
</style>
<?php include 'footer.php'; ?>
</body>
</html>