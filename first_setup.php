<?php
// first_setup.php
session_start();
require_once 'database.php'; // provides $conn

// Only accessible to logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Check if user already completed profile setup
$checkSetup = $conn->prepare("SELECT completed_setup, profile_completed FROM users WHERE id = ?");
$checkSetup->bind_param("i", $user_id);
$checkSetup->execute();
$setupResult = $checkSetup->get_result();

if ($setupResult->num_rows === 1) {
    $user = $setupResult->fetch_assoc();
    // Check profile_completed instead of completed_setup
    if ((int)$user['profile_completed'] === 1) {
        header('Location: profile.php');
        exit;
    }
}

// If the profile already exists and looks complete, redirect to profile
$check = $conn->prepare("SELECT id, firstname, surname, course, section, qr_code FROM student_profiles WHERE user_id = ? LIMIT 1");
$check->bind_param("i", $user_id);
$check->execute();
$checkRes = $check->get_result();

if ($checkRes && $checkRes->num_rows > 0) {
    $row = $checkRes->fetch_assoc();
    if (!empty($row['firstname']) && !empty($row['surname']) && !empty($row['course']) && !empty($row['section']) && !empty($row['qr_code'])) {
        // Update user's profile_completed status
        $updateSetup = $conn->prepare("UPDATE users SET profile_completed = 1 WHERE id = ?");
        $updateSetup->bind_param("i", $user_id);
        $updateSetup->execute();
        
        header('Location: profile.php');
        exit;
    }
}

// show form (POST handled by save_setup.php)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>First Time Setup - Complete Profile</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        .student-number-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .student-number-input input {
            text-align: center;
        }
        .student-number-separator {
            font-weight: bold;
            color: #666;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        .year-level-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .year-level-option {
            flex: 1;
            text-align: center;
        }
        .year-level-option input[type="radio"] {
            display: none;
        }
        .year-level-option label {
            display: block;
            padding: 12px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .year-level-option input[type="radio"]:checked + label {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .year-level-option label:hover {
            border-color: #007bff;
        }
        .input-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .validation-message {
            font-size: 12px;
            margin-top: 5px;
            min-height: 15px;
        }
        .valid {
            color: #28a745;
        }
        .invalid {
            color: #dc3545;
        }
        .year-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="setup-wrapper">
  <div class="setup-card">
    <h2>Complete Your Profile</h2>

    <form action="save_setup.php" method="POST" autocomplete="off">
      <div class="form-group">
        <input type="text" name="surname" placeholder="Surname" required>
      </div>
      
      <div class="form-group">
        <input type="text" name="firstname" placeholder="First Name" required>
      </div>
      
      <div class="form-group">
        <input type="text" name="middle_initial" maxlength="1" placeholder="Middle Initial" required>
      </div>

      <!-- Year Level Selection -->
      <div class="form-group">
        <label>Year Level</label>
        <div class="year-level-container">
            <div class="year-level-option">
                <input type="radio" id="year1" name="year_level" value="1" required>
                <label for="year1">1st Year</label>
            </div>
            <div class="year-level-option">
                <input type="radio" id="year2" name="year_level" value="2">
                <label for="year2">2nd Year</label>
            </div>
            <div class="year-level-option">
                <input type="radio" id="year3" name="year_level" value="3">
                <label for="year3">3rd Year</label>
            </div>
            <div class="year-level-option">
                <input type="radio" id="year4" name="year_level" value="4">
                <label for="year4">4th Year</label>
            </div>
        </div>
      </div>

      <div class="form-group">
        <select name="sex" required>
          <option value="">Select Sex</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>

      <div class="form-group">
        <select name="course" id="courseSelect" required>
          <option value="">Select Course</option>
          <option value="BSIS">BS Information Systems</option>
          <option value="BSN">BS Nursing</option>
          <option value="BSE">BS Engineering</option>
          <option value="BSP">BS Psychology</option>
          <option value="BSCS" id="bscsOption">BS Computer Science</option>
        </select>
      </div>

      <div class="form-group">
        <select name="section" id="sectionSelect" required disabled>
          <option value="">Select Section</option>
          <!-- Sections will be populated dynamically based on year level -->
        </select>
      </div>

      <!-- Manual Student Number Input -->
      <div class="form-group">
        <label for="student_number">Student Number</label>
        <div class="student-number-input">
          <input type="number" id="yearPart" min="2022" max="2025" placeholder="Year" style="width: 80px;">
          <span class="student-number-separator">-</span>
          <input type="number" id="coursePart" min="1" max="5" placeholder="Course" style="width: 60px;">
          <span class="student-number-separator">-</span>
          <input type="number" id="numberPart" min="100" max="700" placeholder="Number" style="width: 80px;">
        </div>
        <div class="year-hint" id="yearHint">
          Select year level to see allowed admission year
        </div>
        <div class="validation-message" id="studentNumberValidation"></div>
        <input type="hidden" name="student_number" id="studentNumberInput" required>
      </div>

      <button class="save-btn" type="submit" id="submitBtn" disabled>Save & Continue</button>
    </form>
  </div>
</div>

<script>
// Course to code mapping
const courseCodes = {
    'BSIS': '4',
    'BSN': '1', 
    'BSE': '2',
    'BSP': '3',
    'BSCS': '5'
};

// Course code to name mapping (for validation)
const codeToCourse = {
    '1': 'BSN',
    '2': 'BSE',
    '3': 'BSP',
    '4': 'BSIS',
    '5': 'BSCS'
};

// Courses available for each year level
const availableCourses = {
    '1': ['BSIS', 'BSN', 'BSE', 'BSP', 'BSCS'],
    '2': ['BSIS', 'BSN', 'BSE', 'BSP'],
    '3': ['BSIS', 'BSN', 'BSE', 'BSP'],
    '4': ['BSIS', 'BSN', 'BSE', 'BSP']
};

// Section ranges for each year level
const sectionRanges = {
    '1': { start: 100, end: 110 }, // 1st year: 100-110
    '2': { start: 200, end: 210 }, // 2nd year: 200-210
    '3': { start: 300, end: 310 }, // 3rd year: 300-310
    '4': { start: 400, end: 410 }  // 4th year: 400-410
};

// Year level to admission year mapping
const yearToAdmission = {
    '1': 2025, // 1st year admitted in 2025
    '2': 2024, // 2nd year admitted in 2024
    '3': 2023, // 3rd year admitted in 2023
    '4': 2022  // 4th year admitted in 2022
};

// Populate sections based on year level
function populateSections(yearLevel) {
    const sectionSelect = document.getElementById('sectionSelect');
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    
    if (!yearLevel) {
        sectionSelect.disabled = true;
        return;
    }
    
    const range = sectionRanges[yearLevel];
    for (let i = range.start; i <= range.end; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        sectionSelect.appendChild(option);
    }
    
    sectionSelect.disabled = false;
}

// Update available courses based on selected year level
function updateAvailableCourses(yearLevel) {
    const courseSelect = document.getElementById('courseSelect');
    const currentCourse = courseSelect.value;
    
    // Reset all options
    Array.from(courseSelect.options).forEach(option => {
        if (option.value) {
            option.disabled = false;
            option.classList.remove('disabled');
        }
    });
    
    // Disable courses not available for selected year level
    Array.from(courseSelect.options).forEach(option => {
        if (option.value && !availableCourses[yearLevel].includes(option.value)) {
            option.disabled = true;
            option.classList.add('disabled');
            
            // If current selection is not available, clear it
            if (option.value === currentCourse) {
                courseSelect.value = '';
            }
        }
    });
}

// Update year hint based on selected year level
function updateYearHint(yearLevel) {
    const yearHint = document.getElementById('yearHint');
    if (yearLevel) {
        const admissionYear = yearToAdmission[yearLevel];
        yearHint.textContent = `${getYearLevelText(yearLevel)} students must use admission year: ${admissionYear}`;
        yearHint.style.color = '#28a745';
    } else {
        yearHint.textContent = 'Select year level to see allowed admission year';
        yearHint.style.color = '#666';
    }
}

// Get year level text
function getYearLevelText(yearLevel) {
    const yearTexts = {
        '1': '1st Year',
        '2': '2nd Year', 
        '3': '3rd Year',
        '4': '4th Year'
    };
    return yearTexts[yearLevel] || '';
}

// Validate student number parts
function validateStudentNumber() {
    const yearPart = document.getElementById('yearPart').value;
    const coursePart = document.getElementById('coursePart').value;
    const numberPart = document.getElementById('numberPart').value;
    const validationMsg = document.getElementById('studentNumberValidation');
    const hiddenInput = document.getElementById('studentNumberInput');
    const submitBtn = document.getElementById('submitBtn');
    const courseSelect = document.getElementById('courseSelect').value;
    const yearLevel = document.querySelector('input[name="year_level"]:checked');

    // Check if all parts are filled
    if (!yearPart || !coursePart || !numberPart) {
        validationMsg.textContent = 'Please complete all parts of student number';
        validationMsg.className = 'validation-message invalid';
        hiddenInput.value = '';
        submitBtn.disabled = true;
        return;
    }

    // Validate year range
    if (yearPart < 2022 || yearPart > 2025) {
        validationMsg.textContent = 'Year must be between 2022-2025';
        validationMsg.className = 'validation-message invalid';
        hiddenInput.value = '';
        submitBtn.disabled = true;
        return;
    }

    // Validate year level matches admission year
    if (yearLevel) {
        const expectedYear = yearToAdmission[yearLevel.value];
        if (parseInt(yearPart) !== expectedYear) {
            validationMsg.textContent = `${getYearLevelText(yearLevel.value)} students must use admission year ${expectedYear}`;
            validationMsg.className = 'validation-message invalid';
            hiddenInput.value = '';
            submitBtn.disabled = true;
            return;
        }
    }

    // Validate course code range
    if (coursePart < 1 || coursePart > 5) {
        validationMsg.textContent = 'Course code must be between 1-5';
        validationMsg.className = 'validation-message invalid';
        hiddenInput.value = '';
        submitBtn.disabled = true;
        return;
    }

    // Validate number range
    if (numberPart < 100 || numberPart > 700) {
        validationMsg.textContent = 'Number must be between 100-700';
        validationMsg.className = 'validation-message invalid';
        hiddenInput.value = '';
        submitBtn.disabled = true;
        return;
    }

    // Validate BSCS restriction (cannot be below 2025)
    if (coursePart === '5' && yearPart < 2025) {
        validationMsg.textContent = 'BSCS students must have year 2025 or later';
        validationMsg.className = 'validation-message invalid';
        hiddenInput.value = '';
        submitBtn.disabled = true;
        return;
    }

    // Validate course selection matches course code
    const expectedCourse = codeToCourse[coursePart];
    if (courseSelect && courseSelect !== expectedCourse) {
        validationMsg.textContent = `Course code ${coursePart} doesn't match selected course ${courseSelect}`;
        validationMsg.className = 'validation-message invalid';
        hiddenInput.value = '';
        submitBtn.disabled = true;
        return;
    }

    // All validations passed
    const studentNumber = `${yearPart}-${coursePart}-${numberPart}`;
    hiddenInput.value = studentNumber;
    validationMsg.textContent = '✓ Valid student number format';
    validationMsg.className = 'validation-message valid';
    
    // Enable submit button only if all required fields are filled
    const allFieldsFilled = document.querySelector('input[name="surname"]').value &&
                          document.querySelector('input[name="firstname"]').value &&
                          document.querySelector('input[name="middle_initial"]').value &&
                          document.querySelector('select[name="sex"]').value &&
                          courseSelect &&
                          document.querySelector('select[name="section"]').value &&
                          yearLevel;
    
    submitBtn.disabled = !allFieldsFilled;
}

// Add event listeners for year level changes
document.querySelectorAll('input[name="year_level"]').forEach(radio => {
    radio.addEventListener('change', function() {
        updateAvailableCourses(this.value);
        populateSections(this.value);
        updateYearHint(this.value);
        validateStudentNumber();
    });
});

// Add event listener for course changes
document.getElementById('courseSelect').addEventListener('change', validateStudentNumber);

// Add event listener for section changes
document.getElementById('sectionSelect').addEventListener('change', validateStudentNumber);

// Add event listeners for student number input parts
document.getElementById('yearPart').addEventListener('input', validateStudentNumber);
document.getElementById('coursePart').addEventListener('input', validateStudentNumber);
document.getElementById('numberPart').addEventListener('input', validateStudentNumber);

// Add event listeners for other required fields
document.querySelector('input[name="surname"]').addEventListener('input', validateStudentNumber);
document.querySelector('input[name="firstname"]').addEventListener('input', validateStudentNumber);
document.querySelector('input[name="middle_initial"]').addEventListener('input', validateStudentNumber);
document.querySelector('select[name="sex"]').addEventListener('change', validateStudentNumber);

// Initialize form state
document.addEventListener('DOMContentLoaded', function() {
    // Disable submit button initially
    document.getElementById('submitBtn').disabled = true;
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>