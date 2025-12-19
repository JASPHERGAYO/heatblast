<?php
session_start();
include "database.php";

if (!isset($_SESSION['temp_email'])) {
    header("Location: forgotpass.php?step=email");
    exit;
}

$new = $_POST['new_password'];
$confirm = $_POST['confirm_password'];

if ($new !== $confirm) {
    header("Location: forgotpass.php?step=reset&error=Passwords do not match");
    exit;
}

$email = $_SESSION['temp_email'];
$hashed = password_hash($new, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed, $email);
$stmt->execute();

// CLEAR SESSION (OTP already cleared from database in verification step)
unset($_SESSION['temp_email']);

header("Location: login.php?updated=1");
exit;