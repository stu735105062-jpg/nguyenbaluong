<?php
require '../config/config.php';
session_start();

if (!isset($_POST['restore'])) {
    header('Location: ../public/home.php');
    exit();
}

// ✅ Validate file
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Please upload a valid CSV file.';
    header('Location: ../public/home.php');
    exit();
}

// ✅ Check file type (must be CSV)
$fileType = mime_content_type($_FILES['file']['tmp_name']);
if (strpos($fileType, 'csv') === false && strpos($_FILES['file']['name'], '.csv') === false) {
    $_SESSION['error'] = 'Only CSV files are allowed.';
    header('Location: ../public/home.php');
    exit();
}

$tmpName = $_FILES['file']['tmp_name'];
$handle = fopen($tmpName, 'r');
if ($handle === false) {
    $_SESSION['error'] = 'Unable to open uploaded file.';
    header('Location: ../public/home.php');
    exit();
}

// ✅ Read header row
$header = fgetcsv($handle, 1000, ',');
if ($header === false) {
    $_SESSION['error'] = 'CSV file is empty.';
    header('Location: ../public/home.php');
    exit();
}

// ✅ Assume CSV has columns: id, name, email, phone
$inserted = 0;
$skipped = 0;

while (($row = fgetcsv($handle, 1000, ',')) !== false) {
    $id    = intval($row[0]);
    $name  = mysqli_real_escape_string($conn, $row[1]);
    $email = mysqli_real_escape_string($conn, $row[2]);
    $phone = mysqli_real_escape_string($conn, $row[3]);

    // Check if ID already exists
    $check = mysqli_query($conn, "SELECT id FROM student_info WHERE id = $id");
    if (mysqli_num_rows($check) > 0) {
        $skipped++;
        continue;
    }

    // Insert new record
    $sql = "INSERT INTO student_info (id, name, email, phone) VALUES ($id, '$name', '$email', '$phone')";
    if (mysqli_query($conn, $sql)) {
        $inserted++;
    }
}

fclose($handle);

// ✅ Display messages
if ($inserted > 0) {
    $_SESSION['success'] = "$inserted record(s) restored successfully.";
}
if ($skipped > 0) {
    $_SESSION['error'] = "$skipped record(s) already exist and were skipped.";
}

header('Location: ../public/home.php');
exit();
?>
