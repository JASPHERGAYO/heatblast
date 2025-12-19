<?php
session_start();
include 'database.php';

// Check if student_id is provided
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    die("Invalid QR code: No student ID provided");
}

$student_id = $_GET['student_id'];

// Check who is scanning
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Admin is scanning - redirect to profile view
    header("Location: profile.php?id=" . $student_id);
    exit();
} 
elseif (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    // Staff is scanning - redirect to profile view
    header("Location: profile.php?id=" . $student_id);
    exit();
}
elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Student is scanning - redirect to profile view
    header("Location: profile.php?id=" . $student_id);
    exit();
}
else {
    // No one is logged in - redirect to login
    header("Location: login.php?redirect=scan&student_id=" . $student_id);
    exit();
}
?>