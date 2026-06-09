<?php
require_once '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['session_id'])) {
    $student_id = $_SESSION['user_id'];
    $session_id = (int)$_POST['session_id'];

    // Check if already enrolled
    $checkSql = "SELECT id FROM enrollments WHERE student_id = $student_id AND session_id = $session_id";
    $result = $conn->query($checkSql);

    if ($result->num_rows == 0) {
        $sql = "INSERT INTO enrollments (student_id, session_id) VALUES ($student_id, $session_id)";
        $conn->query($sql);
    }
    
    header("Location: dashboard.php?enrolled=success");
    exit();
}

header("Location: dashboard.php");
exit();
?>
