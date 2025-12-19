<?php
session_start();
include 'database.php';

// Check if user is logged in as student ONLY
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prevent admin/staff from accessing this page
if (isset($_SESSION['admin_logged_in']) || isset($_SESSION['staff_logged_in'])) {
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Load current user data from users table
$q = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$q->bind_param("i", $user_id);
$q->execute();
$user = $q->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

$email = $user['email'] ?? ''; // FIXED: Use $user not $user_result

// Get firstname from student_profiles table
$name_q = $conn->prepare("SELECT firstname FROM student_profiles WHERE user_id = ?");
$name_q->bind_param("i", $user_id);
$name_q->execute();
$name_result = $name_q->get_result()->fetch_assoc();

// Define $firstname variable
$firstname = $name_result['firstname'] ?? 'Student';

// Handle password change only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    
    // Verify current password - check against users table
    $check_q = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $check_q->bind_param("i", $user_id);
    $check_q->execute();
    $result = $check_q->get_result()->fetch_assoc();
    
    if ($result && password_verify($current_password, $result['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_q = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $pass_q->bind_param("si", $hashed_password, $user_id);
                
                if ($pass_q->execute()) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password: " . $conn->error;
                }
            } else {
                $error_message = "Password must be at least 8 characters long.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Student</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #4caf50;
            --accent-blue: #e8f4fd;
            --accent-yellow: #fff3cd;
            --white: #FFFFFF;
            --light-bg: #F9FAFB;
            --gray-light: #E5E7EB;
            --gray: #6B7280;
            --gray-dark: #374151;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--gray-dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .password-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
            width: 100%;
        }

        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 25px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .card-header h1 {
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-body {
            padding: 30px;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 20px;
            }
            
            .card-header {
                padding: 20px;
            }
            
            .card-header h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .password-container {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .card-body {
                padding: 15px;
            }
        }

        /* Info Box */
        .info-box {
            background: var(--accent-blue);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #2196F3;
        }

        .info-box h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #1976d2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-box p {
            margin: 0;
            color: #37474f;
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message i {
            font-size: 1.2rem;
        }

        /* Profile Info */
        .profile-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid var(--gray-light);
        }

        .profile-info h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            background: var(--white);
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid var(--gray-light);
        }

        .info-item strong {
            color: var(--primary-green);
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            color: var(--gray-dark);
            font-size: 1rem;
        }

        /* Form Section */
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid var(--gray-light);
        }

        .form-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-requirements {
            background: var(--accent-yellow);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }

        .password-requirements strong {
            color: #856404;
            display: block;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements ul {
            margin: 8px 0;
            padding-left: 20px;
            color: #856404;
        }

        .password-requirements li {
            margin-bottom: 4px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-dark);
        }

        .input-with-icon {
            position: relative;
            width: 100%;
        }

        .input-with-icon input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid var(--gray-light);
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
            box-sizing: border-box;
        }

        .input-with-icon input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--primary-green);
        }

        /* Password Strength Indicator */
        .password-strength {
            height: 4px;
            background: var(--gray-light);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak {
            background: #dc3545;
            width: 33%;
        }

        .strength-medium {
            background: #ffc107;
            width: 66%;
        }

        .strength-strong {
            background: #28a745;
            width: 100%;
        }

        .strength-text {
            font-size: 0.85rem;
            margin-top: 5px;
            color: var(--gray);
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            min-width: 180px;
        }

        .btn-primary {
            background: var(--primary-green);
            color: var(--white);
            border: 2px solid var(--primary-green);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
        }

        .btn-secondary:hover {
            background: var(--primary-green);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Responsive adjustments for buttons */
        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                min-width: auto;
            }
        }

        /* Responsive adjustments for form */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .input-with-icon input {
                padding: 14px 45px 14px 15px;
            }
        }

        @media (max-width: 480px) {
            .form-section {
                padding: 15px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 15px;
            }
            
            .info-box, .profile-info, .form-section {
                padding: 15px;
            }
        }

        /* Password match indicator */
        .password-match {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
            font-size: 0.9rem;
            opacity: 0;
            transition: var(--transition);
        }

        .password-match.visible {
            opacity: 1;
        }

        .password-match.match {
            color: var(--primary-green);
        }

        .password-match.no-match {
            color: #dc3545;
        }

        /* Show password row */
        .show-password-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }

        .show-password-row input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
        }

        .show-password-row label {
            color: var(--gray-dark);
            cursor: pointer;
            user-select: none;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="password-container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-key"></i> Change Password</h1>
        </div>
        
        <div class="card-body">
            <!-- Information Box -->
            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> Student Account</h3>
                <p>You can change your password here. For security reasons, personal information cannot be modified by students.</p>
            </div>
            
            <?php if ($success_message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <!-- Display User Information -->
            <div class="profile-info">
                <h3><i class="fas fa-user"></i> Student Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Name</strong>
                        <span><?= htmlspecialchars($firstname) ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email</strong>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Password Change Form -->
            <div class="form-section">
                <h2><i class="fas fa-lock"></i> Change Your Password</h2>
                
                <div class="password-requirements">
                    <strong><i class="fas fa-shield-alt"></i> Password Requirements:</strong>
                    <ul>
                        <li>Minimum 8 characters long</li>
                        <li>Include uppercase and lowercase letters</li>
                        <li>Include numbers and special characters for stronger security</li>
                        <li>Avoid common words or personal information</li>
                    </ul>
                </div>
                
                <form method="POST" id="passwordForm">
                    <!-- Current Password -->
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="input-with-icon">
                            <input type="password" id="current_password" name="current_password" required 
                                   placeholder="Enter your current password" autocomplete="current-password">
                            <button type="button" class="toggle-password" data-target="current_password">
                            
                            </button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-with-icon">
                            <input type="password" id="new_password" name="new_password" required minlength="8" 
                                   placeholder="Enter new password (min. 8 characters)" autocomplete="new-password">
                            <button type="button" class="toggle-password" data-target="new_password">
                           
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                    
                    <!-- Confirm New Password -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-with-icon">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" 
                                   placeholder="Confirm your new password" autocomplete="new-password">
                            <button type="button" class="toggle-password" data-target="confirm_password">
                          
                            </button>
                        </div>
                        <div class="password-match" id="passwordMatch">
                            <i class="fas fa-check-circle"></i>
                            <span>Passwords match</span>
                        </div>
                    </div>
                    
                    <!-- Show All Passwords Checkbox -->
                    <div class="show-password-row">
                        <input type="checkbox" id="showAllPasswords">
                        <label for="showAllPasswords">Show all passwords</label>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                        
                        <a href="profile.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Toggle password visibility for individual fields
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Show all passwords checkbox
const showAllCheckbox = document.getElementById('showAllPasswords');
if (showAllCheckbox) {
    showAllCheckbox.addEventListener('change', function() {
        const passwordInputs = [
            document.getElementById('current_password'),
            document.getElementById('new_password'),
            document.getElementById('confirm_password')
        ];
        
        const showAll = this.checked;
        document.querySelectorAll('.toggle-password i').forEach(icon => {
            if (showAll) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        passwordInputs.forEach(input => {
            if (input) {
                input.type = showAll ? 'text' : 'password';
            }
        });
    });
}

// Password strength checker
const newPasswordInput = document.getElementById('new_password');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

function checkPasswordStrength(password) {
    let score = 0;
    
    // Length check
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    
    // Character variety checks
    if (/[a-z]/.test(password)) score++; // lowercase
    if (/[A-Z]/.test(password)) score++; // uppercase
    if (/[0-9]/.test(password)) score++; // numbers
    if (/[^A-Za-z0-9]/.test(password)) score++; // special characters
    
    return score;
}

function updateStrengthIndicator(password) {
    const strength = checkPasswordStrength(password);
    
    // Reset
    strengthBar.className = 'strength-bar';
    strengthText.textContent = '';
    
    if (password.length === 0) {
        return;
    }
    
    if (strength < 3) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#dc3545';
    } else if (strength < 5) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Medium strength';
        strengthText.style.color = '#ffc107';
    } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#28a745';
    }
}

if (newPasswordInput) {
    newPasswordInput.addEventListener('input', function() {
        updateStrengthIndicator(this.value);
        checkPasswordMatch();
    });
}

// Password match checker
const confirmPasswordInput = document.getElementById('confirm_password');
const passwordMatchDiv = document.getElementById('passwordMatch');

function checkPasswordMatch() {
    const password = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    
    if (password === '' && confirmPassword === '') {
        passwordMatchDiv.className = 'password-match';
        return;
    }
    
    if (confirmPassword === '') {
        passwordMatchDiv.className = 'password-match';
        return;
    }
    
    if (password === confirmPassword) {
        passwordMatchDiv.className = 'password-match visible match';
        passwordMatchDiv.innerHTML = '<i class="fas fa-check-circle"></i><span>Passwords match</span>';
    } else {
        passwordMatchDiv.className = 'password-match visible no-match';
        passwordMatchDiv.innerHTML = '<i class="fas fa-times-circle"></i><span>Passwords do not match</span>';
    }
}

if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
}

// Form validation
const form = document.getElementById('passwordForm');
if (form) {
    form.addEventListener('submit', function(event) {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const currentPassword = document.getElementById('current_password').value;
        
        // Check if current password is filled
        if (!currentPassword.trim()) {
            event.preventDefault();
            alert('Please enter your current password.');
            return;
        }
        
        // Check password length
        if (password.length < 8) {
            event.preventDefault();
            alert('Password must be at least 8 characters long.');
            return;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            event.preventDefault();
            alert('New passwords do not match. Please confirm your new password.');
            return;
        }
        
        // Check if new password is different from current
        if (password === currentPassword) {
            event.preventDefault();
            alert('New password must be different from current password.');
            return;
        }
    });
}
</script>
</body>
</html>