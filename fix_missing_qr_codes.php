<?php
// fix_missing_qrcodes.php
include 'database.php';
include 'phpqrcode/qrlib.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fixing Missing QR Codes</h2>";

// Get all student profiles
$result = $conn->query("SELECT user_id, qr_code FROM student_profiles");

// Ensure qrcodes directory exists
$qr_folder = __DIR__ . '/qrcodes/';
if (!file_exists($qr_folder)) {
    mkdir($qr_folder, 0777, true);
    echo "Created qrcodes directory<br>";
}

$regenerated = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    $db_qr_path = $row['qr_code'];
    
    // Determine the correct file path
    $qr_file_name = 'qr_user_' . $user_id . '.png';
    $qr_full_path = $qr_folder . $qr_file_name;
    $qr_db_path = 'qrcodes/' . $qr_file_name;
    
    // Check if file exists
    if (!file_exists($qr_full_path)) {
        // Generate QR URL
$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/";
$qr_url = $base_url . "scan_handler.php?student_id=" . $user_id;
        
        // Generate QR code
        QRcode::png($qr_url, $qr_full_path, QR_ECLEVEL_L, 6);
        
        // Update database
        $stmt = $conn->prepare("UPDATE student_profiles SET qr_code = ? WHERE user_id = ?");
        $stmt->bind_param("si", $qr_db_path, $user_id);
        $stmt->execute();
        
        echo "✅ Generated QR for user $user_id<br>";
        $regenerated++;
    } else {
        echo "✓ QR exists for user $user_id<br>";
        $skipped++;
    }
}

echo "<hr><strong>Summary:</strong><br>";
echo "Regenerated: $regenerated QR codes<br>";
echo "Skipped: $skipped users<br>";
echo '<br><a href="profile.php">Go back to Profile</a>';
?>