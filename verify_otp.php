<?php
session_start();
require_once 'database.php';
require_once 'email_config.php';

if (!isset($_SESSION['verify_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['verify_email'];
$error = '';
$success = '';

// Resend OTP functionality
if (isset($_POST['resend_otp'])) {
    // Generate new OTP
    $new_otp = sprintf("%06d", mt_rand(1, 999999));
    $new_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
    $stmt->bind_param("sss", $new_otp, $new_expires, $email);
    
    if ($stmt->execute()) {
        // Send new OTP via email
        $email_sent = sendOTPEmail($email, $new_otp);
        
        if ($email_sent) {
            $_SESSION['otp_attempts'] = 3; // Reset attempts
            $success = "New verification code sent to your email!";
        } else {
            $error = "Failed to send email. Please try again.";
        }
    } else {
        $error = "Error generating new code. Please try again.";
    }
}

// Verify OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];
    
    // Check OTP validity - GET THE USER ID TOO
    $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        $current_time = date('Y-m-d H:i:s');
        
        if ($user['otp_code'] == $entered_otp && $current_time < $user['otp_expires_at']) {
            // ✅ FIXED: Mark email as verified but redirect to first_setup
            $stmt = $conn->prepare("UPDATE users SET completed_setup = 1, otp_code = NULL, otp_expires_at = NULL WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // ✅ FIXED: Set user session and redirect to first_setup.php
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            
            // Clear verification session
            unset($_SESSION['verify_email']);
            unset($_SESSION['otp_attempts']);
            
            // ✅ FIXED: Redirect to first_setup instead of login
            header("Location: first_setup.php");
            exit();
        } else {
            $_SESSION['otp_attempts']--;
            
            if ($_SESSION['otp_attempts'] <= 0) {
                $error = "Too many failed attempts. Please register again.";
                // Clean up
                $stmt = $conn->prepare("DELETE FROM users WHERE email = ? AND completed_setup = 0");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                session_destroy();
                header("Location: register.php?error=attempts_exceeded");
                exit();
            } else if ($current_time > $user['otp_expires_at']) {
                $error = "OTP has expired. Please request a new one.";
            } else {
                $error = "Invalid OTP code! You have " . $_SESSION['otp_attempts'] . " attempts remaining.";
            }
        }
    } else {
        $error = "User not found! Please register again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - KLD</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="login-wrapper">
    <div class="login-card">
        <h2>Verify Your Email</h2>
        <p style="text-align: center; margin-bottom: 20px;">
            We sent a 6-digit verification code to:<br>
            <strong><?php echo htmlspecialchars($email); ?></strong>
        </p>

        <?php if ($error): ?>
            <script>
            Swal.fire({
                icon: "error",
                title: "Verification Failed",
                text: "<?php echo $error; ?>"
            });
            </script>
        <?php endif; ?>

        <?php if ($success): ?>
            <script>
            Swal.fire({
                icon: "success",
                title: "Code Sent!",
                text: "<?php echo $success; ?>"
            });
            </script>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <div class="form-group">
                <input type="text" name="otp" id="otpInput" maxlength="6" placeholder="Enter 6-digit code" required 
                       pattern="[0-9]{6}" style="text-align: center; font-size: 18px; letter-spacing: 5px;"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            
            <button type="submit" name="verify" class="login-btn">Verify Email</button>
        </form>

        <form method="POST" style="margin-top: 15px;">
            <button type="submit" name="resend_otp" class="resend-btn">Resend Verification Code</button>
        </form>

        <div class="login-links">
            <a href="register.php">Use different email</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Remove query params after popup appears
if (window.location.search.includes("error=") || window.location.search.includes("success=")) {
    window.history.replaceState({}, document.title, "verify_otp.php");
}

// Auto-focus OTP input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('otpInput').focus();
});

// Optional: Show a visual indicator when 6 digits are entered
document.getElementById('otpInput').addEventListener('input', function(e) {
    if (this.value.length === 6) {
        const verifyBtn = document.querySelector('button[name="verify"]');
        verifyBtn.style.backgroundColor = '#28a745';
        verifyBtn.innerHTML = '✓ Verify Email';
        verifyBtn.focus();
    } else {
        const verifyBtn = document.querySelector('button[name="verify"]');
        verifyBtn.style.backgroundColor = '';
        verifyBtn.innerHTML = 'Verify Email';
    }
});
</script>

</body>
</html>