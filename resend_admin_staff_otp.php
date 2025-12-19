<?php
session_start();
require_once 'database.php';
require_once 'email_config.php';



// Check if verification session exists
if (!isset($_SESSION['verify_email']) || !isset($_SESSION['verify_role'])) {
    header("Location: register_admin_staff.php");
    exit();
}

// ONLY ADMINS can access this page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // If not admin, check what type of user they are
    if (isset($_SESSION['user_id'])) {
        header("Location: profile.php"); // Student
    } elseif (isset($_SESSION['staff_logged_in'])) {
        header("Location: staff_db.php"); // Staff
    } else {
        header("Location: login.php"); // Not logged in
    }
    exit();
}
// Admins can continue...


$email = $_SESSION['verify_email'];
$role = $_SESSION['verify_role'];

// Generate new OTP
$otp_code = sprintf("%06d", mt_rand(1, 999999));
$otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update OTP in database
if ($role === 'admin') {
    $stmt = $conn->prepare("UPDATE admins SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
} else {
    $stmt = $conn->prepare("UPDATE staff SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
}

$stmt->bind_param("sss", $otp_code, $otp_expires_at, $email);
$stmt->execute();

// Send new OTP email
$email_sent = sendOTPEmail($email, $otp_code);

if ($email_sent) {
    // Reset OTP attempts
    $_SESSION['otp_attempts'] = 3;
    
    // Redirect back to verification page
    header("Location: verify_admin_staff.php?resent=1");
    exit();
} else {
    header("Location: verify_admin_staff.php?error=email_failed");
    exit();
}
?>