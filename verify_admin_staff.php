<?php
session_start();

// Check if verification session exists
if (!isset($_SESSION['verify_email']) || !isset($_SESSION['verify_role'])) {
    header("Location: register_admin_staff.php");
    exit();
}

// ONLY ADMINS can access this page
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
// Admins can continue...

$email = $_SESSION['verify_email'];
$role = $_SESSION['verify_role'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Admin/Staff</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login.css">
        <link rel="stylesheet" href="role_styles.css">  
    <style>
        /* (CSS styles from earlier) */
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="verification-container">
        <h2 class="verification-title">Verify Your Email</h2>
        
        <p class="verification-message">
            We've sent a 6-digit verification code to:<br>
            <strong><?php echo htmlspecialchars($email); ?></strong><br>
            <small>(<?php echo ucfirst($role); ?> Account)</small>
        </p>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php
                    switch ($_GET['error']) {
                        case "invalid_otp":
                            echo "Invalid OTP code. Please try again.";
                            break;
                        case "expired":
                            echo "OTP has expired. Please request a new one.";
                            break;
                        case "attempts":
                            echo "Too many failed attempts. Please register again.";
                            break;
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <form action="process_verify_admin_staff.php" method="POST" id="otpForm">
            <div class="otp-inputs">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input type="text" 
                           class="otp-input" 
                           name="otp<?php echo $i; ?>" 
                           maxlength="1" 
                           min="0" 
                           max="9"
                           oninput="moveToNext(this, <?php echo $i; ?>)" 
                           required>
                <?php endfor; ?>
            </div>
            
            <button type="submit" class="login-btn">Verify Account</button>
        </form>
        
        <div class="resend-link">
            Didn't receive the code? <a href="#" onclick="resendOTP()">Resend OTP</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>

     <script>
        function moveToNext(current, nextIndex) {
            // Only allow numbers
            current.value = current.value.replace(/[^0-9]/g, '');
            
            if (current.value.length >= current.maxLength) {
                const next = document.querySelector(`input[name="otp${nextIndex + 1}"]`);
                if (next) {
                    next.focus();
                }
            }
            
            // Auto-submit when last digit is entered
            if (nextIndex === 6 && current.value.length >= current.maxLength) {
                document.getElementById('otpForm').submit();
            }
        }
        
        function resendOTP() {
            alert('OTP resent! Check your email.');
            // You can implement AJAX call here
        }
    </script>
</body>
</html>