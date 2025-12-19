<?php
session_start();
require_once 'database.php';
require_once 'email_config.php';

    if (isset($_GET['role']) && $_GET['role'] !== 'student') {
    header("Location: choose_role.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $agree = isset($_POST['agree']) ? 1 : 0;

    // Validate KLD email
    if (!preg_match('/@kld\.edu\.ph$/', $email)) {
        header("Location: register.php?error=kld");
        exit();
    }

    // Check if user agreed to terms
    if (!$agree) {
        header("Location: register.php?error=agree");
        exit();
    }

    // ========== PASSWORD VALIDATION ==========
    // Check minimum length
    if (strlen($password) < 8) {
        header("Location: register.php?error=password_length");
        exit();
    }

    // Check for forbidden special characters
    $forbiddenChars = ['@', '#', '$'];
    foreach ($forbiddenChars as $char) {
        if (strpos($password, $char) !== false) {
            header("Location: register.php?error=special_chars");
            exit();
        }
    }
    // ========== END PASSWORD VALIDATION ==========

    // Check if email already exists and is verified
    $stmt = $conn->prepare("SELECT id, completed_setup FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['completed_setup'] == 1) {
            header("Location: register.php?error=exists");
            exit();
        } else {
            // User exists but not verified - delete old record
            $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
        }
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate 6-digit OTP
    $otp_code = sprintf("%06d", mt_rand(1, 999999));
    $otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Insert user with OTP
    $stmt = $conn->prepare("INSERT INTO users (email, password, otp_code, otp_expires_at, completed_setup) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $email, $hashedPassword, $otp_code, $otp_expires_at);

    if ($stmt->execute()) {
        // Send OTP via email using PHPMailer
        $email_sent = sendOTPEmail($email, $otp_code);
        
        if ($email_sent) {
            // Store email in session for verification
            $_SESSION['verify_email'] = $email;
            $_SESSION['otp_attempts'] = 3; // Allow 3 attempts
            
            // Redirect to verification page
            header("Location: verify_otp.php");
            exit();
        } else {
            // If email failed to send, delete the user record
            $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            header("Location: register.php?error=email_failed");
            exit();
        }
    } else {
        header("Location: register.php?error=invalid");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>