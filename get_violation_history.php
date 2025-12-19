<?php
session_start();
require_once 'database.php';
require_once 'admin_function.php';
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_staff = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$is_admin && !$is_staff) {
    die("Unauthorized");
}

$student_id = $_GET['student_id'] ?? 0;

// Get student data
$student_data = getStudentData($student_id, $conn);

if (!$student_data) {
    echo "<p>Student not found.</p>";
    exit;
}

// Get violation history
$violation_history = getStudentViolationHistory($student_data['user_id'], $conn);

if (!$violation_history || $violation_history->num_rows === 0) {
    echo "<p>No violation history found for this student.</p>";
    exit;
}
?>

<h3>Violation History for <?= htmlspecialchars($student_data['surname'] . ', ' . $student_data['firstname']) ?></h3>
<p>Student Number: <?= htmlspecialchars($student_data['student_number']) ?></p>

<div style="max-height: 400px; overflow-y: auto; margin-top: 20px;">
    <?php while($violation = $violation_history->fetch_assoc()): ?>
        <div class="violation-history-item <?= $violation['violation_category'] ?> <?= $violation['status'] ?>">
            <div class="violation-history-header">
                <strong><?= htmlspecialchars($violation['violation_type']) ?></strong>
                <span class="violation-history-date">
                    <?= date("M j, Y g:i A", strtotime($violation['created_at'])) ?>
                </span>
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                <span class="status-badge violation-<?= $violation['violation_category'] ?>">
                    <?= strtoupper($violation['violation_category']) ?>
                </span>
                <span class="status-badge status-<?= $violation['status'] ?>">
                    <?= ucfirst($violation['status']) ?>
                </span>
                <?php if (!empty($violation['sanction_type'])): ?>
                <span class="sanction-exists-badge">Sanctioned</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($violation['description'])): ?>
                <p style="margin: 5px 0; font-size: 0.9em;"><?= htmlspecialchars($violation['description']) ?></p>
            <?php endif; ?>
            <?php if (!empty($violation['proof_filename'])): ?>
                <p><a href="<?= htmlspecialchars($violation['proof_filename']) ?>" target="_blank">View Proof</a></p>
            <?php endif; ?>
            <?php if (!empty($violation['sanction_type'])): ?>
                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd;">
                    <strong>Sanction:</strong> <?= htmlspecialchars($violation['sanction_type']) ?>
                    <?php if (!empty($violation['due_date'])): ?>
                        <br><small>Due: <?= date("M j, Y", strtotime($violation['due_date'])) ?></small>
                    <?php endif; ?>
                    <?php if (!empty($violation['completion_date'])): ?>
                        <br><small>Completed: <?= date("M j, Y", strtotime($violation['completion_date'])) ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>