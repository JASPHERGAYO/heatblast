<?php
// DESTROY ANY EXISTING SESSION FIRST
session_start();
session_destroy();
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: login.php?error=empty");
    exit;
}

/* ============================================================
   CHECK ADMIN LOGIN
============================================================ */
$adminQuery = $conn->prepare("
    SELECT id, email, password, has_setup 
    FROM admins 
    WHERE email = ? 
    LIMIT 1
");
$adminQuery->bind_param("s", $email);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();

if ($adminResult->num_rows === 1) {
    $admin = $adminResult->fetch_assoc();
    
    if (!password_verify($password, $admin['password'])) {
        header("Location: login.php?error=wrongpass");
        exit;
    }

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];

    if ((int)$admin['has_setup'] === 0) {
        header("Location: first_setup_admin.php");
        exit;
    }

    header("Location: profile.php");
    exit;
}

/* ============================================================
   CHECK USER LOGIN
============================================================ */
$userQuery = $conn->prepare("
    SELECT id, email, password, completed_setup, profile_completed 
    FROM users 
    WHERE email = ? 
    LIMIT 1
");
$userQuery->bind_param("s", $email);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows === 0) {
    header("Location: login.php?error=notfound");
    exit;
}

$user = $userResult->fetch_assoc();

// Check if email is verified (completed_setup = 1)
if ((int)$user['completed_setup'] === 0) {
    header("Location: login.php?error=notverified");
    exit;
}

if (!password_verify($password, $user['password'])) {
    header("Location: login.php?error=wrongpass");
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];

// Check if profile is completed
if ((int)$user['profile_completed'] === 0) {
    header("Location: first_setup.php");
    exit;
}

// Both email verified and profile completed - go to profile
header("Location: profile.php");
exit;
?>