<?php
include "database.php";

// Delete OTPs older than 24 hours
$conn->query("DELETE FROM password_reset_otps WHERE expires_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
?>