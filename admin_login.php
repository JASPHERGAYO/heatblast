<?php
session_start();
include "connection.php";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM admins WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();

        if (password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: profile.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Admin does not exist!";
    }
}
?>
