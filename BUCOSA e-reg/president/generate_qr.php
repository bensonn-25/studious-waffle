<?php
require_once '../includes/db_connect.php';
require_once '../includes/phpqrcode.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'president') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$session_id = (int)$_GET['id'];
$president_id = $_SESSION['user_id'];

// Fetch session details
$sql = "SELECT * FROM sessions WHERE id = $session_id AND created_by = $president_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Session not found or unauthorized.");
}

header("Location: session_attendance.php?id=" . $session_id);
exit();
