<?php
$password = 'admin123'; // you can change this if you want a different admin password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "<h3>Generated hash for password: <code>$password</code></h3>";
echo "<pre>$hash</pre>";
?>
