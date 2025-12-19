<?php
session_start();
require_once 'database.php';
require_once 'email_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // 'admin' or 'staff'
    
    // Validate KLD email
    if (!preg_match('/@kld\.edu\.ph$/', $email)) {
        header("Location: register_admin_staff.php?error=kld");
        exit();
    }
    
    // Check password match
    if ($password !== $confirm_password) {
        header("Location: register_admin_staff.php?error=passwords_dont_match");
        exit();
    }
    
    // Password validation
    if (strlen($password) < 8) {
        header("Location: register_admin_staff.php?error=password_length");
        exit();
    }
    
    $forbiddenChars = ['@', '#', '$'];
    foreach ($forbiddenChars as $char) {
        if (strpos($password, $char) !== false) {
            header("Location: register_admin_staff.php?error=special_chars");
            exit();
        }
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate 6-digit OTP
    $otp_code = sprintf("%06d", mt_rand(1, 999999));
    $otp_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Check if email already exists (and is verified)
    if ($role === 'admin') {
        $check_stmt = $conn->prepare("SELECT id, verified FROM admins WHERE email = ?");
    } else {
        $check_stmt = $conn->prepare("SELECT id, verified FROM staff WHERE email = ?");
    }
    
    if (!$check_stmt) {
        error_log("Prepare failed for check: " . $conn->error);
        header("Location: register_admin_staff.php?error=database_error");
        exit();
    }
    
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['verified'] == 1) {
            header("Location: register_admin_staff.php?error=exists");
            exit();
        } else {
            // User exists but not verified - delete old record
            if ($role === 'admin') {
                $delete_stmt = $conn->prepare("DELETE FROM admins WHERE email = ?");
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM staff WHERE email = ?");
            }
            
            if ($delete_stmt) {
                $delete_stmt->bind_param("s", $email);
                $delete_stmt->execute();
            }
        }
    }
    
    // Insert into appropriate table with OTP and role
    if ($role === 'admin') {
        // For admins table
        $stmt = $conn->prepare("INSERT INTO admins (email, password, role, otp_code, otp_expires_at, verified) VALUES (?, ?, 'admin', ?, ?, 0)");
    } else {
        // For staff table (now with role column)
        $stmt = $conn->prepare("INSERT INTO staff (email, password, role, otp_code, otp_expires_at, verified) VALUES (?, ?, 'staff', ?, ?, 0)");
    }
    
    if (!$stmt) {
        error_log("Prepare failed for insert: " . $conn->error);
        header("Location: register_admin_staff.php?error=database_error");
        exit();
    }
    
    // Bind parameters - both tables have same structure now
    if ($role === 'admin') {
        $stmt->bind_param("ssss", $email, $hashedPassword, $otp_code, $otp_expires_at);
    } else {
        $stmt->bind_param("ssss", $email, $hashedPassword, $otp_code, $otp_expires_at);
    }
    
    if ($stmt->execute()) {
        // Send OTP via email
        $email_sent = sendOTPEmail($email, $otp_code);
        
        if ($email_sent) {
            // Store email and role in session for verification
            $_SESSION['verify_email'] = $email;
            $_SESSION['verify_role'] = $role;
            $_SESSION['otp_attempts'] = 3; // Allow 3 attempts
            
            // Redirect to admin/staff verification page
            header("Location: verify_admin_staff.php");
            exit();
        } else {
            // If email failed to send, delete the record
            if ($role === 'admin') {
                $delete_stmt = $conn->prepare("DELETE FROM admins WHERE email = ?");
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM staff WHERE email = ?");
            }
            
            if ($delete_stmt) {
                $delete_stmt->bind_param("s", $email);
                $delete_stmt->execute();
            }
            
            header("Location: register_admin_staff.php?error=email_failed");
            exit();
        }
    } else {
        error_log("Execute failed: " . $stmt->error);
        header("Location: register_admin_staff.php?error=database_error");
        exit();
    }
} else {
    header("Location: register_admin_staff.php");
    exit();
}
?>