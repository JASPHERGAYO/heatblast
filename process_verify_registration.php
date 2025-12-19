<?php
session_start();
include "database.php";

if (!isset($_SESSION['temp_email']) || !isset($_SESSION['verification_code'])) {
    header("Location: register.php?step=email");
    exit;
}

$entered_code = trim($_POST['code']);
$stored_code = $_SESSION['verification_code'];
$email = $_SESSION['temp_email'];
$password = $_SESSION['temp_password'];

// Check if code is expired (10 minutes)
if (time() - $_SESSION['code_time'] > 600) {
    header("Location: register.php?step=code&error=Code expired");
    exit;
}

if ($entered_code == $stored_code) {
    // Code verified - create user account
    $stmt = $conn->prepare("INSERT INTO users (email, password, completed_setup) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $email, $password);
    
    if ($stmt->execute()) {
        // Clear session and show success
        unset($_SESSION['temp_email']);
        unset($_SESSION['temp_password']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['code_time']);
        
        header("Location: register.php?step=verified");
        exit;
    } else {
        header("Location: register.php?step=code&error=Registration failed");
        exit;
    }
} else {
    header("Location: register.php?step=code&error=Invalid code");
    exit;
}
?>