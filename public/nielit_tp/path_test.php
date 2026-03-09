<?php
echo "<h3>Real Server Path Finder</h3>";

echo "<p><b>Current File:</b><br>" . __FILE__ . "</p>";

echo "<p><b>Vendor Folder Expected At:</b><br>" . dirname(__FILE__) . "/vendor/autoload.php</p>";

echo "<p><b>Exists?</b> ";
if (file_exists(dirname(__FILE__) . "/vendor/autoload.php")) {
    echo "<span style='color:green;'>YES ✔</span>";
} else {
    echo "<span style='color:red;'>NO ❌</span>";
}
?>
