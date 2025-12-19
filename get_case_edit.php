<?php
session_start();
require_once 'database.php';
require_once 'admin_function.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Unauthorized access');
}

$case_id = $_GET['case_id'] ?? null;

if ($case_id) {
    $case = getCaseDetails($case_id, $conn);
    
    if ($case) {
        // Get sanction information to enforce requirements
        $has_sanction = violationHasSanction($case_id, $conn);
        $sanction_status = 'none';
        $sanction_id = 0;
        $current_sanction_type = '';
        $current_due_date = '';
        $current_completion_date = '';

        if ($has_sanction) {
            // Check if sanctions table exists and has the required columns
            $check_sanctions_table = $conn->query("SHOW TABLES LIKE 'sanctions'");
            if ($check_sanctions_table->num_rows > 0) {
                // Check if columns exist before selecting
                $check_cols = $conn->query("SHOW COLUMNS FROM sanctions LIKE 'sanction_type'");
                if ($check_cols->num_rows > 0) {
                    $sanction_stmt = $conn->prepare("SELECT id, status, sanction_type, due_date, completion_date FROM sanctions WHERE violation_id = ?");
                    $sanction_stmt->bind_param("i", $case_id);
                    $sanction_stmt->execute();
                    $sanction_result = $sanction_stmt->get_result();
                    $sanction_data = $sanction_result->fetch_assoc();
                    
                    $sanction_status = $sanction_data ? $sanction_data['status'] : 'none';
                    $sanction_id = $sanction_data['id'] ?? 0;
                    $current_sanction_type = $sanction_data['sanction_type'] ?? '';
                    $current_due_date = $sanction_data['due_date'] ?? '';
                    $current_completion_date = $sanction_data['completion_date'] ?? '';
                } else {
                    // Fallback for old schema
                    $sanction_stmt = $conn->prepare("SELECT id, status FROM sanctions WHERE violation_id = ?");
                    $sanction_stmt->bind_param("i", $case_id);
                    $sanction_stmt->execute();
                    $sanction_result = $sanction_stmt->get_result();
                    $sanction_data = $sanction_result->fetch_assoc();
                    $sanction_status = $sanction_data ? $sanction_data['status'] : 'none';
                    $sanction_id = $sanction_data['id'] ?? 0;
                }
            }
        }

        // Determine if editing should be blocked
        $block_editing = false; // Disable blocking to allow adding sanction
        $require_sanction_update = ($has_sanction && $sanction_status == 'pending' && $case['status'] == 'pending');

        // Use the correct field names from your query
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

        // FIXED: Proper date handling with current time as fallback
        $created_at = '';
        if (isset($case['created_at']) && $case['created_at'] != '0000-00-00 00:00:00' && $case['created_at'] != NULL) {
            $created_at = date("Y-m-d\TH:i", strtotime($case['created_at']));
        } else {
            $created_at = date("Y-m-d\TH:i"); // Current time if null
        }

        $updated_at = '';
        if (isset($case['updated_at']) && $case['updated_at'] != '0000-00-00 00:00:00' && $case['updated_at'] != NULL) {
            $updated_at = date("F j, Y g:i A", strtotime($case['updated_at']));
        } else {
            $updated_at = 'Never updated';
        }
?>
<div class="edit-form-container">
    <form id="editCaseForm">
        <input type="hidden" name="case_id" value="<?= $case_id ?>">
        <input type="hidden" name="has_sanction" value="<?= $has_sanction ? '1' : '0' ?>">
        
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
            <div class="timestamp-info" style="background: #e8f4fd; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>📅 Case Timestamps:</strong><br>
                <small>Created: <?= date("F j, Y g:i A", strtotime($case['created_at'])) ?></small><br>
                <small>Last Updated: <?= $updated_at ?></small>
            </div>
            
            <div class="form-group">
                <label>Violation Type:</label>
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
                <label>Category:</label>
                <select name="violation_category" class="form-control" required id="categorySelect">
                    <option value="minor" <?= $violation_category == 'minor' ? 'selected' : '' ?>>Minor</option>
                    <option value="major" <?= $violation_category == 'major' ? 'selected' : '' ?>>Major</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Status:</label>
                <select name="status" class="form-control" required>
                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in-progress" <?= $status == 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $status == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Violation Description:</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Enter detailed description of the violation..."><?= $description ?></textarea>
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
                        <option value="counseling_mandatory" <?= $current_sanction_type == 'counseling_mandatory' ? 'selected' : '' ?>>Mandatory Counseling</option>
                        <option value="community_service_extended" <?= $current_sanction_type == 'community_service_extended' ? 'selected' : '' ?>>Extended Community Service</option>
                    </optgroup>
                </select>
            </div>
            
            <div class="form-group">
                <label>Sanction Status</label>
                <select class="form-control" name="sanction_status">
                    <option value="pending" <?= $sanction_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="in-progress" <?= $sanction_status == 'in-progress' ? 'selected' : '' ?>>In Progress</option>
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
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Case</button>
            <button type="button" class="btn btn-danger" onclick="closeModal('caseModal')">Cancel</button>
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

document.getElementById('editCaseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Add current timestamp
    formData.append('updated_at', new Date().toISOString().slice(0, 16));
    
    fetch('update_case.php', {  // Changed from update_violation.php
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Case updated successfully! Status: ' + (data.updated_status || 'updated'));
            closeModal('caseModal');
            location.reload();
        } else {
            alert('Error updating case: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating case: ' + error);
    });
});
</script>
<?php
    } else {
        echo "<p>Case not found.</p>";
    }
} else {
    echo "<p>No case ID provided.</p>";
}
?>