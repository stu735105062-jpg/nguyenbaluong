<?php
session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    $_SESSION['error'] = "⚠️ Please login to access this page.";
    header("Location: ../public/index.php"); // adjust path if needed
    exit();
}
?>