<?php
// Start session for login detection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session variables
$isLogged = false;
$isAdmin = false;
$isStaff = false;

if (isset($_SESSION['userid']) && !empty($_SESSION['userid'])) {
    $isLogged = true;
} 
elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $isLogged = true;
    $isAdmin = true;
}
elseif (isset($_SESSION['staff_logged_in']) && !empty($_SESSION['staff_logged_in'])) {
    $isLogged = true;
    $isStaff = true;
}
elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $isLogged = true;
} 
elseif (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $isLogged = true;
}

// Check if we're viewing a student profile (for admin/staff)
$viewingStudentProfile = false;
$currentStudentId = null;
$currentStudentNumber = null;

if (($isAdmin || $isStaff) && (isset($_GET['student_id']) || isset($_GET['id']))) {
    $viewingStudentProfile = true;
    
    // Prefer user_id from student_data if available (from admin_db.php)
    if (isset($student_data) && isset($student_data['user_id'])) {
        $currentStudentId = $student_data['user_id'];
        $currentStudentNumber = $student_data['student_number'];
    } 
    // Or from user_id variable (from profile.php)
    elseif (isset($user_id) && is_numeric($user_id)) {
        $currentStudentId = $user_id;
        // We might not have student number here easily without query, but profile.php usually has $profile data
        $currentStudentNumber = isset($profile['student_number']) ? $profile['student_number'] : $user_id;
    }
    else {
        // Fallback to GET parameters
        $currentStudentId = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['student_id']) ? $_GET['student_id'] : null);
        $currentStudentNumber = $currentStudentId;
    }
}

// Get current page to determine context
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
/* ===========================================
   NAVBAR STYLES
=========================================== */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    height: 64px;
       background: #00c476; /* Lime background */
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-left {
    display: flex;
    align-items: center;
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: white; /* White font for logo */
}

.nav-links {
    display: flex;
    list-style: none;
    align-items: center;
    gap: 30px;
    margin: 0;
    padding: 0;
}

.nav-links li {
    margin: 0;
}

.nav-links a {
    text-decoration: none;
    color: white; /* White font for all links */
    font-weight: 500;
    font-size: 16px;
    transition: all 0.3s ease;
    padding: 8px 12px;
    border-radius: 6px;
}

.nav-links a:hover {
    color: white;
    background: rgba(255, 255, 255, 0.2); /* Light white overlay on hover */
}

.nav-links a.admin-btn {
    background: rgba(255, 255, 255, 0.2); /* Semi-transparent white */
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.nav-links a.admin-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}

/* Profile Dropdown Styles */
.profile-dropdown {
    position: relative;
    display: inline-block;
}

.profile-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    transition: background 0.3s ease;
    color: white; /* White font for profile button */
}

.profile-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.profile-icon {
    font-size: 18px;
}

.profile-text {
    font-weight: 500;
    color: white; /* White font for profile text */
}

.profile-arrow {
    font-size: 12px;
    color: white; /* White font for arrow */
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: #ffffff;
    border-radius: 8px;
    padding: 8px 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 220px;
    display: none;
    z-index: 1001;
    border: 1px solid rgba(0,0,0,0.1);
}

.dropdown-menu a {
    display: block;
    padding: 10px 16px;
    font-size: 14px;
    color: #333;
    text-decoration: none;
    transition: background 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dropdown-menu a:hover {
    background: rgba(0, 196, 118, 0.1);
    color: #00c476;
}

.dropdown-divider {
    height: 1px;
    background: #eee;
    margin: 6px 0;
}

/* Hamburger Menu (Mobile) */
.hamburger {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
    color: white; /* White color for hamburger icon */
}

/* Responsive Design */
@media (max-width: 768px) {
    .navbar {
        padding: 0 15px;
    }
    
    .hamburger {
        display: block;
    }
    
    .nav-links {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #00c476; /* Lime background for mobile menu */
        flex-direction: column;
        padding: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        gap: 15px;
    }
    
    .nav-links.active {
        display: flex;
    }
    
    .nav-links li {
        width: 100%;
        text-align: center;
    }
    
    .nav-links a {
        display: block;
        padding: 12px;
        width: 100%;
        color: white; /* White font for mobile links */
    }
    
    .nav-links a:hover {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .profile-dropdown {
        width: 100%;
    }
    
    .profile-btn {
        justify-content: center;
        width: 100%;
        color: white;
    }
    
    .dropdown-menu {
        position: static;
        width: 100%;
        box-shadow: none;
        border: none;
        background: rgba(0, 196, 118, 0.05);
    }
}
</style>

<nav class="navbar">
  <div class="nav-left">
    <div class="logo">Pixel Wizard Co.</div>
  </div>

  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="features.php">Features</a></li>
    <li><a href="violations.php">Violations</a></li>
    <li><a href="demo.php">Demo</a></li>
    <li><a href="How-it-works.php">How it Works</a></li>

    <!-- LOGIN / PROFILE DROPDOWN -->
    <li>
    <?php if (!$isLogged): ?>
        <a href="login.php" class="admin-btn">Login</a>
    <?php else: ?>
        <div class="profile-dropdown">
          <button class="profile-btn">
            <span class="profile-icon">👤</span>
            <span class="profile-text">
                <?php 
                    if ($isAdmin) {
                        echo "Admin";
                    } elseif ($isStaff) {
                        echo "Staff";
                    } else {
                        echo "My Account";
                    }
                ?>
            </span>
            <span class="profile-arrow">▼</span>
          </button>

          <div class="dropdown-menu">
            <?php if ($isAdmin): ?>
                <!-- ADMIN DROPDOWN -->
                <?php if ($viewingStudentProfile && $currentPage !== 'admin_db.php'): ?>
                    <a href="admin_db.php?student_id=<?php echo $currentStudentNumber; ?>">
                        📊 Admin Dashboard
                    </a>
                    <a href="profile.php?id=<?php echo $currentStudentId; ?>">
                        👨‍🎓 Student Profile
                    </a>
                <?php else: ?>
                    <a href="admin_db.php">📊 Admin Dashboard</a>
                    <?php if ($viewingStudentProfile): ?>
                        <a href="profile.php?id=<?php echo $currentStudentId; ?>">
                            👨‍🎓 Student Profile
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- CREATE ACCOUNT OPTION - Only for Admin -->
                <div class="dropdown-divider"></div>
                <a href="choose_role.php">➕ Create Account</a>
                
            <?php elseif ($isStaff): ?>
                <!-- STAFF DROPDOWN -->
                <?php if ($viewingStudentProfile && $currentPage !== 'staff_db.php'): ?>
                    <a href="staff_db.php?student_id=<?php echo $currentStudentNumber; ?>">
                        📋 Staff Portal
                    </a>
                    <a href="profile.php?id=<?php echo $currentStudentId; ?>">
                        👨‍🎓 Student Profile
                    </a>
                <?php else: ?>
                    <a href="staff_db.php">📋 Staff Portal</a>
                    <?php if ($viewingStudentProfile): ?>
                        <a href="profile.php?id=<?php echo $currentStudentId; ?>">
                            👨‍🎓 Student Profile
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- STUDENT DROPDOWN -->
                <a href="profile.php">👤 My Profile</a>
            <?php endif; ?>
            
            <!-- Logout for all users -->
            <div class="dropdown-divider"></div>
            <a href="logout.php">🚪 Logout</a>
          </div>
        </div>
    <?php endif; ?>
    </li>
  </ul>

  <button class="hamburger" aria-label="menu">☰</button>
</nav>

<script>
// Profile dropdown functionality
const profileBtn = document.querySelector(".profile-btn");
const dropdown = document.querySelector(".dropdown-menu");

if (profileBtn && dropdown) {
    profileBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function(e) {
        if (!document.querySelector(".profile-dropdown")?.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });
}

// Mobile hamburger menu functionality
const hamburger = document.querySelector(".hamburger");
const navLinks = document.querySelector(".nav-links");

if (hamburger && navLinks) {
    hamburger.addEventListener("click", () => {
        navLinks.classList.toggle("active");
    });

    // Close mobile menu when clicking on a link
    document.querySelectorAll(".nav-links a").forEach(link => {
        link.addEventListener("click", () => {
            navLinks.classList.remove("active");
        });
    });
}
</script>
