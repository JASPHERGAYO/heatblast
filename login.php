<?php
// THIS MUST BE AT THE VERY TOP - BEFORE ANY OUTPUT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); // Start output buffering

// If user is already logged in, redirect to profile
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_db.php");
    exit;
}
if (isset($_SESSION['staff_id'])) {
    header("Location: staff_db.php");
    exit;
}

// Check if admin login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Check admin credentials from database
    require_once 'database.php';
    
    try {
        $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $db_password = $admin['password'];
            
            // Check if password is hashed or plain text
            if (password_verify($password, $db_password)) {
                // Password is hashed and matches
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_id'] = $admin['id'];
                header("Location: admin_db.php");
                exit();
            } elseif ($password === $db_password) {
                // Password is plain text and matches
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_id'] = $admin['id'];
                header("Location: admin_db.php");
                exit();
            } else {
                $login_error = "Invalid admin password!";
            }
        } else {
            $login_error = "No admin found with that email!";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $login_error = "Database error!";
    }
}

// Check if staff login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Check staff credentials from database
    require_once 'database.php';
    
    try {
        $stmt = $conn->prepare("SELECT id, email, password FROM staff WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $staff = $result->fetch_assoc();
            $db_password = $staff['password'];
            
            // Check if password is hashed or plain text
            if (password_verify($password, $db_password)) {
                // Password is hashed and matches
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_email'] = $staff['email'];
                $_SESSION['staff_id'] = $staff['id'];
                header("Location: staff_db.php");
                exit();
            } elseif ($password === $db_password) {
                // Password is plain text and matches
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_email'] = $staff['email'];
                $_SESSION['staff_id'] = $staff['id'];
                header("Location: staff_db.php");
                exit();
            } else {
                $staff_error = "Invalid staff password!";
            }
        } else {
            $staff_error = "No staff found with that email!";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $staff_error = "Database error!";
    }
}

// Check if student login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_login'])) {
    require_once 'database.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $student_error = "Please fill in all fields";
    } else {
        /* ============================================================
           CHECK USER LOGIN
        ============================================================ */
        $userQuery = $conn->prepare("
            SELECT id, email, password, completed_setup, profile_completed 
            FROM users 
            WHERE email = ? 
            LIMIT 1
        ");
        $userQuery->bind_param("s", $email);
        $userQuery->execute();
        $userResult = $userQuery->get_result();

        if ($userResult->num_rows === 0) {
            $student_error = "Email not found";
        } else {
            $user = $userResult->fetch_assoc();

            // Check if email is verified (completed_setup = 1)
            if ((int)$user['completed_setup'] === 0) {
                $student_error = "Email not verified. Please check your email for verification code.";
            } else if (!password_verify($password, $user['password'])) {
                $student_error = "Invalid password";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];

                // Check if profile is completed
                if ((int)$user['profile_completed'] === 0) {
                    header("Location: first_setup.php");
                    exit;
                }

                // Both email verified and profile completed - go to profile
                header("Location: profile.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pixel Wizard Co. - Login</title>
    <style>
        /* ===========================================
           ROOT VARIABLES
        =========================================== */
        :root {
            --green: #00c476;
            --nav-height: 64px;
        }

        /* ===========================================
           GLOBAL (SAFE)
        =========================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f3fffb;
            color: #333;
            line-height: 1.6;
        }

        /* ===========================================
           LOGIN PAGE STYLES
        =========================================== */

        /* Wrapper around auth card */
        .login-wrapper {
            min-height: calc(100vh - var(--nav-height));
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Auth card */
        .login-card {
            width: 450px;
            padding: 32px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            animation: fadeLogin 0.5s ease;
        }

        .login-card h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 30px;
            font-weight: 700;
            color: #0e8f5c;
        }

        .login-card p {
            text-align: center;
            margin-bottom: 25px;
            color: #666;
        }

        /* User Type Buttons */
        .user-type-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }

        .user-btn {
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .user-btn i {
            font-size: 20px;
        }

        .admin-btn {
            background-color: #e74c3c;
            color: white;
        }

        .admin-btn:hover {
            background-color: #c0392b;
            transform: scale(1.02);
        }

        .staff-btn {
            background-color: #3498db;
            color: white;
        }

        .staff-btn:hover {
            background-color: #2980b9;
            transform: scale(1.02);
        }

        .student-btn {
            background-color: #2ecc71;
            color: white;
        }

        .student-btn:hover {
            background-color: #27ae60;
            transform: scale(1.02);
        }

        /* Inputs for LOGIN ONLY */
        .login-card input {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px;
            border-radius: 10px;
            border: 1px solid #c9e8db;
            font-size: 16px;
            outline: none;
            transition: 0.25s;
        }

        .login-card input:focus {
            border-color: var(--green);
            box-shadow: 0 0 5px rgba(0,196,118,0.4);
        }

        /* Password container */
        .password-container {
            position: relative;
            margin-bottom: 18px;
        }

        .password-container input {
            width: 100%;
            padding: 14px 45px 14px 14px;
            border-radius: 10px;
            border: 1px solid #c9e8db;
            font-size: 16px;
            outline: none;
            transition: 0.25s;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: var(--green);
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #029d63;
            transform: scale(1.04);
        }

        /* Links */
        .login-links {
            margin-top: 16px;
            text-align: center;
        }

        .login-links a {
            color: #0e8f5c;
            text-decoration: none;
            font-size: 15px;
            display: block;
            margin-top: 6px;
        }

        .login-links a:hover {
            text-decoration: underline;
        }

        /* Login Form */
        .login-form {
            display: none;
        }

        /* Error Message */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }

        /* Success Message */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }


    

        /* ===========================================
           ANIMATIONS
        =========================================== */
        @keyframes fadeLogin {
            from { opacity: 0; transform: translateY(15px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===========================================
           RESPONSIVE
        =========================================== */
        @media (max-width: 480px) {
            .login-card {
                width: 92%;
                padding: 24px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'nav.php'; ?>

    <div class="login-wrapper">
        <div class="login-card">
            <h2>Login</h2>
            
            
            <!-- Show SweetAlert for URL parameters -->
            <?php if (isset($_GET['error']) || isset($_GET['verified'])): ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php 
                    $error_messages = [
                        'empty' => 'Please fill in all fields',
                        'notfound' => 'Email not found',
                        'wrongpass' => 'Invalid password',
                        'notverified' => 'Email not verified. Please check your email for verification code.'
                    ];
                    
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $message = $error_messages[$error] ?? 'An error occurred';
                        echo "Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: '$message'
                        });";
                    } 
                    
                    if (isset($_GET['verified'])) {
                        echo "Swal.fire({
                            icon: 'success',
                            title: 'Email Verified!',
                            text: 'Your email has been verified. Please login.'
                        });";
                    }
                    ?>
                });
                </script>
            <?php endif; ?>
            
            <div class="user-type-buttons">
                <button class="user-btn admin-btn" onclick="showLoginForm('admin')">
                    <i class="fas fa-user-shield"></i>
                    Admin Login
                </button>
                
                <button class="user-btn staff-btn" onclick="showLoginForm('staff')">
                    <i class="fas fa-user-tie"></i>
                    Staff Login
                </button>
                
                <button class="user-btn student-btn" onclick="showLoginForm('student')">
                    <i class="fas fa-user-graduate"></i>
                    Student Login
                </button>
            </div>
            
            <!-- Admin Login Form -->
            <div id="admin-form" class="login-form">
                <?php if (isset($login_error)): ?>
                    <div class="error-message">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Admin KLD Email Address" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="password-container">
                        <input type="password" name="password" placeholder="Password" required 
                               value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                        <button class="toggle-password" type="button" onclick="togglePassword(this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <input type="hidden" name="admin_login" value="1">
                    <button type="submit" class="login-btn">Login as Admin</button>
                </form>
                
                <div class="login-links">
                    <a href="forgotpass.php">Forgot Password?</a>
                </div>
            </div>
            
            <!-- Staff Login Form -->
            <div id="staff-form" class="login-form">
                <?php if (isset($staff_error)): ?>
                    <div class="error-message">
                        <?php echo $staff_error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Staff KLD Email Address" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="password-container">
                        <input type="password" name="password" placeholder="Password" required 
                               value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                        <button class="toggle-password" type="button" onclick="togglePassword(this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <input type="hidden" name="staff_login" value="1">
                    <button type="submit" class="login-btn">Login as Staff</button>
                </form>
     
                
                <div class="login-links">
                    <a href="forgotpass.php">Forgot Password?</a>
                </div>
            </div>
            
            <!-- Student Login Form -->
            <div id="student-form" class="login-form">
                <?php if (isset($student_error)): ?>
                    <div class="error-message">
                        <?php echo $student_error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Student KLD Email Address" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="password-container">
                        <input type="password" name="password" placeholder="Password" required>
                        <button class="toggle-password" type="button" onclick="togglePassword(this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <input type="hidden" name="student_login" value="1">
                    <button type="submit" class="login-btn">Login as Student</button>
                </form>
                
                <div class="login-links">
                    <a href="forgotpass.php">Forgot Password?</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

    <script>
        function showLoginForm(userType) {
            // Hide all forms first
            document.querySelectorAll('.login-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show the selected form
            document.getElementById(`${userType}-form`).style.display = 'block';
            
            // Update the header text
            const headerText = document.querySelector('.login-card p');
            if (userType === 'admin') {
                headerText.textContent = 'Access admin dashboard and management tools';
            } else if (userType === 'staff') {
                headerText.textContent = 'Report and manage student violations';
            } else {
                headerText.textContent = 'View your violations and credentials';
            }
        }

        function togglePassword(button) {
            const passwordInput = button.closest('.password-container').querySelector('input[type="password"]');
            const icon = button.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Show student form by default if there's an error
        <?php if (isset($login_error) || isset($staff_error) || isset($student_error) || isset($_GET['error']) || isset($_GET['verified'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if (isset($staff_error)): ?>
                    showLoginForm('staff');
                <?php elseif (isset($login_error)): ?>
                    showLoginForm('admin');
                <?php else: ?>
                    showLoginForm('student');
                <?php endif; ?>
            });
        <?php endif; ?>

        // Remove query params after popup appears
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes("error=") || window.location.search.includes("verified=")) {
                window.history.replaceState({}, document.title, "login.php");
            
            }
        });
    </script>
</body>
</html>