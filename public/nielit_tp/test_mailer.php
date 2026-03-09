<?php
require __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;

echo class_exists("PHPMailer\PHPMailer\PHPMailer") 
     ? "WORKING ✔" 
     : "NOT WORKING ❌";
