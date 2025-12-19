<?php
session_start();

$step = isset($_GET['step']) ? $_GET['step'] : "email";
$error = isset($_GET['error']) ? $_GET['error'] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<?php include 'nav.php'; ?>
<div class="login-wrapper">
  <div class="login-card">

    <?php if (!empty($error)) { ?>
        <p style="color:red; text-align:center;"><?= htmlspecialchars($error) ?></p>
    <?php } ?>

    <?php if ($step === "email") { ?>
        <h2>Forgot Password</h2>

        <form action="process_forgotpass.php" method="POST">
            <input type="email" name="email" placeholder="Enter your KLD email" required>
            <button class="login-btn" name="send_code">Send 6-Digit Code</button>
        </form>

        <div class="login-links">
            <a href="login.php">Back to Login</a>
        </div>
    <?php } ?>

    <?php if ($step === "code") { ?>
        <h2>Enter Verification Code</h2>

        <p style="text-align:center; color:#444;">
            A code was sent to <b><?= $_SESSION['temp_email'] ?></b>
        </p>

        <form action="process_verifycode.php" method="POST">
            <input type="text" name="code" maxlength="6" placeholder="Enter 6-digit code" required>
            <button class="login-btn">Verify Code</button>
        </form>

        <div class="login-links">
            <a href="forgotpass.php?step=email">Resend Code</a>
        </div>
    <?php } ?>

    <?php if ($step === "reset") { ?>
        <h2>Update Password</h2>

        <form action="process_updatepassword.php" method="POST">
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button class="login-btn">Update Password</button>
        </form>

        <div class="login-links">
            <a href="login.php">Back to Login</a>
        </div>
    <?php } ?>

  </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
