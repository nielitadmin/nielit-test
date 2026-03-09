<?php
require_once 'includes/db_connect.php';

echo "<pre>";
$stmt = $pdo->query("SELECT DATABASE() AS db");
print_r($stmt->fetch());

$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll());
echo "</pre>";
