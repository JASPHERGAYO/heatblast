<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'email_config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($message)) $errors[] = "Message is required.";
    
    // If no errors, send email
    if (empty($errors)) {
        // Send email to your team
        if (sendContactFormEmail($name, $email, $subject, $message)) {
            // Send auto-reply to user
            sendContactAutoReply($email, $name);
            
            // Set success message
            $_SESSION['feedback_sent'] = true;
            header('Location: index.php#contact');
            exit();
        } else {
            $_SESSION['contact_error'] = "Failed to send message. Please try again.";
        }
    } else {
        $_SESSION['contact_errors'] = $errors;
    }
    
    header('Location: index.php#contact');
    exit();
} else {
    header('Location: index.php');
    exit();
}
?>