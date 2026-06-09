<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int)$_SESSION['user_id'];
$message = '';
$messageType = 'danger';

function attendance_window_sql($conn) {
    $columns = get_attendance_window_columns($conn);
    $selectedColumns = [
        'id',
        'title',
        'status',
        'qr_token'
    ];

    if ($columns['started'] === 'attendance_started_at') {
        $selectedColumns[] = 'attendance_started_at';
    }

    if ($columns['expires'] === 'attendance_expires_at') {
        $selectedColumns[] = 'attendance_expires_at';
    } elseif ($columns['close_time'] === 'attendance_close_time') {
        $selectedColumns[] = 'attendance_close_time';
    }

    return $selectedColumns;
}

function resolve_expiry_value($session, $columns) {
    if (!empty($session['attendance_expires_at'])) {
        return $session['attendance_expires_at'];
    }

    if (!empty($session['attendance_close_time'])) {
        return $session['attendance_close_time'];
    }

    return null;
}

if (!isset($_GET['token']) || trim($_GET['token']) === '') {
    $message = 'No attendance token provided.';
} else {
    $token = $conn->real_escape_string(trim($_GET['token']));
    $columns = get_attendance_window_columns($conn);
    $selectedColumns = attendance_window_sql($conn);
    $sessionSql = 'SELECT ' . implode(', ', $selectedColumns) . " FROM sessions WHERE qr_token = '$token' LIMIT 1";
    $result = $conn->query($sessionSql);

    if ($result && $result->num_rows > 0) {
        $session = $result->fetch_assoc();
        $sessionId = (int)$session['id'];
        $sessionTitle = $session['title'];
        $expiresAt = resolve_expiry_value($session, $columns);
        $isActive = $session['status'] === 'ongoing' && !empty($expiresAt) && strtotime($expiresAt) >= time();

        if (!$isActive) {
            $message = 'Attendance is not active or this QR code has expired.';
            $messageType = 'warning';
        } else {
            $enrollCheckSql = "SELECT id FROM enrollments WHERE student_id = $studentId AND session_id = $sessionId";
            $isEnrolled = $conn->query($enrollCheckSql);

            if ($isEnrolled && $isEnrolled->num_rows > 0) {
                $attendanceCheckSql = "SELECT id FROM attendance WHERE student_id = $studentId AND session_id = $sessionId";
                $alreadyMarked = $conn->query($attendanceCheckSql);

                if ($alreadyMarked && $alreadyMarked->num_rows === 0) {
                    $deviceIp = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                    $insertSql = "INSERT INTO attendance (student_id, session_id, device_ip) VALUES ($studentId, $sessionId, '$deviceIp')";

                    if ($conn->query($insertSql) === TRUE) {
                        $message = 'Attendance recorded successfully for ' . htmlspecialchars($sessionTitle) . '.';
                        $messageType = 'success';
                    } else {
                        $message = 'Database error: Could not mark attendance.';
                    }
                } else {
                    $message = 'You have already marked your attendance for this session.';
                    $messageType = 'warning';
                }
            } else {
                $message = 'You must enroll in this session before marking attendance.';
            }
        }
    } else {
        $message = 'Invalid or expired QR code.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <?php if ($messageType === 'success'): ?>
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4 class="text-success fw-bold">Attendance Recorded</h4>
                    <?php elseif ($messageType === 'warning'): ?>
                        <i class="fas fa-exclamation-circle fa-4x text-warning mb-3"></i>
                        <h4 class="text-warning fw-bold">Warning</h4>
                    <?php else: ?>
                        <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                        <h4 class="text-danger fw-bold">Error</h4>
                    <?php endif; ?>

                    <p class="lead mt-3 mb-4"><?php echo htmlspecialchars($message); ?></p>
                    <a href="../student/dashboard.php" class="btn btn-primary mt-1 px-4">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>