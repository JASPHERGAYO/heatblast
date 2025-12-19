<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

function sendOTPEmail($email, $otp_code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0; // Set to 0 for production
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'qrviolationrecorder@gmail.com';
        $mail->Password = 'bwrf ymcl ucas zqla'; // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;

        // Recipients - FIXED: Use the same email as your Username
        $mail->setFrom('qrviolationrecorder@gmail.com', 'QR Violation Recorder Portal'); // ← CHANGE THIS LINE
        $mail->addAddress($email);
        $mail->addReplyTo('noreply@kld.edu.ph', 'QR VIOLATION RECORDER No Reply');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'QR VIOLATION RECORDER - Email Verification Code';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px; }
                .otp-code { font-size: 32px; font-weight: bold; text-align: center; margin: 30px 0; color: #007bff; letter-spacing: 5px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
                .note { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>QR Violation Recorder Email Verification</h1>
                </div>
                <p>Hello,</p>
                <p>Thank you for registering with QR Violation Recorder. Use the verification code below to complete your registration:</p>
                
                <div class='otp-code'>$otp_code</div>
                
                <div class='note'>
                    <strong>Note:</strong> This code will expire in 10 minutes.
                </div>
                
                <p>If you didn't request this code, please ignore this email.</p>
                
                <div class='footer'>
                    <p><strong>QR Violation Recorder</strong><br>
                    This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = " $otp_code\nThis code expires in 10 minutes.";

        return $mail->send();
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail($email, $code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0; // Set to 0 for production
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'qrviolationrecorder@gmail.com';
        $mail->Password = 'bwrf ymcl ucas zqla'; // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;

        // Recipients
        $mail->setFrom('qrviolationrecorder@gmail.com', 'QR VIOLATION RECORDER Portal');
        $mail->addAddress($email);
        $mail->addReplyTo('noreply@kld.edu.ph', 'QR Violation Recorder No Reply');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'QR Violation Recorder - Password Reset Code';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px; }
                .otp-code { font-size: 32px; font-weight: bold; text-align: center; margin: 30px 0; color: #007bff; letter-spacing: 5px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
                .note { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>QR Violation Recorder Password Reset</h1>
                </div>
                <p>Hello,</p>
                <p>You have requested to reset your password for your Qr Violation Recorder account. Use the verification code below to proceed:</p>
                
                <div class='otp-code'>$code</div>
                
                <div class='note'>
                    <strong>Note:</strong> This code will expire in 10 minutes.
                </div>
                
                <p>If you didn't request this code, please ignore this email.</p>
                
                <div class='footer'>
                    <p><strong>QR Violation Recorder</strong><br>
                    This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "QR Violation Recorder Password Reset Code: $code\nThis code expires in 10 minutes.";

        return $mail->send();
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
function sendViolationEmail($student_email, $student_name, $violation_type, $violation_description_text, $violation_category, $recorded_by, $converted = false) {
    $mail = new PHPMailer(true);

    try {
        // Set Philippine timezone
        date_default_timezone_set('Asia/Manila');
        
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'qrviolationrecorder@gmail.com';
        $mail->Password = 'bwrf ymcl ucas zqla';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;

        // Recipients
        $mail->setFrom('qrviolationrecorder@gmail.com', 'QR Violation Recorder Portal');
        $mail->addAddress($student_email);
        $mail->addReplyTo('noreply@kld.edu.ph', 'QR Violation Recorder No Reply');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Violation Recorded - QR Violation Recorder';
        
        // Get current Philippine time
        $current_time = date('F j, Y g:i A');
        
        // Simple email body with only violation type and time/date
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .info { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                .action { background: #d1ecf1; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ VIOLATION NOTICE</h1>
                    <p>QR Violation Recorder System</p>
                </div>
                
                <p><strong>Dear " . htmlspecialchars($student_name) . ",</strong></p>
                <p>A new violation has been recorded in your account:</p>
                
                <div class='info'>
                    <p><strong>Violation Type:</strong> " . htmlspecialchars($violation_type) . "</p>
                    <p><strong>Description:</strong> " . htmlspecialchars($violation_description_text) . "</p>
                    <p><strong>Category:</strong> " . strtoupper($violation_category) . "</p>
                    <p><strong>Recorded By:</strong> " . htmlspecialchars($recorded_by) . "</p>
                    <p><strong>Date & Time:</strong> " . $current_time . " (Philippine Time)</p>
                </div>
                
                " . ($converted ? "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <p style='color: #721c24; font-weight: bold;'>🚨 AUTO-CONVERSION NOTICE: Your 4th minor violation has been converted to a MAJOR violation.</p>
                </div>" : "") . "
                
                <div class='action'>
                    <h3>📌 REQUIRED ACTION</h3>
                    <p style='font-size: 18px; font-weight: bold;'>Please come to the <strong>ISASEC Office</strong> to discuss and resolve this violation.</p>
                   
                    <p><strong>Location:</strong> ISASEC Office, 1st Building, 2nd Floor</p>
                    <p><strong>Hours:</strong> Monday-Saturday, 8:00 AM - 5:00 PM</p>
                </div>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; text-align: center;'>
                    <p><strong>QR Violation Recorder System</strong><br>
                    KLD Student Affairs - ISASEC Office<br>
                    Automated notification - Do not reply</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text version
        $mail->AltBody = "NEW VIOLATION NOTICE\n\nDear $student_name,\n\nA violation has been recorded:\nType: $violation_type\nDescription: $violation_description_text\nCategory: $violation_category\nRecorded By: $recorded_by\nDate & Time: $current_time (Philippine Time)\n\n" . ($converted ? "AUTO-CONVERSION: Your 4th minor violation converted to MAJOR.\n\n" : "") . "ACTION REQUIRED: Come to ISASEC Office within 3 working days.\nLocation: ISASEC Office, KLD Campus\nHours: Mon-Fri, 8AM-5PM";

        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Violation Email Error: " . $mail->ErrorInfo);
        return false;
    }
}// Add this function to your email_config.php file (after other email functions)
function sendSanctionCompletedEmail($student_email, $student_name, $violation_type, $violation_description, 
                                  $violation_category, $sanction_type, $violation_date, $completion_date,
                                  $counselor_notes, $recorded_by, $due_date = null, $hours_completed = null) {
    $mail = new PHPMailer(true);

    try {
        // Set Philippine timezone
        date_default_timezone_set('Asia/Manila');
        
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'qrviolationrecorder@gmail.com';
        $mail->Password = 'bwrf ymcl ucas zqla';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;

        // Recipients
        $mail->setFrom('qrviolationrecorder@gmail.com', 'QR Violation Recorder Portal');
        $mail->addAddress($student_email);
        $mail->addReplyTo('noreply@kld.edu.ph', 'QR Violation Recorder No Reply');

        // Content
        $mail->isHTML(true);
        $mail->Subject = '✅ Sanction Completed - Violation Resolved';
        
        // Format dates
        $violation_date_formatted = date('F j, Y g:i A', strtotime($violation_date));
        $completion_date_formatted = date('F j, Y g:i A', strtotime($completion_date));
        $due_date_formatted = $due_date ? date('F j, Y', strtotime($due_date)) : 'Not specified';
        $current_time = date('F j, Y g:i A');
        
        // Email body
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .info { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                .completion-info { background: #d1ecf1; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .success-badge { display: inline-block; padding: 5px 15px; background: #28a745; color: white; border-radius: 20px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ SANCTION COMPLETED</h1>
                    <p>QR Violation Recorder System</p>
                </div>
                
                <p><strong>Dear " . htmlspecialchars($student_name) . ",</strong></p>
                <p>We are pleased to inform you that your sanction has been successfully completed and your violation has been resolved.</p>
                
                <div class='info'>
                    <h3>📋 Violation Details</h3>
                    <p><strong>Violation Type:</strong> " . htmlspecialchars($violation_type) . "</p>
                    <p><strong>Description:</strong> " . htmlspecialchars($violation_description) . "</p>
                    <p><strong>Category:</strong> " . strtoupper($violation_category) . "</p>
                    <p><strong>Date Recorded:</strong> " . $violation_date_formatted . "</p>
                    <p><strong>Sanction Type:</strong> " . htmlspecialchars($sanction_type) . "</p>
                    <p><strong>Due Date:</strong> " . $due_date_formatted . "</p>
                </div>
                
                <div class='completion-info'>
                    <h3>✅ Completion Details</h3>
                    <p><strong>Completion Status:</strong> <span class='success-badge'>COMPLETED</span></p>
                    <p><strong>Completed On:</strong> " . $completion_date_formatted . "</p>
                    " . ($hours_completed ? "<p><strong>Hours Completed:</strong> " . htmlspecialchars($hours_completed) . "</p>" : "") . "
                    <p><strong>Recorded By:</strong> " . htmlspecialchars($recorded_by) . "</p>
                    <p><strong>Notification Date:</strong> " . $current_time . " (Philippine Time)</p>
                </div>
                
                " . ($counselor_notes ? "<div class='info'>
                    <h3>📝 Counselor Notes</h3>
                    <p>" . nl2br(htmlspecialchars($counselor_notes)) . "</p>
                </div>" : "") . "
                
                <div style='background: #e8f5e8; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #28a745;'>✅ VIOLATION RESOLVED</h3>
                    <p style='font-size: 16px;'>Your record has been cleared. Thank you for fulfilling your sanction requirements.</p>
                    <p><strong>Keep up the good behavior!</strong></p>
                </div>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; text-align: center;'>
                    <p><strong>QR Violation Recorder System</strong><br>
                    KLD Student Affairs - ISASEC Office<br>
                    Automated notification - Do not reply</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text version
        $plain_text = "SANCTION COMPLETED NOTIFICATION\n\n";
        $plain_text .= "Dear $student_name,\n\n";
        $plain_text .= "Your sanction has been successfully completed and your violation has been resolved.\n\n";
        $plain_text .= "VIOLATION DETAILS:\n";
        $plain_text .= "Type: $violation_type\n";
        $plain_text .= "Description: $violation_description\n";
        $plain_text .= "Category: $violation_category\n";
        $plain_text .= "Date Recorded: $violation_date_formatted\n";
        $plain_text .= "Sanction Type: $sanction_type\n";
        $plain_text .= "Due Date: $due_date_formatted\n\n";
        $plain_text .= "COMPLETION DETAILS:\n";
        $plain_text .= "Status: COMPLETED\n";
        $plain_text .= "Completed On: $completion_date_formatted\n";
        if ($hours_completed) $plain_text .= "Hours Completed: $hours_completed\n";
        $plain_text .= "Recorded By: $recorded_by\n";
        $plain_text .= "Notification Date: $current_time (Philippine Time)\n\n";
        if ($counselor_notes) $plain_text .= "COUNSELOR NOTES:\n$counselor_notes\n\n";
        $plain_text .= "✅ VIOLATION RESOLVED\n";
        $plain_text .= "Your record has been cleared. Thank you for fulfilling your sanction requirements.\n";
        $plain_text .= "Keep up the good behavior!\n\n";
        $plain_text .= "QR Violation Recorder System\nKLD Student Affairs - ISASEC Office\nAutomated notification";
        
        $mail->AltBody = $plain_text;

        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Sanction Completion Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
// Add this function to your email_config.php file
function sendContactFormEmail($name, $email, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'qrviolationrecorder@gmail.com';
        $mail->Password = 'bwrf ymcl ucas zqla';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;
        
        date_default_timezone_set('Asia/Manila');
        // Recipients - email to your team
        $mail->setFrom('noreply@qrviolationrecorder.com', 'QR Violation Recorder Contact Form');
        $mail->addAddress('qrviolationrecorder@gmail.com'); // Your team email
        $mail->addReplyTo($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Contact Form: $subject";
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #10B981; color: white; padding: 20px; text-align: center; }
                .info { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Contact Form Submission</h1>
                    <p>QR Violation Recorder Website</p>
                </div>
                
                <div class='info'>
                    <h3>📋 Contact Details</h3>
                    <p><strong>From:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Subject:</strong> $subject</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y g:i A') . "</p>
                </div>
                
                <div class='info'>
                    <h3>📝 Message</h3>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "New Contact Form Submission\n\nFrom: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Contact Form Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Auto-reply function for contact form
function sendContactAutoReply($toEmail, $toName) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'qrviolationrecorder@gmail.com';
        $mail->Password = 'bwrf ymcl ucas zqla';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;
        
        // Recipients
        date_default_timezone_set('Asia/Manila');
        $mail->setFrom('qrviolationrecorder@gmail.com', 'QR Violation Recorder');
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo('qrviolationrecorder@gmail.com', 'QR Violation Recorder');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Thank You for Contacting QR Violation Recorder";
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #10B981; color: white; padding: 20px; text-align: center; border-radius: 10px; }
                .content { margin: 20px 0; padding: 20px; background: #f9fafb; border-radius: 5px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Thank You for Contacting Us!</h1>
                </div>
                
                <p>Dear $toName,</p>
                
                <div class='content'>
                    <p>We've successfully received your message and our team will review it shortly. We typically respond within 24 hours on business days.</p>
                    
                    <p>Here's a summary of what happens next:</p>
                    <ul>
                        <li>Our team reviews your inquiry</li>
                        <li>We'll respond via email to $toEmail</li>
                        <li>If needed, we may schedule a follow-up call</li>
                    </ul>
                    
                    <p><strong>Reference:</strong> Inquiry received on " . date('F j, Y') . "</p>
                </div>
                
                <div class='content'>
                    <h3>📞 Alternative Contact Methods</h3>
                    <p><strong>Email:</strong> qrviolationrecorder@gmail.com</p>
                    <p><strong>Phone:</strong> +63 912 345 6789 (Mon-Fri, 9AM-6PM)</p>
                </div>
                
                <div class='footer'>
                    <p><strong>QR Violation Recorder Team</strong></p>
                    <p><em>Innovating campus discipline management through technology</em></p>
                    <p>This is an automated message. Please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Thank you for contacting QR Violation Recorder!\n\nDear $toName,\n\nWe've received your message and will respond within 24 hours on business days.\n\nReference: Inquiry received on " . date('F j, Y') . "\n\nAlternative contact:\nEmail: qrviolationrecorder@gmail.com\nPhone: +63 912 345 6789\n\nQR Violation Recorder Team\nThis is an automated message.";
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Contact Auto-reply Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

?>