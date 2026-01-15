<?php
session_start();
unset($_SESSION['authenticated']); 
$_SESSION['success'] = "You have successfully logged out!"; 
header("Location: index.php"); 
exit;
?>
