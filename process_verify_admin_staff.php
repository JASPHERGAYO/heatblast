<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['verify_email']) || !isset($_SESSION['verify_role']) || !isset($_SESSION['otp_attempts'])) {
    header("Location: register_admin_staff.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_SESSION['verify_email'];
    $role = $_SESSION['verify_role'];
    
    // Get OTP from form (6 individual inputs)
    $otp = '';
    for ($i = 1; $i <= 6; $i++) {
        $otp .= $_POST["otp$i"] ?? '';
    }
    
    // Check OTP length
    if (strlen($otp) !== 6) {
        header("Location: verify_admin_staff.php?error=invalid_otp");
        exit();
    }
    
    // Get user from appropriate table
    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM admins WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM staff WHERE email = ?");
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found
        session_destroy();
        header("Location: register_admin_staff.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Check OTP attempts
    $_SESSION['otp_attempts']--;
    if ($_SESSION['otp_attempts'] <= 0) {
        // Too many failed attempts - delete user
        if ($role === 'admin') {
            $delete_stmt = $conn->prepare("DELETE FROM admins WHERE email = ?");
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM staff WHERE email = ?");
        }
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();
        
        session_destroy();
        header("Location: register_admin_staff.php?error=attempts");
        exit();
    }
    
    // Check if OTP is expired
    $current_time = date('Y-m-d H:i:s');
    if ($current_time > $user['otp_expires_at']) {
        header("Location: verify_admin_staff.php?error=expired");
        exit();
    }
    
    // Verify OTP
    if ($otp !== $user['otp_code']) {
        // Wrong OTP
        header("Location: verify_admin_staff.php?error=invalid_otp");
        exit();
    }
    
    // OTP is correct - verify user account
    if ($role === 'admin') {
        $update_stmt = $conn->prepare("UPDATE admins SET verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE email = ?");
    } else {
        $update_stmt = $conn->prepare("UPDATE staff SET verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE email = ?");
    }
    
    $update_stmt->bind_param("s", $email);
    $update_stmt->execute();
    
    // Clear verification session
    unset($_SESSION['verify_email']);
    unset($_SESSION['verify_role']);
    unset($_SESSION['otp_attempts']);
    
    // Redirect back to admin dashboard with success message
header("Location: admin_db.php?account_created=1&role=" . $role);
exit();
    
} else {
    header("Location: verify_admin_staff.php");
    exit();
}
?>