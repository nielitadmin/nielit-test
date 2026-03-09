<?php
// CLI worker: parse new Excel files in uploads/submissions and insert students
require_once __DIR__.'/../includes/db_connect.php';
require_once __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$dir = __DIR__.'/../uploads/submissions';
if (!is_dir($dir)) {
    echo "No uploads folder found.\n";
    exit;
}
$files = glob($dir.'/*');
foreach ($files as $f) {
    // check if file already processed by looking for a marker file
    $marker = $f . '.processed';
    if (file_exists($marker)) continue;
    echo "Processing $f ...\n";
    try {
        $reader = IOFactory::createReaderForFile($f);
        $spreadsheet = $reader->load($f);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->getHighestDataRow();
        $cols = $sheet->getHighestDataColumn();
        // Expect header row: Roll | Name | DOB | Email | Mobile
        $header = [];
        for ($c = 'A'; $c <= $cols; $c++) {
            $header[] = trim((string)$sheet->getCell($c . '1')->getValue());
            if ($c === $cols) break;
        }
        // Find submission_id by matching filename in submissions table
        $stmt = $pdo->prepare('SELECT id FROM submissions WHERE upload_filename = :fname LIMIT 1');
        $stmt->execute(['fname'=>$f]);
        $sub = $stmt->fetch();
        $submission_id = $sub ? $sub['id'] : null;
        for ($r = 2; $r <= $rows; $r++) {
            $roll = (string)$sheet->getCell('A'.$r)->getValue();
            $name = (string)$sheet->getCell('B'.$r)->getValue();
            $dob_raw = (string)$sheet->getCell('C'.$r)->getValue();
            $email = (string)$sheet->getCell('D'.$r)->getValue();
            $mobile = (string)$sheet->getCell('E'.$r)->getValue();
            $dob = null;
            if (is_numeric($dob_raw)) {
                $dob = date('Y-m-d', PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($dob_raw));
            } else {
                $d = date_create($dob_raw);
                if ($d) $dob = $d->format('Y-m-d');
            }
            $ins = $pdo->prepare('INSERT INTO students (submission_id, roll, name, dob, email, mobile) VALUES (:sub,:roll,:name,:dob,:email,:mobile)');
            $ins->execute([
                'sub'=>$submission_id,
                'roll'=>$roll,
                'name'=>$name,
                'dob'=>$dob,
                'email'=>$email,
                'mobile'=>$mobile
            ]);
        }
        // mark processed
        touch($marker);
        echo "Processed $f -> $rows rows\n";
    } catch (Exception $e) {
        echo "Error processing $f: ".$e->getMessage()."\n";
    }
}
echo "Worker finished.\n";
?>
