<?php
include 'phpqrcode/qrlib.php';
include 'database.php';

// User ID (from database or request)
$user_id = $created_user_id ?? ($_GET['user_id'] ?? $_POST['user_id'] ?? null);
if (!$user_id) {
    // Try to obtain from session if used in web flow
    session_start();
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
}
if (!$user_id) {
    die('No user_id provided to generate_qr.php');
}
$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/";
// The URL that the QR will open - Use scan_handler.php consistently
$qr_url = $base_url . "scan_handler.php?student_id=" . $user_id;

// Folder where QR will be saved (use qrcodes/ to match the rest of the app)
$qr_folder = 'qrcodes/';
if (!file_exists($qr_folder)) {
    mkdir($qr_folder, 0777, true);
}

// Final file path
// actual filesystem path to write the file
$qr_file = $qr_folder . "qr_user_" . $user_id . ".png";

// Generate QR
QRcode::png($qr_url, $qr_file, QR_ECLEVEL_L, 6);

$qr_path_for_db = 'qrcodes/qr_user_' . $user_id . '.png';
// Save the QR path into database (relative path usable in src or file checks)
$stmt = $conn->prepare("UPDATE student_profiles SET qr_code = ? WHERE user_id = ?");
$stmt->bind_param("si", $qr_path_for_db, $user_id);
$stmt->execute();
?>