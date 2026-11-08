<?php
session_start();
if (!isset($_SESSION['barangay_id'])) {
    header('Location: barangay_login.php');
    exit();
}

header('Location: barangay_hazard_map_view.php');
exit();
