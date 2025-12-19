<?php
session_start();
include "database.php";
require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 1. Validate email
if (!isset($_POST['email'])) {
    header("Location: forgotpass.php?step=email&error=Enter your email");
    exit;
}

$email = trim($_POST['email']);

if (!preg_match("/@kld\.edu\.ph$/", $email)) {
    header("Location: forgotpass.php?step=email&error=Use your KLD email");
    exit;
}

// 2. Check if email exists in database
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: forgotpass.php?step=email&error=Email not found");
    exit;
}

// 3. Generate and save code to DATABASE instead of session
$code = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store in database
$stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
$stmt->bind_param("sss", $code, $expires_at, $email);
$stmt->execute();

// Still store email in session for the flow
$_SESSION['temp_email'] = $email;

// 4. Send email using centralized config
require_once 'email_config.php';

if (sendPasswordResetEmail($email, $code)) {
    // Success
} else {
    // Log error if needed
    // error_log("Password reset email failed");
}

header("Location: forgotpass.php?step=code");
exit;
?>