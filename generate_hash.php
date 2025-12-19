<?php
$password = "admin123";
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Use this hash in SQL: " . $hash;
?>