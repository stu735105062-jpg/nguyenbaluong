<?php
require '../config/config.php';

$query = "select * from student_info";

 if (!empty($_GET["searchBox"])) {
    $search = $_GET["searchBox"];
    $query .= " WHERE name LIKE '%$search%'";
}

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error loading Data" . mysqli_errno($conn));
}


?>