<?php
// save_setup.php
session_start();
require_once 'database.php';
require_once 'phpqrcode/qrlib.php'; // phpqrcode library

// FIX: Change 'userid' to 'user_id' to match first_setup.php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// FIX: Change 'userid' to 'user_id'
$uid = (int)$_SESSION['user_id'];

// simple sanitizer
function clean($v) {
    return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
}

$surname = clean($_POST['surname'] ?? '');
$firstname = clean($_POST['firstname'] ?? '');
$mi = clean($_POST['middle_initial'] ?? '');
$studentnum = clean($_POST['student_number'] ?? '');
$sex = clean($_POST['sex'] ?? '');
$course = clean($_POST['course'] ?? '');
$section = clean($_POST['section'] ?? '');
$year_level = (int)($_POST['year_level'] ?? 0); // LAGAY MO TO
 
if ($surname === '' || $firstname === '' || $mi === '' || $studentnum === '' || $sex === '' || $course === '' || $section === '') {
    die('Missing required fields. Please go back and complete the form.');
}

// create directory for QR codes
$qrDir = 'qrcodes/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0777, true);
}

// Build QR URL - adapt $BASE_URL to your environment
$BASE_URL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$qrUrl = $BASE_URL . '/scan_handler.php?student_id=' . urlencode($uid);

// choose filename
$qrFilename = 'user_' . $uid . '.png';
$qrPath = $qrDir . $qrFilename;
$qrPathForDb = 'qrcodes/' . $qrFilename;

// generate QR (error correction H, size 5)
QRcode::png($qrUrl, $qrPath, QR_ECLEVEL_H, 5);

// if profile exists update, otherwise insert
$check = $conn->prepare("SELECT id FROM student_profiles WHERE user_id = ? LIMIT 1");
$check->bind_param("i", $uid);
$check->execute();
$checkRes = $check->get_result();

if ($checkRes && $checkRes->num_rows > 0) {
    // update - ADDED year_level
    $upd = $conn->prepare("UPDATE student_profiles SET surname=?, firstname=?, middle_initial=?, student_number=?, sex=?, course=?, section=?, year_level=?, qr_code=? WHERE user_id=?");
    $upd->bind_param("sssssssisi",
        $surname, $firstname, $mi, $studentnum, $sex, $course, $section, $year_level, $qrPathForDb, $uid
    );
    if (!$upd->execute()) {
        error_log("Profile update error: " . $upd->error);
        die("Could not update profile.");
    }
} else {
    // insert - ADDED year_level
    $ins = $conn->prepare("INSERT INTO student_profiles (user_id, surname, firstname, middle_initial, student_number, sex, course, section, year_level, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->bind_param("issssssiss", $uid, $surname, $firstname, $mi, $studentnum, $sex, $course, $section, $year_level, $qrPathForDb);
    if (!$ins->execute()) {
        error_log("Profile insert error: " . $ins->error);
        die("Could not create profile.");
    }
}

// FIX: Also update the user's completed_setup status
$updateUser = $conn->prepare("UPDATE users SET completed_setup = 1 WHERE id = ?");
$updateUser->bind_param("i", $uid);
$updateUser->execute();
$_SESSION['setup_success'] = "Account has been created successfully!";
// done -> go to profile
header('Location: profile.php');
exit;
?>