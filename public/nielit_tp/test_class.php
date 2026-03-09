<?php
require __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;

if (class_exists("PHPMailer\PHPMailer\PHPMailer")) {
    echo "PHPMailer WORKING ✔";
} else {
    echo "PHPMailer NOT WORKING ❌";
}
