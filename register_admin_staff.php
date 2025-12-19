<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is already logged in
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin/Staff Registration</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login.css">
     <link rel="stylesheet" href="role_styles.css">  
    <style>
        /* Remove duplicate password-container styles from login.css */
        .registration-form .password-container {
            margin-bottom: 0;
        }
        
        .show-password-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: -5px 0 25px 0; /* Adjusted spacing */
        }
        
        .show-password-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
        }
        
        .show-password-row label {
            color: #333;
            cursor: pointer;
            font-size: 14px;
        }
        
        /* Add spacing between form fields */
        .registration-form input[type="email"],
        .registration-form input[type="password"],
        .registration-form input[type="text"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px; /* Space between fields */
            border-radius: 10px;
            border: 1px solid #c9e8db;
            font-size: 16px;
            outline: none;
            transition: 0.25s;
            box-sizing: border-box;
        }
        
        /* Focus state */
        .registration-form input[type="email"]:focus,
        .registration-form input[type="password"]:focus,
        .registration-form input[type="text"]:focus {
            border-color: var(--green);
            box-shadow: 0 0 5px rgba(0, 196, 118, 0.4);
        }
        
        /* Specific spacing for confirm password field */
        .registration-form input[name="confirm_password"] {
            margin-bottom: 12px; /* Less space before checkbox */
        }
        
        /* Remove bottom margin from password field in container */
        .registration-form .password-container input {
            margin-bottom: 0;
        }
        
        /* Add space between password and confirm password */
        .registration-form .password-container + input[name="confirm_password"] {
            margin-top: 18px; /* Space between password and confirm */
        }
        
        /* Button spacing */
        .registration-form .login-btn {
            margin-top: 5px; /* Space after checkbox */
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="role-selection">
        <h2 class="role-title">Admin/Staff Registration</h2>
        
        <!-- Role Selection -->
        <div class="role-buttons">
            <button class="role-btn admin-btn" onclick="selectRole('admin')">
                <i class="fas fa-user-shield role-icon"></i>
                Admin Account
            </button>
            
            <button class="role-btn staff-btn" onclick="selectRole('staff')">
                <i class="fas fa-user-tie role-icon"></i>
                Staff Account
            </button>
        </div>
        
        <!-- Admin Registration Form -->
        <div id="admin-form" class="registration-form">
            <h3 style="text-align: center; color: #e74c3c; margin-bottom: 25px;">Admin Registration</h3>
            
            <?php if (isset($_GET['error'])): ?>
                <p style="color:red; text-align:center; margin-bottom:20px;">
                    <?php
                        switch ($_GET['error']) {
                            case "exists":
                                echo "This email is already registered.";
                                break;
                            case "kld":
                                echo "Email must end with @kld.edu.ph";
                                break;
                            case "password_length":
                                echo "Password must be at least 8 characters long.";
                                break;
                            case "special_chars":
                                echo "Password cannot contain @, #, or $ characters.";
                                break;
                        }
                    ?>
                </p>
            <?php endif; ?>
            
            <form action="process_register_admin_staff.php" method="POST" onsubmit="return validatePassword()">
                <input type="hidden" name="role" value="admin">
                
                <input type="email" name="email" placeholder="admin@kld.edu.ph" required>
                
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required maxlength="50">
                </div>
                
                <input type="password" name="confirm_password" id="confirm_password_admin" placeholder="Confirm Password" required>
                
                <!-- Show Password Checkbox -->
                <div class="show-password-row">
                    <input type="checkbox" id="showPasswordAdmin" onclick="togglePasswords('admin')">
                    <label for="showPasswordAdmin">Show Password</label>
                </div>
                
                <button class="login-btn" type="submit">Register as Admin</button>
            </form>
        </div>
        
        <!-- Staff Registration Form -->
        <div id="staff-form" class="registration-form">
            <h3 style="text-align: center; color: #3498db; margin-bottom: 25px;">Staff Registration</h3>
            
            <?php if (isset($_GET['error'])): ?>
                <p style="color:red; text-align:center; margin-bottom:20px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </p>
            <?php endif; ?>
            
            <form action="process_register_admin_staff.php" method="POST" onsubmit="return validatePassword()">
                <input type="hidden" name="role" value="staff">
                
                <input type="email" name="email" placeholder="staff@kld.edu.ph" required>
                
                <div class="password-container">
                    <input type="password" name="password" id="password_staff" placeholder="Password" required maxlength="50">
                </div>
                
                <input type="password" name="confirm_password" id="confirm_password_staff" placeholder="Confirm Password" required>
                
                <!-- Show Password Checkbox -->
                <div class="show-password-row">
                    <input type="checkbox" id="showPasswordStaff" onclick="togglePasswords('staff')">
                    <label for="showPasswordStaff">Show Password</label>
                </div>
                
                <button class="login-btn" type="submit">Register as Staff</button>
            </form>
        </div>
        
        <a href="choose_role.php" class="back-link">← Back to Role Selection</a>
    </div>

    <?php include 'footer.php'; ?>
                
   <script>
function selectRole(role) {
    // Remove selected class from all buttons
    document.querySelectorAll('.role-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Add selected class to clicked button
    if (role === 'admin') {
        document.querySelector('.admin-btn').classList.add('selected');
    } else {
        document.querySelector('.staff-btn').classList.add('selected');
    }
    
    // Hide all forms
    document.querySelectorAll('.registration-form').forEach(form => {
        form.classList.remove('active');
    });
    
    // Show selected form
    document.getElementById(role + '-form').classList.add('active');
}

function validatePassword() {
    // Get the active form
    const activeForm = document.querySelector('.registration-form.active');
    if (!activeForm) return false;
    
    let passwordField, confirmPasswordField;
    
    if (activeForm.id === 'admin-form') {
        passwordField = document.getElementById('password');
        confirmPasswordField = document.getElementById('confirm_password_admin');
    } else {
        passwordField = document.getElementById('password_staff');
        confirmPasswordField = document.getElementById('confirm_password_staff');
    }
    
    if (!passwordField || !confirmPasswordField) {
        alert('Password fields not found');
        return false;
    }
    
    const password = passwordField.value;
    const confirmPassword = confirmPasswordField.value;
    const forbiddenChars = ['@', '#', '$'];
    
    // Check minimum length
    if (password.length < 8) {
        alert('Password must be at least 8 characters long.');
        return false;
    }
    
    // Check for forbidden characters
    for (let char of forbiddenChars) {
        if (password.includes(char)) {
            alert('Password cannot contain these special characters: @, #, $');
            return false;
        }
    }
    
    // Check password confirmation
    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return false;
    }
    
    return true;
}

// Toggle passwords for a specific form
function togglePasswords(formType) {
    let passwordField, confirmField, checkbox;
    
    if (formType === 'admin') {
        passwordField = document.getElementById('password');
        confirmField = document.getElementById('confirm_password_admin');
        checkbox = document.getElementById('showPasswordAdmin');
    } else {
        passwordField = document.getElementById('password_staff');
        confirmField = document.getElementById('confirm_password_staff');
        checkbox = document.getElementById('showPasswordStaff');
    }
    
    if (checkbox.checked) {
        passwordField.type = 'text';
        confirmField.type = 'text';
    } else {
        passwordField.type = 'password';
        confirmField.type = 'password';
    }
}

// Real-time password validation
document.addEventListener('input', function(e) {
    if (e.target.type === 'password' && 
        (e.target.name === 'password' || e.target.id === 'password_staff' || 
         e.target.id === 'confirm_password_admin' || e.target.id === 'confirm_password_staff')) {
        const password = e.target.value;
        const forbiddenChars = ['@', '#', '$'];
        
        // Remove forbidden characters in real-time
        let cleanedPassword = password;
        forbiddenChars.forEach(char => {
            cleanedPassword = cleanedPassword.split(char).join('');
        });
        
        // Update the input value if characters were removed
        if (cleanedPassword !== password) {
            e.target.value = cleanedPassword;
        }
    }
});

// Auto-select admin role on page load
document.addEventListener('DOMContentLoaded', function() {
    selectRole('admin');
});
</script>
</body>
</html>