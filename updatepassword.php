<?php include 'nav.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
<div class="login-wrapper">
  <div class="login-card">

    <?php if (isset($_GET['success'])) { ?>
      <p style="text-align:center; color:green; font-weight:600; margin-bottom:10px;">
        Password updated successfully!
      </p>
    <?php } ?>

    <h2>Update Password</h2>

    <form action="process_updatepassword.php" method="POST">
      <input type="password" name="new_password" placeholder="New Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>

      <button class="login-btn">Update Password</button>
    </form>

    <div class="login-links">
      <a href="login.php">Back to Login</a>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
