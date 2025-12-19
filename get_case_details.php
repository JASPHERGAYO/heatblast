<?php
session_start();
require_once 'database.php';
require_once 'admin_function.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Unauthorized access');
}

$case_id = $_GET['case_id'] ?? null;

if ($case_id) {
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
    
    if ($stmt) {
        $stmt->bind_param("i", $case_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $case = $result->fetch_assoc();
        
        if ($case) {
            $has_sanction = !empty($case['sanction_id']);
            $sanction_status = $case['sanction_status'] ?? 'none';
            
            if ($sanction_status == 'completed') {
                $display_status = 'resolved';
            } elseif ($case['status'] == 'resolved') {
                $display_status = 'resolved';
            } elseif ($sanction_status == 'in-progress') {
                $display_status = 'in-progress';
            } else {
                $display_status = $case['status'];
            }
            
            // ========== PORTABLE FILE HELPER FUNCTIONS ==========
            function getFileIcon($extension) {
                $icons = [
                    'pdf' => '📄',
                    'doc' => '📝',
                    'docx' => '📝',
                    'jpg' => '🖼️',
                    'jpeg' => '🖼️',
                    'png' => '🖼️',
                    'gif' => '🖼️',
                ];
                return $icons[$extension] ?? '📎';
            }
            
            function createPortableFileLink($file_path, $link_text = 'View File') {
                if (empty($file_path)) {
                    return '<span class="file-missing">No file uploaded</span>';
                }
                
                // Clean the path
                $clean_path = ltrim($file_path, './\\');
                
                // Get file info
                $file_ext = strtolower(pathinfo($clean_path, PATHINFO_EXTENSION));
                $file_icon = getFileIcon($file_ext);
                
                // Try multiple path strategies (works on any PC/server)
                $found_url = null;
                $base_dir = dirname($_SERVER['SCRIPT_NAME']);
                
                // Strategy 1: Try as-is (relative to current script)
                if (file_exists(__DIR__ . '/' . $clean_path)) {
                    $found_url = $clean_path;
                }
                // Strategy 2: Try with base directory
                elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $base_dir . '/' . $clean_path)) {
                    $found_url = $base_dir . '/' . $clean_path;
                }
                // Strategy 3: Try from document root
                elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $clean_path)) {
                    $found_url = '/' . $clean_path;
                }
                // Strategy 4: Try just the filename in the expected directory
                else {
                    $filename_only = basename($clean_path);
                    // Try violation proofs
                    if (file_exists(__DIR__ . '/uploads/violation_proofs/' . $filename_only)) {
                        $found_url = 'uploads/violation_proofs/' . $filename_only;
                    }
                    // Try sanction proofs
                    elseif (file_exists(__DIR__ . '/uploads/sanction_proofs/' . $filename_only)) {
                        $found_url = 'uploads/sanction_proofs/' . $filename_only;
                    }
                }
                
                if ($found_url) {
                    return '<div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">' . $file_icon . '</span>
                        <a href="' . htmlspecialchars($found_url) . '" target="_blank" class="file-link">
                            📎 ' . $link_text . '
                        </a>
                        <small style="color: #6c757d;">
                            (' . strtoupper($file_ext) . ' file)
                        </small>
                    </div>';
                } else {
                    return '<div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">' . $file_icon . '</span>
                        <span style="color: #dc3545;">
                            ⚠️ File not found
                        </span>
                        <small style="color: #6c757d;">
                            (' . htmlspecialchars(basename($clean_path)) . ')
                        </small>
                    </div>';
                }
            }
?>
<div class="case-details">
    <div class="detail-section">
        <h4>Student Information</h4>
        <div class="detail-row">
            <label>Name:</label>
            <span><?= htmlspecialchars($case['surname'] . ', ' . $case['firstname']) ?></span>
        </div>
        <div class="detail-row">
            <label>Student Number:</label>
            <span><?= htmlspecialchars($case['student_number']) ?></span>
        </div>
        <div class="detail-row">
            <label>Course & Section:</label>
            <span><?= htmlspecialchars($case['course'] . ' - ' . $case['section']) ?></span>
        </div>
        <div class="detail-row">
            <label>Year Level:</label>
            <span><?= htmlspecialchars($case['year_level'] ?? 'Not specified') ?></span>
        </div>
        <div class="detail-row">
            <label>Gender:</label>
            <span><?= htmlspecialchars($case['sex'] ?? 'Not specified') ?></span>
        </div>
        <div class="detail-row">
            <label>Email:</label>
            <span><?= htmlspecialchars($case['email']) ?></span>
        </div>
    </div>

    <div class="detail-section">
        <h4>Violation Details</h4>
        <div class="detail-row">
            <label>Violation Type:</label>
            <span><?= htmlspecialchars($case['violation_type']) ?></span>
        </div>
        <div class="detail-row">
            <label>Category:</label>
            <span class="status-badge violation-<?= $case['violation_category'] ?>">
                <?= strtoupper($case['violation_category']) ?>
            </span>
        </div>
        <div class="detail-row">
            <label>Violation Date:</label>
            <span><?= date("F j, Y g:i A", strtotime($case['created_at'])) ?></span>
        </div>
        <div class="detail-row">
            <label>Last Updated:</label>
            <span>
                <?php 
                if (isset($case['updated_at']) && $case['updated_at'] != '0000-00-00 00:00:00' && !is_null($case['updated_at'])) {
                    echo date("F j, Y g:i A", strtotime($case['updated_at']));
                } else {
                    echo 'Never updated';
                }
                ?>
            </span>
        </div>
        <div class="detail-row">
            <label>Recorded By:</label>
            <span><?= htmlspecialchars($case['recorded_by_name']) ?></span>
        </div>
        <div class="detail-row">
            <label>Status:</label>
            <span class="status-badge status-<?= $display_status ?>">
                <?= ucfirst($display_status) ?>
            </span>
            <?php if ($has_sanction): ?>
                <span class="sanction-exists-badge">
                    <?= $sanction_status == 'completed' ? 'Completed' : ($sanction_status == 'in-progress' ? 'In Progress' : 'Sanctioned') ?>
                </span>
            <?php endif; ?>
        </div>
        
        <!-- VIOLATION PROOF SECTION - PORTABLE -->
        <?php if (!empty($case['proof_filename'])): ?>
        <div class="detail-row">
            <label>Violation Evidence:</label>
            <span>
                <?= createPortableFileLink($case['proof_filename'], 'View Violation Evidence') ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- VIOLATION DESCRIPTION -->
        <?php if (!empty($case['violation_description'])): ?>
        <div class="detail-row">
            <label>Violation Description:</label>
            <span><?= nl2br(htmlspecialchars($case['violation_description'])) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- RESOLUTION NOTES -->
        <?php if (!empty($case['resolution_notes'])): ?>
        <div class="detail-row">
            <label>Resolution Notes:</label>
            <span><?= nl2br(htmlspecialchars($case['resolution_notes'])) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($has_sanction): ?>
    <div class="detail-section">
        <h4>Sanction Details</h4>
        <div class="detail-row">
            <label>Sanction ID:</label>
            <span>S-<?= str_pad($case['sanction_id'], 3, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="detail-row">
            <label>Sanction Type:</label>
            <span><?= htmlspecialchars($case['sanction_type']) ?></span>
        </div>
        <div class="detail-row">
            <label>Status:</label>
            <span class="status-badge status-<?= $sanction_status ?>">
                <?= ucfirst($sanction_status) ?>
            </span>
        </div>
        <div class="detail-row">
            <label>Due Date:</label>
            <span><?= date("F j, Y", strtotime($case['due_date'])) ?></span>
        </div>
        
        <!-- HOURS COMPLETED -->
        <?php if (!empty($case['hours_completed'])): ?>
        <div class="detail-row">
            <label>Hours Completed:</label>
            <span><?= htmlspecialchars($case['hours_completed']) ?> hours</span>
        </div>
        <?php endif; ?>
        
        <?php if ($case['sanction_created_at']): ?>
        <div class="detail-row">
            <label>Date Assigned:</label>
            <span><?= date("F j, Y g:i A", strtotime($case['sanction_created_at'])) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($case['completion_date']): ?>
        <div class="detail-row">
            <label>Completion Date:</label>
            <span><?= date("F j, Y", strtotime($case['completion_date'])) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- COUNSELOR NOTES -->
        <?php if (!empty($case['counselor_notes'])): ?>
        <div class="detail-row">
            <label>Counselor Notes:</label>
            <span><?= nl2br(htmlspecialchars($case['counselor_notes'])) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- COMPLETION PROOF - PORTABLE -->
        <?php if (!empty($case['completion_proof'])): ?>
        <div class="detail-row">
            <label>Completion Proof:</label>
            <span>
                <?= createPortableFileLink($case['completion_proof'], 'View Completion Proof') ?>
            </span>
        </div>
        <?php endif; ?>
        
        <?php if ($sanction_status != 'completed'): ?>
        <div class="detail-row" style="margin-top: 15px;">
            <label>Actions:</label>
            <button class="btn btn-success" onclick="uploadSanctionProof(<?= $case['sanction_id'] ?>)">Complete Sanction</button>
        </div>
        <?php endif; ?>
    </div>
    
    <?php elseif (!$has_sanction && $case['status'] == 'pending'): ?>
    <div class="detail-section" style="background: #fff3cd; border-color: #ffeaa7;">
        <h4 style="color: #856404;">⚠️ Action Required</h4>
        <div class="detail-row">
            <label>Current Status:</label>
            <span style="color: #856404; font-weight: bold;">
                Violation: <span class="status-badge status-pending">Pending</span> | 
                Sanction: <span class="status-badge status-pending">Not Assigned</span>
            </span>
        </div>
        <div class="detail-row">
            <label>Required Action:</label>
            <span style="color: #856404;">
                You must assign a sanction to proceed. The violation status will automatically update to "In Progress".
            </span>
        </div>
        <div class="detail-row" style="margin-top: 15px;">
            <label>Action:</label>
            <button class="btn btn-warning" onclick="assignSanctionToViolation(<?= $case['id'] ?>, '<?= $case['student_number'] ?>')">
                ⚠️ Assign Sanction Now
            </button>
        </div>
    </div>
    
    <?php elseif ($has_sanction && $sanction_status == 'pending' && $case['status'] == 'in-progress'): ?>
    <div class="detail-section" style="background: #d1ecf1; border-color: #bee5eb;">
        <h4 style="color: #0c5460;">📋 Current Progress</h4>
        <div class="detail-row">
            <label>Current Status:</label>
            <span style="color: #0c5460; font-weight: bold;">
                Violation: <span class="status-badge status-in-progress">In Progress</span> | 
                Sanction: <span class="status-badge status-pending">Pending</span>
            </span>
        </div>
        <div class="detail-row">
            <label>Next Step:</label>
            <span style="color: #0c5460;">Complete the sanction when finished to resolve this case.</span>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
        } else {
            echo "<p>Case not found.</p>";
        }
    } else {
        echo "<p>Database error: Unable to prepare statement.</p>";
        echo "<p>Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p>No case ID provided.</p>";
}
?>

<style>
.case-details .detail-section {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.case-details .detail-section h4 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}
.case-details .detail-row {
    display: flex;
    margin-bottom: 8px;
}
.case-details .detail-row label {
    font-weight: bold;
    width: 150px;
    flex-shrink: 0;
}
.case-details .detail-row span {
    flex-grow: 1;
}
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    margin: 0 2px;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-in-progress { background: #d1ecf1; color: #0c5460; }
.status-resolved { background: #d4edda; color: #155724; }
.violation-minor { background: #ffeaa7; color: #856404; }
.violation-major { background: #fab1a0; color: #d63031; }
.sanction-exists-badge {
    background: #17a2b8;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-left: 8px;
}

/* File link styling */
.file-link {
    display: inline-block;
    padding: 4px 8px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    text-decoration: none;
    color: #495057;
    font-size: 12px;
    transition: all 0.2s;
}

.file-link:hover {
    background: #e9ecef;
    border-color: #adb5bd;
    text-decoration: none;
    color: #495057;
}

.file-missing {
    font-size: 12px;
    color: #6c757d;
    cursor: help;
}
</style>