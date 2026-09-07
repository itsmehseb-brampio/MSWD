<?php
session_start();
if (!isset($_SESSION['barangay_name'])) {
    header("Location: barangay_login.php");
    exit();
}
header("Location: barangay_info.php");
exit;
