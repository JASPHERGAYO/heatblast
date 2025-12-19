<?php
session_start();
require_once 'database.php';
require_once 'admin_function.php';

// ==================== DEBUG MODE ====================
$debug_mode = false; // Set to false when working

if ($debug_mode) {
    echo "<div style='background: #f8f9fa; padding: 15px; border: 2px solid #e74c3c; margin: 10px; font-family: monospace;'>";
    echo "<h3 style='color: #e74c3c;'>🛠️ DEBUG MODE - sanction_details.php</h3>";
    
    // Check session
    echo "<strong>1. SESSION CHECK:</strong><br>";
    echo "admin_logged_in: " . (isset($_SESSION['admin_logged_in']) ? 'SET (' . ($_SESSION['admin_logged_in'] ? 'true' : 'false') . ')' : 'NOT SET') . "<br>";
    
    // Check GET parameters
    echo "<strong>2. GET PARAMETERS:</strong><br>";
    echo "\$_GET array:<br>";
    echo "<pre>";
    print_r($_GET);
    echo "</pre>";
    
    $sanction_id = $_GET['sanction_id'] ?? $_GET['id'] ?? null;
    echo "sanction_id from \$_GET['sanction_id']: " . ($_GET['sanction_id'] ?? 'NOT SET') . "<br>";
    echo "sanction_id from \$_GET['id']: " . ($_GET['id'] ?? 'NOT SET') . "<br>";
    echo "<strong>3. FINAL SANCTION_ID VALUE:</strong> " . ($sanction_id ?: 'NULL/EMPTY') . "<br>";
    echo "</div>";
} else {
    $sanction_id = $_GET['sanction_id'] ?? $_GET['id'] ?? null;
}
// ==================== END DEBUG ====================

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('<div style="background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px;">
        <h4 style="color: #721c24;">❌ Unauthorized Access</h4>
        <p>Please log in as administrator to view sanction details.</p>
    </div>');
}

if (!$sanction_id) {
    echo '<div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 10px;">
        <h4 style="color: #856404;">⚠️ Error: No Sanction ID Provided</h4>
        <p>Please provide a sanction ID in the URL.</p>
        <p><strong>Try one of these formats:</strong></p>
        <ul>
            <li>sanction_details.php?sanction_id=27</li>
            <li>sanction_details.php?id=27</li>
        </ul>
    </div>';
    exit;
}

// Validate sanction_id is a number
if (!is_numeric($sanction_id)) {
    echo '<div style="background: #f8d7da; padding: 15px; border: 1px solid #f5c5c5; margin: 10px;">
        <h4 style="color: #721c24;">❌ Invalid Sanction ID</h4>
        <p>Sanction ID must be a number. Received: ' . htmlspecialchars($sanction_id) . '</p>
    </div>';
    exit;
}

// Convert to integer
$sanction_id = (int)$sanction_id;

try {
    // Updated SQL query to include all missing fields
    $stmt = $conn->prepare("
        SELECT 
            v.*,
            v.description as violation_description,  -- Added description
            v.resolution_notes,                     -- Added resolution_notes from violations
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
            s.counselor_notes,                     -- Already has counselor_notes from sanctions
            s.hours_completed,                     -- Added hours_completed from sanctions
            s.created_at as sanction_created_at,
            v.proof_filename as violation_proof,
            CASE 
                WHEN v.user_type = 'admin' THEN 'Administrator'
                WHEN v.user_type = 'staff' THEN staff.fullname
                ELSE 'Unknown'
            END as recorded_by_name
        FROM sanctions s
        JOIN violations v ON s.violation_id = v.id
        JOIN student_profiles sp ON v.user_id = sp.user_id
        JOIN users u ON sp.user_id = u.id
        LEFT JOIN staff ON v.recorded_by = staff.id AND v.user_type = 'staff'
        WHERE s.id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: Unable to prepare SQL statement. Error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $sanction_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: Unable to execute query. Error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Database error: Unable to get result. Error: " . $stmt->error);
    }
    
    if ($result->num_rows === 0) {
        echo '<div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 10px;">
            <h4 style="color: #856404;">⚠️ Sanction Not Found</h4>
            <p>No sanction found with ID: ' . htmlspecialchars($sanction_id) . '</p>
            <p>The sanction may have been deleted or the ID is incorrect.</p>';
        
        if ($debug_mode) {
            // Try to find any sanctions to help debug
            $check_stmt = $conn->prepare("SELECT id FROM sanctions ORDER BY id DESC LIMIT 5");
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $sanction_ids = [];
            while ($row = $check_result->fetch_assoc()) {
                $sanction_ids[] = $row['id'];
            }
            echo '<p><strong>Available sanction IDs:</strong> ' . implode(', ', $sanction_ids) . '</p>';
        }
        
        echo '</div>';
        exit;
    }
    
    $sanction = $result->fetch_assoc();
    $stmt->close();
    
    if (!$sanction) {
        throw new Exception("Database error: Unable to fetch sanction data.");
    }
    
    $has_sanction = !empty($sanction['sanction_id']);
    $sanction_status = $sanction['sanction_status'] ?? 'none';
    
    if ($sanction_status == 'completed') {
        $display_status = 'resolved';
    } elseif ($sanction['status'] == 'resolved') {
        $display_status = 'resolved';
    } elseif ($sanction_status == 'in-progress') {
        $display_status = 'in-progress';
    } else {
        $display_status = $sanction['status'];
    }
    
    // Helper function for file icons
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
    
    // Function to create portable file links
    function createFileLink($file_path, $file_label = 'View File') {
        if (empty($file_path)) {
            return '<span class="file-missing">No file uploaded</span>';
        }
        
        // Clean the path
        $clean_path = ltrim($file_path, './\\');
        
        // Get file info
        $file_ext = strtolower(pathinfo($clean_path, PATHINFO_EXTENSION));
        $file_icon = getFileIcon($file_ext);
        
        // Create URL - use relative path
        $file_url = $clean_path;
        
        // Check if file exists
        $file_exists = file_exists($clean_path) || 
                      file_exists('.' . $clean_path) || 
                      file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $clean_path);
        
        if ($file_exists) {
            return '<div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;">' . $file_icon . '</span>
                <a href="' . htmlspecialchars($file_url) . '" target="_blank" class="file-link" style="text-decoration: none;">
                    📎 ' . $file_label . '
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
<!-- SANCTION DETAILS START -->
<div class="sanction-details">
    
    <?php if ($debug_mode): ?>
    <div style="background: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; margin: 10px 0; font-size: 12px;">
        <strong>Debug Loaded:</strong> 
        Sanction ID: <?= $sanction_id ?> | 
        Sanction Found: ✅ YES | 
        Sanction Status: <?= $sanction_status ?> |
        Violation ID: <?= $sanction['id'] ?? 'N/A' ?>
    </div>
    <?php endif; ?>

    <div class="detail-section">
        <h4>Student Information</h4>
        <div class="detail-row">
            <label>Name:</label>
            <span><?= htmlspecialchars($sanction['surname'] . ', ' . $sanction['firstname']) ?></span>
        </div>
        <div class="detail-row">
            <label>Student Number:</label>
            <span><?= htmlspecialchars($sanction['student_number']) ?></span>
        </div>
        <div class="detail-row">
            <label>Course & Section:</label>
            <span><?= htmlspecialchars($sanction['course'] . ' - ' . $sanction['section']) ?></span>
        </div>
        <div class="detail-row">
            <label>Year Level:</label>
            <span><?= htmlspecialchars($sanction['year_level'] ?? 'Not specified') ?></span>
        </div>
        <div class="detail-row">
            <label>Gender:</label>
            <span><?= htmlspecialchars($sanction['sex'] ?? 'Not specified') ?></span>
        </div>
        <div class="detail-row">
            <label>Email:</label>
            <span><?= htmlspecialchars($sanction['email']) ?></span>
        </div>
    </div>

    <div class="detail-section">
        <h4>Violation Details</h4>
        <div class="detail-row">
            <label>Violation Type:</label>
            <span><?= htmlspecialchars($sanction['violation_type']) ?></span>
        </div>
        <div class="detail-row">
            <label>Category:</label>
            <span class="status-badge violation-<?= htmlspecialchars($sanction['violation_category']) ?>">
                <?= strtoupper($sanction['violation_category']) ?>
            </span>
        </div>
        <div class="detail-row">
            <label>Violation Date:</label>
            <span><?= date("F j, Y g:i A", strtotime($sanction['created_at'])) ?></span>
        </div>
        <div class="detail-row">
            <label>Last Updated:</label>
            <span>
                <?php 
                if (isset($sanction['updated_at']) && $sanction['updated_at'] != '0000-00-00 00:00:00' && !is_null($sanction['updated_at'])) {
                    echo date("F j, Y g:i A", strtotime($sanction['updated_at']));
                } else {
                    echo 'Never updated';
                }
                ?>
            </span>
        </div>
        <div class="detail-row">
            <label>Recorded By:</label>
            <span><?= htmlspecialchars($sanction['recorded_by_name']) ?></span>
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
        
        <!-- VIOLATION PROOF SECTION -->
        <?php if (!empty($sanction['violation_proof'])): ?>
        <div class="detail-row">
            <label>Violation Evidence:</label>
            <span>
                <?= createFileLink($sanction['violation_proof'], 'View Violation Evidence') ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- VIOLATION DESCRIPTION -->
        <?php if (!empty($sanction['violation_description'])): ?>
        <div class="detail-row">
            <label>Violation Description:</label>
            <span><?= nl2br(htmlspecialchars($sanction['violation_description'])) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- RESOLUTION NOTES (from violations table) -->
        <?php if (!empty($sanction['resolution_notes'])): ?>
        <div class="detail-row">
            <label>Resolution Notes:</label>
            <span><?= nl2br(htmlspecialchars($sanction['resolution_notes'])) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="detail-section">
        <h4>Sanction Details</h4>
        <div class="detail-row">
            <label>Sanction ID:</label>
            <span>S-<?= str_pad($sanction['sanction_id'], 3, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="detail-row">
            <label>Sanction Type:</label>
            <span><?= htmlspecialchars($sanction['sanction_type']) ?></span>
        </div>
        <div class="detail-row">
            <label>Status:</label>
            <span class="status-badge status-<?= $sanction_status ?>">
                <?= ucfirst($sanction_status) ?>
            </span>
        </div>
        <div class="detail-row">
            <label>Due Date:</label>
            <span><?= date("F j, Y", strtotime($sanction['due_date'])) ?></span>
        </div>
        
        <!-- HOURS COMPLETED (from sanctions table) -->
        <?php if (!empty($sanction['hours_completed'])): ?>
        <div class="detail-row">
            <label>Hours Completed:</label>
            <span><?= htmlspecialchars($sanction['hours_completed']) ?> hours</span>
        </div>
        <?php endif; ?>
        
        <?php if ($sanction['sanction_created_at']): ?>
        <div class="detail-row">
            <label>Date Assigned:</label>
            <span><?= date("F j, Y g:i A", strtotime($sanction['sanction_created_at'])) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($sanction['completion_date']): ?>
        <div class="detail-row">
            <label>Completion Date:</label>
            <span><?= date("F j, Y", strtotime($sanction['completion_date'])) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- COUNSELOR NOTES (from sanctions table) -->
        <?php if (!empty($sanction['counselor_notes'])): ?>
        <div class="detail-row">
            <label>Counselor Notes:</label>
            <span><?= nl2br(htmlspecialchars($sanction['counselor_notes'])) ?></span>
        </div>
        <?php endif; ?>
   <?php if (!empty($sanction['completion_proof'])): ?>
<div class="detail-row">
    <label>Completion Proof:</label>
    <span>
        <?php
        $file_path = $sanction['completion_proof'];
        
        // ========== PORTABLE PATH RESOLUTION ==========
        // 1. Clean the path - remove any leading slashes or dots
        $file_path = ltrim($file_path, './\\');
        
        // 2. If the path contains any absolute directory reference, strip it
        // This handles cases where old data has full paths
        $base_dir = dirname($_SERVER['SCRIPT_NAME']);
        if (strpos($file_path, $base_dir . '/') === 0) {
            $file_path = substr($file_path, strlen($base_dir) + 1);
        }
        
        // 3. Try multiple methods to find the file
        $found_url = null;
        $methods = [];
        
        // Method 1: Relative to current script location
        $methods[] = [
            'label' => 'Relative to script',
            'url' => $file_path,
            'path' => __DIR__ . '/' . $file_path
        ];
        
        // Method 2: With base directory
        $methods[] = [
            'label' => 'With base dir',
            'url' => $base_dir . '/' . $file_path,
            'path' => $_SERVER['DOCUMENT_ROOT'] . $base_dir . '/' . $file_path
        ];
        
        // Method 3: Just from document root (if file is at root level)
        $methods[] = [
            'label' => 'From doc root',
            'url' => '/' . $file_path,
            'path' => $_SERVER['DOCUMENT_ROOT'] . '/' . $file_path
        ];
        
        // Try each method
        foreach ($methods as $method) {
            if (file_exists($method['path'])) {
                $found_url = $method['url'];
                break;
            }
        }
        
        // Get file info for display
        $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $file_icon = '📎';
        $icons = ['pdf' => '📄', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'doc' => '📝', 'docx' => '📝'];
        if (isset($icons[$file_ext])) {
            $file_icon = $icons[$file_ext];
        }
        ?>
        
        <?php if ($debug_mode): ?>
        <div style="font-size: 10px; color: #666; background: #f8f9fa; padding: 5px; margin: 5px 0; border: 1px solid #dee2e6;">
            <strong>File Debug:</strong><br>
            Original DB: <?= htmlspecialchars($sanction['completion_proof']) ?><br>
            Cleaned: <?= htmlspecialchars($file_path) ?><br>
            Base Dir: <?= htmlspecialchars($base_dir) ?><br>
            Found URL: <?= $found_url ? htmlspecialchars($found_url) : 'NOT FOUND' ?><br>
            File Exists: <?= $found_url ? '✅ YES' : '❌ NO' ?>
        </div>
        <?php endif; ?>
        
        <?php if ($found_url): ?>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;"><?= $file_icon ?></span>
                <a href="<?= htmlspecialchars($found_url) ?>" target="_blank" class="file-link">
                    📎 View Completion Proof
                </a>
                <small style="color: #6c757d;">
                    (<?= strtoupper($file_ext) ?> file)
                </small>
            </div>
        <?php else: ?>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 20px;"><?= $file_icon ?></span>
                <span style="color: #dc3545;">
                    ⚠️ File Not Found
                </span>
                <small style="color: #6c757d;">
                    (Stored: <?= htmlspecialchars(basename($file_path)) ?>)
                </small>
                <?php if ($debug_mode): ?>
                <br><small style="color: #666;">Tried paths:<br>
                <?php foreach ($methods as $m): ?>
                    • <?= $m['label'] ?>: <?= file_exists($m['path']) ? '✅' : '❌' ?><br>
                <?php endforeach; ?>
                </small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </span>
</div>
<?php endif; ?>
<!-- SANCTION DETAILS END -->

<?php
} catch (Exception $e) {
    echo '<div style="background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px;">
        <h4 style="color: #721c24;">❌ Error Loading Sanction Details</h4>
        <p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    
    if ($debug_mode) {
        echo '<p><strong>Debug Info:</strong></p>
            <ul>
                <li>Sanction ID Attempted: ' . htmlspecialchars($sanction_id ?? 'NULL') . '</li>
                <li>Session Active: ' . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . '</li>
                <li>Database Connected: ' . ($conn ? 'Yes' : 'No') . '</li>
            </ul>';
    }
    
    echo '</div>';
}
?>

<style>
.sanction-details .detail-section {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.sanction-details .detail-section h4 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}
.sanction-details .detail-row {
    display: flex;
    margin-bottom: 8px;
}
.sanction-details .detail-row label {
    font-weight: bold;
    width: 150px;
    flex-shrink: 0;
}
.sanction-details .detail-row span {
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