<?php
session_start();
require_once 'database.php';

// Include email configuration

$emailConfigPath = 'email_config.php';
if (file_exists($emailConfigPath)) {
    require_once $emailConfigPath;
} else {
    // Try parent directory if not found
    $emailConfigPath = dirname(_DIR_) . '/email_config.php';
    if (file_exists($emailConfigPath)) {
        require_once $emailConfigPath;
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// FULL UPDATED FILE WITH COMPLETE VIOLATIONS
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isStaff = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;
if (!$isAdmin && !$isStaff) { 
    header("Location: login.php"); 
    exit(); 
}

$logged_in_user_id = null; 
$user_type = null; 
$user_name = null;

if ($isAdmin) {
    $user_type = 'admin';
    $admin_email = $_SESSION['admin_email'] ?? 'admin@kld.edu.ph';
    $sql = "SELECT id, surname, firstname, role FROM admins WHERE email = ?";
    $adminQuery = $conn->prepare($sql);
    $adminQuery->bind_param("s", $admin_email);
    $adminQuery->execute();
    $admin = $adminQuery->get_result()->fetch_assoc();
    $logged_in_user_id = $admin['id'] ?? 1;
    $user_name = ($admin['firstname'] ?? 'Administrator') . ' ' . ($admin['surname'] ?? '');
} else if ($isStaff) {
    $user_type = 'staff';
    $staff_email = $_SESSION['staff_email'] ?? '';
    $stf = $conn->prepare("SELECT id, fullname FROM staff WHERE email=? LIMIT 1");
    $stf->bind_param("s", $staff_email);
    $stf->execute();
    $staff = $stf->get_result()->fetch_assoc();
    $logged_in_user_id = $staff['id'] ?? null;
    $user_name = $staff['fullname'] ?? '';
}

$student_id = isset($_GET['id']) ? $_GET['id'] : ($_SESSION['scanned_student_id'] ?? null);
if ($student_id) $_SESSION['scanned_student_id'] = $student_id;
if (!$student_id) die("No student selected.");

$studentQuery = $conn->prepare("SELECT sp.*, u.email FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.user_id = ?");
$studentQuery->bind_param("i", $student_id);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();
if (!$student) die("Student not found.");

$student_name = $student['surname'] . ', ' . $student['firstname'];
if (!empty($student['middle_initial'])) $student_name .= ' ' . $student['middle_initial'] . '.';

// ===================== COUNTING LOGIC WITH CONVERSION TRACKING =====================
// Count ALL active minor violations (pending + under_review) for DISPLAY
$minor_count_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'minor' AND status IN ('pending', 'under_review')");
$minor_count_q->bind_param("i", $student_id);
$minor_count_q->execute();
$current_minors = $minor_count_q->get_result()->fetch_assoc()['total'];

// Count ALL active major violations (pending + under_review) for DISPLAY
$major_count_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'major' AND status IN ('pending', 'under_review')");
$major_count_q->bind_param("i", $student_id);
$major_count_q->execute();
$current_majors = $major_count_q->get_result()->fetch_assoc()['total'];

// ===================== NEW: GET CONVERTED MINOR IDs =====================
// Get all minor IDs that have already been converted
$converted_minor_ids = [];
$tracking_q = $conn->prepare("SELECT converted_violation_ids FROM violation_conversion_tracking WHERE student_id = ?");
$tracking_q->bind_param("i", $student_id);
$tracking_q->execute();
$tracking_result = $tracking_q->get_result();

while ($tracking_row = $tracking_result->fetch_assoc()) {
    if (!empty($tracking_row['converted_violation_ids'])) {
        $ids = explode(',', $tracking_row['converted_violation_ids']);
        $converted_minor_ids = array_merge($converted_minor_ids, $ids);
    }
}

// Count how many minors have NOT been converted yet
$available_minors = 0;
if ($current_minors > 0) {
    if (empty($converted_minor_ids)) {
        // No conversions yet, all minors are available
        $available_minors = $current_minors;
    } else {
        // Count minors that are NOT in the converted list
        $placeholders = str_repeat('?,', count($converted_minor_ids) - 1) . '?';
        $available_q = $conn->prepare("SELECT COUNT(*) as count FROM violations WHERE user_id = ? AND violation_category = 'minor' AND status IN ('pending', 'under_review') AND id NOT IN ($placeholders)");
        
        $params = array_merge([$student_id], $converted_minor_ids);
        $types = str_repeat('i', count($params));
        $available_q->bind_param($types, ...$params);
        $available_q->execute();
        $available_minors = $available_q->get_result()->fetch_assoc()['count'];
    }
}

// Calculate conversion warning based on AVAILABLE minors (not total minors)
$remaining_minors = $available_minors % 4;
$next_conversion = 4 - $remaining_minors;

// Special case: If exactly 0 available minors, need 4 more
if ($remaining_minors == 0) {
    $next_conversion = 4;
}

// For CRITICAL warning: Show when next minor will convert
$show_critical_warning = ($remaining_minors == 3 && $available_minors > 0);

// Also count only pending for display purposes
$pending_minors_only_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'minor' AND status = 'pending'");
$pending_minors_only_q->bind_param("i", $student_id);
$pending_minors_only_q->execute();
$pending_minors_only = $pending_minors_only_q->get_result()->fetch_assoc()['total'];

$pending_majors_only_q = $conn->prepare("SELECT COUNT(*) AS total FROM violations WHERE user_id = ? AND violation_category = 'major' AND status = 'pending'");
$pending_majors_only_q->bind_param("i", $student_id);
$pending_majors_only_q->execute();
$pending_majors_only = $pending_majors_only_q->get_result()->fetch_assoc()['total'];

// ===================== AUTO-CONVERSION LOGIC WITH TRACKING =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $violation_type = trim($_POST['violation_type'] ?? '');
    $violation_category = trim($_POST['violation_category'] ?? 'minor');
    $description = trim($_POST['description'] ?? '');
    $status = 'pending';
    $error = '';

    // Handle file upload - SAME LOGIC FROM assign_sanction.php
    $proof_filename = null;
    $proof_db_path = null;
    if (isset($_FILES['violationProof']) && $_FILES['violationProof']['error'] === UPLOAD_ERR_OK) {
        $proof_tmp = $_FILES['violationProof']['tmp_name'];
        $proof_name = $_FILES['violationProof']['name'];
        $proof_size = $_FILES['violationProof']['size'];
        $proof_ext = strtolower(pathinfo($proof_name, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        if (!in_array($proof_ext, $allowed_ext)) {
            $error = 'Invalid file type. Only JPG, PNG, PDF, DOC, DOCX allowed';
        }
        
        // Validate file size (5MB max)
        if ($proof_size > 5 * 1024 * 1024) {
            $error = 'File size too large. Maximum 5MB allowed';
        }
        
        if (!$error) {
            $proof_filename = 'violation_proof_' . time() . '_' . uniqid() . '.' . $proof_ext;
            $upload_path = 'uploads/violation_proofs/' . $proof_filename;
            $proof_db_path = $upload_path;
            
            // Create directory if it doesn't exist
            if (!file_exists('uploads/violation_proofs')) {
                mkdir('uploads/violation_proofs', 0777, true);
            }
            
            if (!move_uploaded_file($proof_tmp, $upload_path)) {
                error_log("Failed to upload proof file: " . $proof_name);
                $proof_db_path = null;
            }
        }
    } elseif (isset($_FILES['violationProof']) && $_FILES['violationProof']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_errors = [
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.'
        ];
        $error = 'File upload error: ' . ($upload_errors[$_FILES['violationProof']['error']] ?? 'Unknown error');
    }

    if (!$error && $violation_type) {
        $final_category = $violation_category;
        $should_track_conversion = false;
        $converted_ids = [];
        
        if ($violation_category === 'minor') {
            error_log("DEBUG: current_minors = $current_minors, available_minors = $available_minors");
            
            // If adding this minor would make available_minors = 4, then convert
            if (($available_minors + 1) == 4) {
                $final_category = 'major';
                
                // Get the 3 previous unconverted minor IDs to mark as converted
                $prev_minors_q = $conn->prepare("SELECT id FROM violations WHERE user_id = ? AND violation_category = 'minor' AND status IN ('pending', 'under_review') AND id NOT IN (SELECT converted_violation_ids FROM violation_conversion_tracking WHERE student_id = ? AND converted_violation_ids IS NOT NULL) ORDER BY created_at DESC LIMIT 3");
                $prev_minors_q->bind_param("ii", $student_id, $student_id);
                $prev_minors_q->execute();
                $prev_minors_result = $prev_minors_q->get_result();
                
                while ($minor = $prev_minors_result->fetch_assoc()) {
                    $converted_ids[] = $minor['id'];
                }
                
                $description .= " [AUTO-CONVERTED: 4th minor violation. Converted minors: " . implode(',', $converted_ids) . "]";
                error_log("AUTO-CONVERSION TRIGGERED: available_minors=$available_minors, will convert to major");
                
                $should_track_conversion = true;
            } else {
                error_log("NO CONVERSION: available_minors=$available_minors, needs " . (4 - ($available_minors + 1)) . " more for conversion");
                $should_track_conversion = false;
            }
        }

        $insertQuery = $conn->prepare("INSERT INTO violations (user_id, recorded_by, user_type, violation_type, violation_category, description, status, proof_filename, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $insertQuery->bind_param("iissssss", $student_id, $logged_in_user_id, $user_type, $violation_type, $final_category, $description, $status, $proof_db_path);
        
                      if ($insertQuery->execute()) {
            $violation_id = $conn->insert_id;
            
            // If this was an auto-conversion, record it in the tracking table
            if ($should_track_conversion && $final_category === 'major' && !empty($converted_ids)) {
                $tracking_insert = $conn->prepare("INSERT INTO violation_conversion_tracking (student_id, converted_violation_ids, resulting_major_id) VALUES (?, ?, ?)");
                $converted_ids_str = implode(',', $converted_ids);
                $tracking_insert->bind_param("isi", $student_id, $converted_ids_str, $violation_id);
                $tracking_insert->execute();
                error_log("CONVERSION TRACKED: Recorded conversion of minor IDs " . $converted_ids_str . " to major ID $violation_id");
            }
            
            // Send email notification to student
            if (function_exists('sendViolationEmail')) {
                try {
                    $student_email = $student['email'];
                    
                    // Get recorded by name properly
                    $recorded_by_name = '';
                    if ($isAdmin) {
                        // Get admin name from session/database
                        $recorded_by_name = 'Administrator';
                        if (!empty($admin['firstname']) || !empty($admin['surname'])) {
                            $recorded_by_name = trim(($admin['firstname'] ?? '') . ' ' . ($admin['surname'] ?? ''));
                        }
                        if (empty($recorded_by_name) || $recorded_by_name == ' ') {
                            $recorded_by_name = 'Administrator';
                        }
                    } else if ($isStaff) {
                        // Get staff name from session/database
                        $recorded_by_name = 'Staff Member';
                        if (!empty($staff['fullname'])) {
                            $recorded_by_name = trim($staff['fullname']);
                        }
                        if (empty($recorded_by_name)) {
                            $recorded_by_name = 'Staff Member';
                        }
                    }
                    
                    // Get the description text for the violation type
                    $violation_description_text = $description;
                    if ($final_category === 'minor' && isset($minor_violations[$violation_type])) {
                        $violation_description_text = $minor_violations[$violation_type];
                    } elseif ($final_category === 'major' && isset($major_violations[$violation_type])) {
                        $violation_description_text = $major_violations[$violation_type];
                    }
                    
                    $email_sent = sendViolationEmail(
                        $student_email,
                        $student_name,
                        $violation_type,
                        $violation_description_text,
                        $final_category,
                        $recorded_by_name,
                        isset($should_track_conversion) && $should_track_conversion
                    );
                    
                    if ($email_sent) {
                        error_log("SUCCESS: Violation email sent to $student_email");
                    } else {
                        error_log("WARNING: Violation recorded but email failed to send to $student_email");
                    }
                } catch (Exception $e) {
                    error_log("ERROR sending violation email: " . $e->getMessage());
                }
            }
            
            error_log("SUCCESS: Violation recorded - ID=$violation_id, Type=$violation_type, Category=$final_category, Proof=" . ($proof_db_path ? 'Yes' : 'No'));
            
            unset($_SESSION['scanned_student_id']);
            header("Location: profile.php?success=violation_recorded&id=" . $student_id);
            exit;
        } else {
            $error = "Failed to record violation: " . $conn->error;
            error_log("ERROR: " . $error);
        }
    } elseif (!$violation_type) {
        $error = "Please select a violation type";
    }
}

// ===================== VIOLATION LISTS (keep as before) =====================
$minor_violations = [
    "No ID" => "Failure to conspicuously wear College-issued ID",
    "Improper Attire" => "Wearing shorts, sando, or inappropriate clothing",
    "Improper Uniform" => "Not wearing prescribed uniform",
    "Late" => "Arriving late to class",
    "Mobile Phone Use" => "Disruptive use of mobile phone during class",
    "Disruptive Behavior" => "Running, loitering, noisy behavior",
    "Littering" => "Spitting or littering on campus",
    "Public Display of Affection" => "Petting, necking in campus",
    "Vaping" => "Use of e-cigarette/vape on campus",
    "Parking Violation" => "Violation of parking rules",
    "Disrespect" => "Disrespectful conduct toward authority",
    "Classroom Entry" => "Entering classroom without permission",
    "Gambling Materials" => "Possession of gambling items",
    "Other Minor" => "Other minor infractions"
];

$major_violations = [
    "Academic Cheating" => "Cheating in exams or assignments",
    "Plagiarism" => "Submitting another's work as own",
    "Falsification" => "Forgery, altering documents, spreading false info, forging stamps",
    "Physical Assault" => "Causing physical injury",
    "Threats" => "Threatening any person verbally or digitally",
    "Bullying" => "Bullying, harassment, defamation",
    "Weapon Possession" => "Possession of deadly weapons",
    "Drug Violation" => "Possession or use of illegal drugs",
    "Alcohol Violation" => "Possessing or drinking alcohol on campus",
    "Sexual Harassment" => "Any sexual harassment act",
    "Vandalism" => "Damaging school property",
    "Hazing" => "Participation in hazing",
    "Unauthorized Organization" => "Joining unrecognized fraternities/sororities",
    "Unauthorized Solicitation" => "Collecting money without approval",
    "System Tampering" => "Tampering with IT systems",
    "Gambling" => "Engaging in gambling",
    "Lewd Conduct" => "Obscene or indecent acts",
    "Disruption of Classes" => "Instigating disruption or illegal assembly",
    "Smoking" => "Smoking within school premises",
    "Publishing False Information" => "Spreading false/damaging info about the school",
    "Forging Security Stamps" => "Forging school security stamps/stickers",
    "ID or Document Misuse" => "Using someone else's ID or tampering with ID",
    "Accumulation of 4 Minor Offenses" => "Every 4 minors = major offense",
    "Endangering Safety" => "Acts endangering students/staff",
    "Forcible Entry" => "Entering restricted areas forcibly",
    "Unauthorized Use of Rooms" => "Using rooms without permission",
    "Misuse of IT Systems" => "Hacking, bypassing security",
    "Bribery" => "Offering money/favors to staff",
    "Stealing" => "Theft of property",
    "Tampering Emergency Devices" => "Using alarms or extinguishers improperly",
    "Obscene Materials" => "Possessing obscene/pornographic materials",
    "Violent Protest or Coercion" => "Protests using force or intimidation",
    "Unauthorized Posting" => "Posting literature without approval",
    "Aiding Violations" => "Helping others commit violations",
    "Other Major" => "Other major offenses"
];
?>
<!DOCTYPE html>
<html>
<head>
    
    <title>Record Violation</title>
    <link rel="stylesheet" href="styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <style>
        .violation-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .setup-card {
            max-width: 100%;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
        }
        .user-info-box {
            background: <?= $isAdmin ? '#e8f4fd' : '#e8f5e8'; ?>;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid <?= $isAdmin ? '#3498db' : '#2e7d32'; ?>;
        }
        .student-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .student-info h3 {
            margin-top: 0;
            color: white;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 10px;
        }
        .student-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        .student-detail-item {
            display: flex;
            flex-direction: column;
        }
        .student-detail-label {
            font-weight: 600;
            font-size: 0.9em;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .student-detail-value {
            font-size: 1.1em;
            font-weight: 500;
        }
        
        /* VIOLATION WARNING BOX - UPDATED */
        .violation-warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
        }
        .violation-warning-box.critical {
            background: #f8d7da;
            border: 3px solid #dc3545;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { border-color: #dc3545; }
            50% { border-color: #ff6b6b; }
            100% { border-color: #dc3545; }
        }
        .violation-warning-box h4 {
            margin: 0 0 15px 0;
            color: #856404;
            font-size: 1.2em;
            border-bottom: 1px solid rgba(133, 100, 4, 0.2);
            padding-bottom: 8px;
        }
        .violation-warning-box.critical h4 {
            color: #721c24;
        }
        .violation-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        .stat-item {
            background: rgba(255, 255, 255, 0.5);
            padding: 10px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-value.pending {
            color: #f39c12;
        }
        .stat-value.active {
            color: #3498db;
        }
        .stat-value.conversion {
            color: #e74c3c;
        }
        
        .violation-category {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title::before {
            content: "📋";
            font-size: 1.1em;
        }
        .category-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .category-btn {
            flex: 1;
            padding: 15px 25px;
            border: 2px solid #e1e8ed;
            background: #f8f9fa;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1em;
            color: #555;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .category-btn:hover {
            border-color: #007bff;
            background: #e3f2fd;
            transform: translateY(-2px);
        }
        .category-btn.active {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .category-btn.minor::before {
            content: "⚠️";
        }
        .category-btn.major::before {
            content: "🚨";
        }
        .violation-list {
            max-height: 400px;
            overflow-y: auto;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fafbfc;
        }
        .violation-option {
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .violation-option:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            transform: translateX(5px);
        }
        .violation-option.selected {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
        }
        .violation-title {
            font-weight: 600;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        .violation-details {
            font-size: 0.9em;
            opacity: 0.8;
            line-height: 1.4;
        }
        .violation-option.selected .violation-details {
            opacity: 0.9;
        }
        
        /* File Upload Styling */
        .file-upload-container {
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }
        .file-upload-container:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .file-upload-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1em;
        }
        .file-upload-box {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: white;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-upload-box:hover {
            border-color: #007bff;
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.1);
        }
        .file-icon {
            font-size: 2.5em;
            color: #007bff;
        }
        .file-info {
            flex: 1;
        }
        .file-info h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        .file-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9em;
        }
        .file-input {
            display: none;
        }
        .selected-file {
            margin-top: 15px;
            padding: 10px 15px;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 6px;
            color: #0c5460;
        }
        .file-requirements {
            margin-top: 10px;
            font-size: 0.85em;
            color: #6c757d;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1em;
        }
        textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 1em;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s ease;
            background: #fafbfc;
        }
        textarea:focus {
            outline: none;
            border-color: #007bff;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .save-btn {
            flex: 2;
            padding: 15px 30px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        .back-btn {
            flex: 1;
            padding: 15px 30px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        /* RESPONSIVE VIOLATION FORM */
        
        /* ================== RESPONSIVE STYLES ================== */

/* Tablet (768px and below) */
@media (max-width: 768px) {
    .violation-container {
        padding: 10px;
    }
    .setup-card {
        padding: 20px;
    }
    .student-details {
        grid-template-columns: 1fr;
    }
    .violation-stats {
        grid-template-columns: 1fr;
    }
    .category-buttons {
        flex-direction: column;
    }
    .action-buttons {
        flex-direction: column;
    }
    .save-btn, .back-btn {
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }
    .violation-warning-box {
        padding: 15px;
    }
    .file-upload-container {
        padding: 15px;
    }
    .file-upload-box {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
    h2 {
        font-size: 1.5em;
    }
    .student-info {
        padding: 20px;
    }
}

/* Mobile (480px and below) */
@media (max-width: 480px) {
    .violation-container {
        padding: 5px;
    }
    .setup-card {
        padding: 15px;
    }
    .violation-list {
        max-height: 300px;
    }
    .violation-option {
        padding: 12px;
    }
    .violation-title {
        font-size: 1em;
    }
    .violation-details {
        font-size: 0.85em;
    }
    .student-detail-value {
        font-size: 1em;
    }
    .stat-value {
        font-size: 1.2em;
    }
    .category-btn {
        padding: 12px;
        font-size: 0.9em;
    }
    textarea {
        min-height: 100px;
        padding: 12px;
    }
}

/* Small Mobile (320px and below) */
@media (max-width: 320px) {
    .setup-card {
        padding: 10px;
    }
    h2 {
        font-size: 1.3em;
    }
    .student-info {
        padding: 15px;
    }
    .section-title {
        font-size: 1.1em;
    }
    .file-upload-label {
        font-size: 1em;
    }
}

/* Landscape Mode for Mobile */
@media (max-height: 600px) and (orientation: landscape) {
    .violation-list {
        max-height: 200px;
    }
    textarea {
        min-height: 80px;
    }
}
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="violation-container">
    <div class="setup-card">
        <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50; font-size: 2em;">📝 Record Violation</h2>
        
        <!-- User Info Box -->
        <div class="user-info-box">
            <h4>👮 <?= $isAdmin ? 'Admin' : 'Staff' ?> Mode</h4>
            <p>You are recording a violation as <strong><?= $isAdmin ? 'Administrator' : htmlspecialchars($user_name) ?></strong>.</p>
        </div>
        
        <div class="student-info">
            <h3>👤 Student Information</h3>
            <div class="student-details">
                <div class="student-detail-item">
                    <span class="student-detail-label">Full Name</span>
                    <span class="student-detail-value"><?= htmlspecialchars($student_name) ?></span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Student Number</span>
                    <span class="student-detail-value"><?= htmlspecialchars($student['student_number']) ?></span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Email Address</span>
                    <span class="student-detail-value"><?= htmlspecialchars($student['email']) ?></span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Course & Section</span>
                    <span class="student-detail-value"><?= htmlspecialchars($student['course'] . ' - ' . $student['section']) ?></span>
                </div>
            </div>
        </div>

        <!-- ===================== UPDATED: VIOLATION WARNING BOX ===================== -->
        <div class="violation-warning-box <?= $show_critical_warning ? 'critical' : '' ?>">
            <h4>⚠️ Violation Status for <?= htmlspecialchars($student_name) ?></h4>
            
            <div class="violation-stats">
                <div class="stat-item">
                    <div class="stat-label">Active Minor Violations</div>
                    <div class="stat-value active"><?= $current_minors ?></div>
                    <small>(pending + under review)</small>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Pending Minor Only</div>
                    <div class="stat-value pending"><?= $pending_minors_only ?></div>
                    <small>(not yet reviewed)</small>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Active Major Violations</div>
                    <div class="stat-value active"><?= $current_majors ?></div>
                    <small>(pending + under review)</small>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Next Conversion In</div>
                    <div class="stat-value conversion"><?= $next_conversion ?> minor(s)</div>
                    <small>(4 minors = 1 major)</small>
                </div>
            </div>
            
            <?php if ($available_minors > 0): ?>
                <?php if ($remaining_minors == 3): ?>
                    <div style="text-align: center; margin-top: 15px; padding: 12px; background: rgba(220, 53, 69, 0.1); border-radius: 6px;">
                        <p style="color: #dc3545; font-weight: bold; font-size: 1.1em; margin: 0;">
                            🚨 WARNING: Next minor violation will be AUTOMATICALLY CONVERTED to MAJOR!
                        </p>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #721c24;">
                            This student has <?= $available_minors ?> unconverted minor violations
                        </p>
                    </div>
                <?php elseif ($remaining_minors == 2): ?>
                    <p style="color: #856404; font-weight: bold; text-align: center; margin-top: 15px;">
                        ⚠️ 2 more minor violations will trigger automatic major offense
                    </p>
                <?php elseif ($remaining_minors == 1): ?>
                    <p style="color: #856404; text-align: center; margin-top: 15px;">
                        ℹ️ 3 more minor violations will trigger automatic major offense
                    </p>
                <?php else: ?>
                    <p style="color: #28a745; text-align: center; margin-top: 15px;">
                        ✓ Keep track of violations (<?= $next_conversion ?> until next conversion)
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align: center; color: #6c757d; margin-top: 15px;">
                    No unconverted minor violations. Need 4 NEW minors for next conversion.
                </p>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="violationForm" enctype="multipart/form-data">
            <div class="violation-category">
                <div class="section-title">Violation Category</div>
                <div class="category-buttons">
                    <button type="button" class="category-btn minor active" data-category="minor">Minor Violations</button>
                    <button type="button" class="category-btn major" data-category="major">Major Violations</button>
                </div>
                
                <div id="minor-violations" class="violation-list">
                    <?php foreach ($minor_violations as $value => $description): ?>
                        <div class="violation-option" data-value="<?= htmlspecialchars($value) ?>" data-category="minor">
                            <div class="violation-title"><?= htmlspecialchars($value) ?></div>
                            <div class="violation-details"><?= htmlspecialchars($description) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="major-violations" class="violation-list" style="display: none;">
                    <?php foreach ($major_violations as $value => $description): ?>
                        <div class="violation-option" data-value="<?= htmlspecialchars($value) ?>" data-category="major">
                            <div class="violation-title"><?= htmlspecialchars($value) ?></div>
                            <div class="violation-details"><?= htmlspecialchars($description) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <input type="hidden" name="violation_type" id="violation_type" required>
                <input type="hidden" name="violation_category" id="violation_category" value="minor">
            </div>
            
            <!-- File Upload Section - SAME AS assign_sanction.php -->
            <div class="file-upload-container">
                <label class="file-upload-label">📎 Upload Evidence (Optional)</label>
                <div class="file-upload-box" onclick="document.getElementById('fileInput').click()">
                    <div class="file-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="file-info">
                        <h4>Click to upload evidence</h4>
                        <p>Upload photos, documents, or other evidence related to this violation</p>
                    </div>
                    <input type="file" name="violationProof" id="fileInput" class="file-input" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                </div>
                <div class="selected-file" id="selectedFile" style="display: none;">
                    <i class="fas fa-file"></i> Selected: <span id="fileName"></span>
                    <button type="button" onclick="removeFile()" style="margin-left: 10px; color: #dc3545; background: none; border: none; cursor: pointer;">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
              
            </div>
            
            <div class="form-group">
                <label for="description">📝 Additional Details</label>
                <textarea name="description" id="description" placeholder="Provide specific details about the violation, including location, time, circumstances, witnesses, and any other relevant information that will help in the investigation..." rows="6"></textarea>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="save-btn">✅ Record Violation</button>
                <a href="profile.php?id=<?= $student_id ?>" class="back-btn">← Back to Profile</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryButtons = document.querySelectorAll('.category-btn');
    const minorList = document.getElementById('minor-violations');
    const majorList = document.getElementById('major-violations');
    const violationTypeInput = document.getElementById('violation_type');
    const violationCategoryInput = document.getElementById('violation_category');
    const violationOptions = document.querySelectorAll('.violation-option');
    
    // Category switching
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // Update active button
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide appropriate list
            if (category === 'minor') {
                minorList.style.display = 'block';
                majorList.style.display = 'none';
                violationCategoryInput.value = 'minor';
            } else {
                minorList.style.display = 'none';
                majorList.style.display = 'block';
                violationCategoryInput.value = 'major';
            }
            
            // Clear selection when switching categories
            violationTypeInput.value = '';
            violationOptions.forEach(opt => opt.classList.remove('selected'));
        });
    });
    
    // Violation selection
    violationOptions.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const category = this.getAttribute('data-category');
            
            // Update selection
            violationOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            violationTypeInput.value = value;
        });
    });
    
    // File upload handling
    const fileInput = document.getElementById('fileInput');
    const selectedFileDiv = document.getElementById('selectedFile');
    const fileNameSpan = document.getElementById('fileName');
    
    fileInput.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            const file = this.files[0];
            fileNameSpan.textContent = file.name;
            selectedFileDiv.style.display = 'block';
        } else {
            selectedFileDiv.style.display = 'none';
        }
    });
    
    // Form validation
    document.getElementById('violationForm').addEventListener('submit', function(e) {
        if (!violationTypeInput.value) {
            e.preventDefault();
            alert('⚠️ Please select a violation type before submitting.');
            return false;
        }
    });
});

function removeFile() {
    const fileInput = document.getElementById('fileInput');
    const selectedFileDiv = document.getElementById('selectedFile');
    
    fileInput.value = '';
    selectedFileDiv.style.display = 'none';
}
// Handle orientation change
window.addEventListener('orientationchange', function() {
    setTimeout(function() {
        // Force reflow on orientation change
        document.body.style.minHeight = window.innerHeight + 'px';
    }, 100);
});

// Adjust for mobile touch
document.querySelectorAll('.violation-option').forEach(option => {
    option.addEventListener('touchstart', function() {
        this.style.backgroundColor = '#f0f0f0';
    });
    option.addEventListener('touchend', function() {
        this.style.backgroundColor = '';
    });
});
</script>
</body>
</html>