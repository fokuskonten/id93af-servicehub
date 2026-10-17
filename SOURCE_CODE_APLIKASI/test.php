<?php
// Test file untuk debugging
echo "<h1>Test PHP File</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Directory: " . __DIR__ . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

// Test file existence
$files_to_check = [
    'config/database.php',
    'config/session.php',
    'includes/header.php',
    'includes/footer.php',
    'classes/User.php',
    'classes/Service.php'
];

echo "<h2>File Check:</h2>";
foreach ($files_to_check as $file) {
    $exists = file_exists($file) ? "✅ EXISTS" : "❌ NOT FOUND";
    echo "<p>$file: $exists</p>";
}

// Test database connection
echo "<h2>Database Test:</h2>";
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        $db = new Database();
        echo "<p>✅ Database connection successful</p>";
    } else {
        echo "<p>❌ Database config file not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

phpinfo();
?>