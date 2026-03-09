<?php
$input = 'admin123';
$hash = '$2y$10$qkQxGGE15C0XHrR9M.IXzeGWYXUJ41PvVrO1Gc3S2MMoSvnHFRi7y';
var_dump(password_verify($input, $hash));
?>
