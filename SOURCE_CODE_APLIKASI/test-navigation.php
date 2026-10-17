<?php
// Test file untuk debugging navigation
echo "<h1>Navigation Test</h1>";

echo "<h2>Server Variables:</h2>";
echo "<pre>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";

echo "<h2>Path Detection:</h2>";
$script_path = $_SERVER['PHP_SELF'];
echo "Script Path: " . $script_path . "<br>";

if (strpos($script_path, '/admin/') !== false) {
    echo "Current Directory: admin<br>";
    echo "Base Path: ../";
} elseif (strpos($script_path, '/customer/') !== false) {
    echo "Current Directory: customer<br>";
    echo "Base Path: ../";
} elseif (strpos($script_path, '/public/') !== false) {
    echo "Current Directory: public<br>";
    echo "Base Path: ../";
} else {
    echo "Current Directory: root<br>";
    echo "Base Path: (empty)";
}

echo "<h2>Test Links:</h2>";
echo '<a href="../index.php">Home</a><br>';
echo '<a href="dashboard.php">Dashboard</a><br>';
echo '<a href="customers.php">Customers</a><br>';
echo '<a href="services.php">Services</a><br>';
?>