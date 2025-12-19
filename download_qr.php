<?php
session_start();
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in']) && !isset($_SESSION['staff_logged_in'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isStaff = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if ($isAdmin && !isset($_GET['id'])) {
    // Admin downloading their own QR
    $qrPath = __DIR__ . '/qrcodes/admin_qr.png';
    $fileName = 'Admin_QR_Code.png';
} elseif ($isStaff && !isset($_GET['id'])) {
    // Staff downloading their own QR
    $staff_email = $_SESSION['staff_email'] ?? '';
    $stf = $conn->prepare("SELECT qr_code, fullname FROM staff WHERE email=? LIMIT 1");
    $stf->bind_param("s", $staff_email);
    $stf->execute();
    $staff = $stf->get_result()->fetch_assoc();
    
    $qrPath = $staff['qr_code'] ?? __DIR__ . '/qrcodes/default_qr.png';
    $fileName = ($staff['fullname'] ?? 'Staff') . '_QR_Code.png';
} elseif ($user_id) {
    // Student downloading their QR
    $q = $conn->prepare("SELECT qr_code, firstname, surname FROM student_profiles WHERE user_id = ?");
    $q->bind_param("i", $user_id);
    $q->execute();
    $result = $q->get_result()->fetch_assoc();
    
    if ($result) {
        $qrPath = $result['qr_code'];
        $fileName = $result['firstname'] . '_' . $result['surname'] . '_QR_Code.png';
    } else {
        die("QR code not found.");
    }
} else {
    die("Invalid request.");
}

// Normalize relative paths (e.g. 'qrcodes/user_1.png') to absolute filesystem path
// Accept Windows drive letters like D:\ or D:/ as absolute too
if (!preg_match('#^(?:[a-zA-Z]:|/|https?://)#', $qrPath)) {
    $qrPath = __DIR__ . '/' . ltrim($qrPath, '/\\');
}

// Check if file exists, fallback to default
if (!file_exists($qrPath)) {
    // Try default QR code (absolute path)
    $defaultPath = __DIR__ . '/qrcodes/default_qr.png';
    if (file_exists($defaultPath)) {
        $qrPath = $defaultPath;
    } else {
        die("QR code file not found.");
    }
}

// Set headers for download
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($qrPath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output the file
readfile($qrPath);
exit();
?>