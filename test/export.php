<?php
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=MyData.csv");

require '../config/config.php';
require '../includes/load_data.php'; // gives us $result

$output = fopen("php://output", "w");

// Write column headers
fputcsv($output, ['ID', 'Name', 'Email', 'Phone']);

// Write rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [$row['id'], $row['name'], $row['email'], $row['phone']]);
}

fclose($output);
exit();
?>
