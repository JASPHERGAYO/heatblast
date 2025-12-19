<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>

    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login.css">
    <!-- Add Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
   
    <style>
        .show-password-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: -10px 0 20px 0;
}

.show-password-row input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

.show-password-row label {
    color: #333;
    cursor: pointer;
}
    </style>
</head>

<body>

<?php include 'nav.php'; ?>

<div class="login-wrapper">
    <div class="login-card">
        
        <h2>Create an Account</h2>

        <!-- ERROR MESSAGES -->
        <?php if (isset($_GET['error'])): ?>
            <p style="color:red; text-align:center; margin-bottom:15px;">
                <?php
                    switch ($_GET['error']) {
                        case "invalid":
                            echo "Please enter a valid email.";
                            break;
                        case "exists":
                            echo "This email is already registered.";
                            break;
                        case "agree":
                            echo "You must accept the Terms and Conditions.";
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

        <form action="process_register.php" method="POST" onsubmit="return validatePassword()">

            <input type="email" name="email" placeholder="yourname@kld.edu.ph" required>

           <div class="password-container">
    <input type="password" name="password" id="password" placeholder="Password" required maxlength="50">
</div>

<div class="show-password-row">
    <input type="checkbox" id="showPassword" name="showPassword" onclick="togglePassword()">
    <label for="showPassword">Show Password</label>
</div><div class="terms-row">
                <input type="checkbox" id="terms" name="agree">

                <label for="terms">
                    I agree to the  <a href="#" id="openTerms">Terms and Conditions</a>
                </label>
            </div>

            <button class="login-btn" type="submit">Create Account</button>
        </form>
                      
        <!-- Admin/Staff Link -->
        <div class="admin-staff-link" style="display: flex; justify-content: center; margin: 20px 0;">
            <a href="choose_role.php" class="admin-staff-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                <i class="fas fa-user-shield"></i>
                Register as Admin/Staff
            </a>
        </div>
        
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- TERMS MODAL -->
<div id="termsModal" class="modal-overlay">
    <div class="modal-box">
        <h2>Terms and Conditions</h2>
        <p><strong>Effective Date:</strong> October 21, 2025</p>

        <p>Welcome to QR Violation Recorder — a student-developed system created for academic purposes within Kolehiyo ng Lungsod ng Dasmariñas / ISASEC. By using this platform, you agree to comply with the following terms and conditions.</p>

        <h3>1. Purpose of the Website</h3>
        <p>This website is designed to demonstrate how student rule violations can be recorded and monitored through QR technology. The system is managed by authorized school personnel such as guards and reviewed by ISASEC officers during designated review sessions. This platform is strictly for educational demonstration only and not an official disciplinary system.</p>

        <h3>2. Who Can Use This Website</h3>
        <p>Only authorized staff may log in and manage violation data. Students do not have full access unless permitted.</p>

        <h3>3. How It Works</h3>
        <p>QR scanning, violation recording, and periodic review. All entries are securely stored.</p>

        <h3>4. Data Privacy and Confidentiality</h3>
        <p>Data is stored securely, not shared externally, and contains no sensitive info like contacts or addresses.</p>

        <h3>5. Limitations and Disclaimer</h3>
        <p>This is an educational prototype and data may be reset or modified during testing.</p>

        <h3>6. User Responsibilities</h3>
        <ul>
            <li>Use the system ethically</li>
            <li>Record violations accurately</li>
            <li>Do not falsify student data</li>
        </ul>

        <h3>7. Project Ownership and Development</h3>
        <p>Created by students of Kolehiyo ng Lungsod ng Dasmariñas under Mark Christopher Borja.</p>

        <h3>8. Changes to Terms</h3>
        <p>This page may be updated as the project develops.</p>

        <h3>9. Contact Information</h3>
        <p>For questions, contact the project team via the supervising instructor or official school email.</p>

        <button id="closeTerms" class="close-btn">Close</button>
    </div>
</div>

<script>
// Password validation function
function validatePassword() {
    const password = document.getElementById('password').value;
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
    
    return true;
}

// Real-time password validation
document.getElementById('password').addEventListener('input', function(e) {
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
});


function togglePassword() {
    const passwordInput = document.getElementById('password');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
}

</script>
</body>
</html>