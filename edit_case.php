<?php
session_start();
require_once 'database.php';
require_once 'admin_function.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$case_id = $_GET['case_id'] ?? null;

if (!$case_id) {
    header("Location: admin_db.php");
    exit();
}

// Fetch complete case details with all fields
$stmt = $conn->prepare("
    SELECT 
        v.*,
        v.description as violation_description,
        v.resolution_notes,
        sp.firstname,
        sp.surname, 
        sp.student_number,
        sp.course,
        sp.section,
        sp.year_level,
        sp.sex,
        u.email,
        s.id as sanction_id,
        s.sanction_type,
        s.status as sanction_status,
        s.due_date,
        s.completion_date,
        s.completion_proof,
        s.counselor_notes,
        s.hours_completed,
        s.created_at as sanction_created_at,
        v.proof_filename,
        CASE 
            WHEN v.user_type = 'admin' THEN 'Administrator'
            WHEN v.user_type = 'staff' THEN staff.fullname
            ELSE 'Unknown'
        END as recorded_by_name
    FROM violations v
    JOIN student_profiles sp ON v.user_id = sp.user_id
    JOIN users u ON sp.user_id = u.id
    LEFT JOIN sanctions s ON v.id = s.violation_id
    LEFT JOIN staff ON v.recorded_by = staff.id AND v.user_type = 'staff'
    WHERE v.id = ?
");

if (!$stmt) {
    $_SESSION['error_message'] = "Database error: " . $conn->error;
    header("Location: admin_db.php");
    exit();
}

$stmt->bind_param("i", $case_id);
$stmt->execute();
$result = $stmt->get_result();
$case = $result->fetch_assoc();

if (!$case) {
    $_SESSION['error_message'] = "Case not found.";
    header("Location: admin_db.php");
    exit();
}

// Get sanction information
$has_sanction = !empty($case['sanction_id']);
$sanction_status = $case['sanction_status'] ?? 'none';
$sanction_id = $case['sanction_id'] ?? 0;
$current_sanction_type = $case['sanction_type'] ?? '';
$current_due_date = $case['due_date'] ?? '';
$current_completion_date = $case['completion_date'] ?? '';
$current_counselor_notes = $case['counselor_notes'] ?? '';
$current_hours_completed = $case['hours_completed'] ?? '';

// Prepare data for form
$student_name = isset($case['surname']) && isset($case['firstname']) 
    ? htmlspecialchars($case['surname'] . ', ' . $case['firstname']) 
    : 'N/A';

$student_number = isset($case['student_number']) 
    ? htmlspecialchars($case['student_number']) 
    : 'N/A';
    
$violation_type = isset($case['violation_type']) 
    ? htmlspecialchars($case['violation_type']) 
    : '';
    
$violation_category = isset($case['violation_category']) 
    ? $case['violation_category'] 
    : 'minor';
    
$status = isset($case['status']) 
    ? $case['status'] 
    : 'pending';
    
$description = isset($case['description']) 
    ? htmlspecialchars($case['description']) 
    : '';
    
$course_section = isset($case['course']) && isset($case['section'])
    ? htmlspecialchars($case['course'] . ' - ' . $case['section'])
    : 'N/A';

$created_at = '';
if (isset($case['created_at']) && $case['created_at'] != '0000-00-00 00:00:00' && $case['created_at'] != NULL) {
    $created_at = date("Y-m-d\TH:i", strtotime($case['created_at']));
} else {
    $created_at = date("Y-m-d\TH:i");
}

$updated_at = '';
if (isset($case['updated_at']) && $case['updated_at'] != '0000-00-00 00:00:00' && $case['updated_at'] != NULL) {
    $updated_at = date("F j, Y g:i A", strtotime($case['updated_at']));
} else {
    $updated_at = 'Never updated';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Case - Student Violation System</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="admin_responsive.css">
    <style>
        .edit-page-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .page-header h1 {
            margin: 0;
            color: #2c3e50;
        }
        .back-button {
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #5a6268;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .form-section h4 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
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
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-control:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .form-actions {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .btn {
            padding: 12px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .timestamp-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="edit-page-container">
        <div class="page-header">
            <h1>Edit Case Details - V-<?= str_pad($case_id, 3, '0', STR_PAD_LEFT) ?></h1>
            <a href="admin_db.php#cases" class="back-button">← Back to Dashboard</a>
        </div>

        <form id="editCaseForm" method="POST" action="update_case.php">
            <input type="hidden" name="case_id" value="<?= $case_id ?>">
            <input type="hidden" name="has_sanction" value="<?= $has_sanction ? '1' : '0' ?>">
            <?php if ($sanction_id): ?>
                <input type="hidden" name="sanction_id" value="<?= $sanction_id ?>">
            <?php endif; ?>
            
            <div class="form-section">
                <h4>Student Information</h4>
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" class="form-control" value="<?= $student_name ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Student Number:</label>
                    <input type="text" class="form-control" value="<?= $student_number ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Course & Section:</label>
                    <input type="text" class="form-control" value="<?= $course_section ?>" disabled>
                </div>
            </div>

            <div class="form-section">
                <h4>Violation Details</h4>
                
                <!-- TIMESTAMP INFORMATION -->
                <div class="timestamp-info">
                    <strong>📅 Case Timestamps:</strong><br>
                    <small>Created: <?= date("F j, Y g:i A", strtotime($case['created_at'])) ?></small><br>
                    <small>Last Updated: <?= $updated_at ?></small>
                </div>
                
                <div class="form-group">
                    <label>Violation Type: *</label>
                    <select name="violation_type" class="form-control" required onchange="updateCategory(this.value)">
                        <optgroup label="Minor Violations">
                            <option value="No ID" <?= $violation_type == 'No ID' ? 'selected' : '' ?>>No ID</option>
                            <option value="Improper Attire" <?= $violation_type == 'Improper Attire' ? 'selected' : '' ?>>Improper Attire</option>
                            <option value="Improper Uniform" <?= $violation_type == 'Improper Uniform' ? 'selected' : '' ?>>Improper Uniform</option>
                            <option value="Late" <?= $violation_type == 'Late' ? 'selected' : '' ?>>Late</option>
                            <option value="Mobile Phone Use" <?= $violation_type == 'Mobile Phone Use' ? 'selected' : '' ?>>Mobile Phone Use</option>
                            <option value="Disruptive Behavior" <?= $violation_type == 'Disruptive Behavior' ? 'selected' : '' ?>>Disruptive Behavior</option>
                            <option value="Littering" <?= $violation_type == 'Littering' ? 'selected' : '' ?>>Littering</option>
                            <option value="Public Display of Affection" <?= $violation_type == 'Public Display of Affection' ? 'selected' : '' ?>>Public Display of Affection</option>
                            <option value="Vaping" <?= $violation_type == 'Vaping' ? 'selected' : '' ?>>Vaping</option>
                            <option value="Parking Violation" <?= $violation_type == 'Parking Violation' ? 'selected' : '' ?>>Parking Violation</option>
                            <option value="Disrespect" <?= $violation_type == 'Disrespect' ? 'selected' : '' ?>>Disrespect</option>
                            <option value="Classroom Entry" <?= $violation_type == 'Classroom Entry' ? 'selected' : '' ?>>Classroom Entry</option>
                            <option value="Gambling Materials" <?= $violation_type == 'Gambling Materials' ? 'selected' : '' ?>>Gambling Materials</option>
                            <option value="Other Minor" <?= $violation_type == 'Other Minor' ? 'selected' : '' ?>>Other Minor</option>
                        </optgroup>
                        <optgroup label="Major Violations">
                            <option value="Academic Cheating" <?= $violation_type == 'Academic Cheating' ? 'selected' : '' ?>>Academic Cheating</option>
                            <option value="Plagiarism" <?= $violation_type == 'Plagiarism' ? 'selected' : '' ?>>Plagiarism</option>
                            <option value="Falsification" <?= $violation_type == 'Falsification' ? 'selected' : '' ?>>Falsification</option>
                            <option value="Physical Assault" <?= $violation_type == 'Physical Assault' ? 'selected' : '' ?>>Physical Assault</option>
                            <option value="Threats" <?= $violation_type == 'Threats' ? 'selected' : '' ?>>Threats</option>
                            <option value="Bullying" <?= $violation_type == 'Bullying' ? 'selected' : '' ?>>Bullying</option>
                            <option value="Weapon Possession" <?= $violation_type == 'Weapon Possession' ? 'selected' : '' ?>>Weapon Possession</option>
                            <option value="Drug Violation" <?= $violation_type == 'Drug Violation' ? 'selected' : '' ?>>Drug Violation</option>
                            <option value="Alcohol Violation" <?= $violation_type == 'Alcohol Violation' ? 'selected' : '' ?>>Alcohol Violation</option>
                            <option value="Sexual Harassment" <?= $violation_type == 'Sexual Harassment' ? 'selected' : '' ?>>Sexual Harassment</option>
                            <option value="Vandalism" <?= $violation_type == 'Vandalism' ? 'selected' : '' ?>>Vandalism</option>
                            <option value="Hazing" <?= $violation_type == 'Hazing' ? 'selected' : '' ?>>Hazing</option>
                            <option value="Unauthorized Organization" <?= $violation_type == 'Unauthorized Organization' ? 'selected' : '' ?>>Unauthorized Organization</option>
                            <option value="Unauthorized Solicitation" <?= $violation_type == 'Unauthorized Solicitation' ? 'selected' : '' ?>>Unauthorized Solicitation</option>
                            <option value="System Tampering" <?= $violation_type == 'System Tampering' ? 'selected' : '' ?>>System Tampering</option>
                            <option value="Gambling" <?= $violation_type == 'Gambling' ? 'selected' : '' ?>>Gambling</option>
                            <option value="Lewd Conduct" <?= $violation_type == 'Lewd Conduct' ? 'selected' : '' ?>>Lewd Conduct</option>
                            <option value="Disruption of Classes" <?= $violation_type == 'Disruption of Classes' ? 'selected' : '' ?>>Disruption of Classes</option>
                            <option value="Smoking" <?= $violation_type == 'Smoking' ? 'selected' : '' ?>>Smoking</option>
                            <option value="Publishing False Information" <?= $violation_type == 'Publishing False Information' ? 'selected' : '' ?>>Publishing False Information</option>
                            <option value="Forging Security Stamps" <?= $violation_type == 'Forging Security Stamps' ? 'selected' : '' ?>>Forging Security Stamps</option>
                            <option value="ID or Document Misuse" <?= $violation_type == 'ID or Document Misuse' ? 'selected' : '' ?>>ID or Document Misuse</option>
                            <option value="Accumulation of 4 Minor Offenses" <?= $violation_type == 'Accumulation of 4 Minor Offenses' ? 'selected' : '' ?>>Accumulation of 4 Minor Offenses</option>
                            <option value="Endangering Safety" <?= $violation_type == 'Endangering Safety' ? 'selected' : '' ?>>Endangering Safety</option>
                            <option value="Forcible Entry" <?= $violation_type == 'Forcible Entry' ? 'selected' : '' ?>>Forcible Entry</option>
                            <option value="Unauthorized Use of Rooms" <?= $violation_type == 'Unauthorized Use of Rooms' ? 'selected' : '' ?>>Unauthorized Use of Rooms</option>
                            <option value="Misuse of IT Systems" <?= $violation_type == 'Misuse of IT Systems' ? 'selected' : '' ?>>Misuse of IT Systems</option>
                            <option value="Bribery" <?= $violation_type == 'Bribery' ? 'selected' : '' ?>>Bribery</option>
                            <option value="Stealing" <?= $violation_type == 'Stealing' ? 'selected' : '' ?>>Stealing</option>
                            <option value="Tampering Emergency Devices" <?= $violation_type == 'Tampering Emergency Devices' ? 'selected' : '' ?>>Tampering Emergency Devices</option>
                            <option value="Obscene Materials" <?= $violation_type == 'Obscene Materials' ? 'selected' : '' ?>>Obscene Materials</option>
                            <option value="Violent Protest or Coercion" <?= $violation_type == 'Violent Protest or Coercion' ? 'selected' : '' ?>>Violent Protest or Coercion</option>
                            <option value="Unauthorized Posting" <?= $violation_type == 'Unauthorized Posting' ? 'selected' : '' ?>>Unauthorized Posting</option>
                            <option value="Aiding Violations" <?= $violation_type == 'Aiding Violations' ? 'selected' : '' ?>>Aiding Violations</option>
                            <option value="Other Major" <?= $violation_type == 'Other Major' ? 'selected' : '' ?>>Other Major</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Category: *</label>
                    <select name="violation_category" class="form-control" required id="categorySelect">
                        <option value="minor" <?= $violation_category == 'minor' ? 'selected' : '' ?>>Minor</option>
                        <option value="major" <?= $violation_category == 'major' ? 'selected' : '' ?>>Major</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status: *</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="resolved" <?= $status == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Violation Description:</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Enter detailed description of the violation..."><?= $description ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Resolution Notes:</label>
                    <textarea name="resolution_notes" class="form-control" rows="3" placeholder="Enter resolution notes or actions taken..."><?= htmlspecialchars($case['resolution_notes'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Date Recorded:</label>
                    <input type="datetime-local" name="created_at" class="form-control" value="<?= $created_at ?>">
                </div>
            </div>

            <!-- Sanction Information Section -->
            <div class="form-section">
                <h4>Sanction Information</h4>
                <div class="form-group">
                    <label>Sanction Type</label>
                    <select class="form-control" name="sanction_type">
                        <option value="">No Sanction</option>
                        <optgroup label="Minor Offenses">
                            <option value="verbal_reprimand" <?= $current_sanction_type == 'verbal_reprimand' ? 'selected' : '' ?>>1st Offense: Verbal Reprimand</option>
                            <option value="written_warning_1" <?= $current_sanction_type == 'written_warning_1' ? 'selected' : '' ?>>1st Offense: Written Warning</option>
                            <option value="written_warning_2" <?= $current_sanction_type == 'written_warning_2' ? 'selected' : '' ?>>2nd Offense: Written Warning + 3hrs Community Service</option>
                            <option value="written_warning_3" <?= $current_sanction_type == 'written_warning_3' ? 'selected' : '' ?>>3rd Offense: Written Warning + 6hrs Community Service + Counseling</option>
                        </optgroup>
                        <optgroup label="Major Offenses">
                            <option value="suspension_6_days" <?= $current_sanction_type == 'suspension_6_days' ? 'selected' : '' ?>>A: Suspension for 6 Days</option>
                            <option value="suspension_10_20_days" <?= $current_sanction_type == 'suspension_10_20_days' ? 'selected' : '' ?>>B: Suspension for 10-20 Days</option>
                            <option value="non_readmission" <?= $current_sanction_type == 'non_readmission' ? 'selected' : '' ?>>C: Non-readmission to the College</option>
                            <option value="dismissal" <?= $current_sanction_type == 'dismissal' ? 'selected' : '' ?>>D: Dismissal from the College</option>
                            <option value="expulsion" <?= $current_sanction_type == 'expulsion' ? 'selected' : '' ?>>E: Expulsion</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Sanction Status</label>
                    <select class="form-control" name="sanction_status">
                        <option value="pending" <?= $sanction_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $sanction_status == 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" class="form-control" name="due_date" value="<?= $current_due_date ?>">
                </div>
                
                <div class="form-group">
                    <label>Completed On</label>
                    <input type="date" class="form-control" name="completion_date" value="<?= $current_completion_date ?>">
                </div>
                
                <div class="form-group">
                    <label>Hours Completed</label>
                    <input type="number" step="0.5" class="form-control" name="hours_completed" value="<?= $current_hours_completed ?>" placeholder="Enter hours completed (e.g., 3.5)">
                    <small style="color: #6c757d;">For community service sanctions</small>
                </div>
                
                <div class="form-group">
                    <label>Counselor Notes</label>
                    <textarea name="counselor_notes" class="form-control" rows="3" placeholder="Enter counselor notes or observations..."><?= htmlspecialchars($current_counselor_notes) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 Save All Changes</button>
                <a href="admin_db.php#cases" class="btn btn-danger">✖ Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Function to automatically update category based on violation type
        function updateCategory(violationType) {
            const minorViolations = [
                'No ID', 'Improper Attire', 'Improper Uniform', 'Late', 'Mobile Phone Use',
                'Disruptive Behavior', 'Littering', 'Public Display of Affection', 'Vaping',
                'Parking Violation', 'Disrespect', 'Classroom Entry', 'Gambling Materials', 'Other Minor'
            ];
            
            const categorySelect = document.getElementById('categorySelect');
            if (minorViolations.includes(violationType)) {
                categorySelect.value = 'minor';
            } else {
                categorySelect.value = 'major';
            }
        }

        // Form submission handler
        document.getElementById('editCaseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
                const violationStatus = document.querySelector('select[name="status"]').value;
    const sanctionStatus = document.querySelector('select[name="sanction_status"]').value;
    
    // Check if statuses are properly paired
    // Allowed: pending + pending OR resolved + completed
    const isValid = (violationStatus === 'pending' && sanctionStatus === 'pending') ||
                    (violationStatus === 'resolved' && sanctionStatus === 'completed');
    
    if (!isValid) {
        alert('❌ Statuses must be paired correctly:\n• Violation: PENDING + Sanction: PENDING\n• Violation: RESOLVED + Sanction: COMPLETED');
        return false;
    }
            
            const formData = new FormData(this);
            
            fetch('update_case.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Case updated successfully!');
                    window.location.href = 'admin_db.php#cases';
                } else {
                    alert('❌ Error updating case: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error updating case: ' + error);
            });
        });
    </script>
</body>
</html>
