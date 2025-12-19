<?php
session_start();
include "database.php";

if (!isset($_SESSION['temp_email'])) {
    header("Location: forgotpass.php?step=email");
    exit;
}

$email = $_SESSION['temp_email'];
$entered = trim($_POST['code']);

// Verify against DATABASE instead of session
$stmt = $conn->prepare("SELECT otp_code, otp_expires_at FROM users WHERE email = ? AND otp_code IS NOT NULL");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $stored_code = $user['otp_code'];
    $expires_at = $user['otp_expires_at'];
    
    // Check if code matches and is not expired
    if ($entered == $stored_code && strtotime($expires_at) > time()) {
        // Clear the OTP after successful verification
        $stmt = $conn->prepare("UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        header("Location: forgotpass.php?step=reset");
        exit;
    }
}

// If code is wrong or expired
header("Location: forgotpass.php?step=code&error=Wrong or expired code");
exit;