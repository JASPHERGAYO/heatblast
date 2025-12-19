<?php
// check_file.php
$file_path = 'uploads/violation_proofs/violation_proof_1764691824_692f0f70529ac.png';

echo "<h3>File Check Diagnostic</h3>";
echo "File to check: " . htmlspecialchars($file_path) . "<br><br>";

// Check various paths
$checks = [
    'Relative path' => $file_path,
    'With ./' => './' . $file_path,
    'With /' => '/' . $file_path,
    'From document root' => $_SERVER['DOCUMENT_ROOT'] . '/' . $file_path,
    'Just filename' => 'violation_proof_1764691824_692f0f70529ac.png',
];

foreach ($checks as $label => $path) {
    $exists = file_exists($path) ? '✅ YES' : '❌ NO';
    echo "<strong>$label:</strong> " . htmlspecialchars($path) . "<br>";
    echo "Exists: $exists<br>";
    
    if (file_exists($path)) {
        echo "Size: " . filesize($path) . " bytes<br>";
        echo "Permissions: " . substr(sprintf('%o', fileperms($path)), -4) . "<br>";
    }
    echo "<br>";
}

echo "<h4>Server Information:</h4>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Check uploads directory
echo "<h4>Uploads Directory Contents:</h4>";
$upload_dir = 'uploads/violation_proofs/';
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    echo "<pre>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo htmlspecialchars($file) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "Directory doesn't exist!<br>";
    echo "Trying to create it...<br>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "Directory created successfully.<br>";
    } else {
        echo "Failed to create directory.<br>";
    }
}
?>