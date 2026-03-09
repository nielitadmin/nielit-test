<?php
require "/home/u664913565/domains/nielitbhubaneswar.in/public_html/__test/public/nielit_tp/vendor/autoload.php";

if (class_exists("PHPMailer\PHPMailer\PHPMailer")) {
    echo "PHPMailer LOADED ✔ Working!";
} else {
    echo "PHPMailer NOT LOADED ❌";
}
