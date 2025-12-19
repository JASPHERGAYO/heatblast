<?php
session_start();
// Check if user is already logged in
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Account Type</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login.css">
     <link rel="stylesheet" href="role_styles.css">   
    <style>
        /* (CSS styles from earlier) */
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="role-container">
        <h2 class="role-title">Create Account</h2>
        <p class="role-subtitle">Select your role to continue</p>
        
        <div class="role-options">
            <!-- Student Option -->
            <a href="register.php?role=student" class="role-btn student-btn">
                <div class="role-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="role-info">
                    <h4>Student Account</h4>
                    <p>Create a student account to view violations and QR code</p>
                </div>
                <i class="fas fa-chevron-right" style="margin-left: auto; color: #ccc;"></i>
            </a>
            
            <!-- Admin/Staff Option -->
            <a href="register_admin_staff.php" class="role-btn admin-staff-btn">
                <div class="role-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="role-info">
                    <h4>Admin or Staff Account</h4>
                    <p>For school administrators and staff members</p>
                </div>
                <i class="fas fa-chevron-right" style="margin-left: auto; color: #ccc;"></i>
            </a>
        </div>
        
        <div class="existing-account">
            Already have an account? <a href="logout.php">Login here</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>