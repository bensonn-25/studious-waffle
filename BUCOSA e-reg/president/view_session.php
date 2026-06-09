<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['president', 'admin', 'super_admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sessionId <= 0) {
    header("Location: dashboard.php");
    exit();
}

$userType = $_SESSION['user_type'];
$userId = (int)$_SESSION['user_id'];
$canManageSession = $userType !== 'president';

$sessionSql = $canManageSession
    ? "SELECT * FROM sessions WHERE id = $sessionId"
    : "SELECT * FROM sessions WHERE id = $sessionId AND created_by = $userId";
$sessionResult = $conn->query($sessionSql);

if (!$sessionResult || $sessionResult->num_rows === 0) {
    die("Session not found or unauthorized.");
}

$session = $sessionResult->fetch_assoc();

$enrolledCount = (int)$conn->query("SELECT COUNT(*) AS total FROM enrollments WHERE session_id = $sessionId")->fetch_assoc()['total'];
$attendedCount = (int)$conn->query("SELECT COUNT(*) AS total FROM attendance WHERE session_id = $sessionId")->fetch_assoc()['total'];
$absentCount = max(0, $enrolledCount - $attendedCount);
$isAttendanceActive = $session['status'] === 'ongoing' && !empty($session['attendance_close_time']) && strtotime($session['attendance_close_time']) >= time();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($session['title']); ?></h2>
            <p class="text-muted mb-0">Session details and attendance summary</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            <a href="session_attendance.php?id=<?php echo $session['id']; ?>" class="btn btn-primary">Attendance Screen</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Session Overview</h5>
                    <?php if ($isAttendanceActive): ?>
                        <span class="badge bg-success">Attendance Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Attendance Inactive</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold">Session Type</div>
                                <div class="fw-bold fs-5"><?php echo htmlspecialchars($session['type']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold">Date & Time</div>
                                <div class="fw-bold fs-5"><?php echo date('F d, Y', strtotime($session['session_date'])); ?></div>
                                <div class="text-muted"><?php echo date('h:i A', strtotime($session['session_time'])); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold">Venue</div>
                                <div class="fw-bold fs-5"><?php echo htmlspecialchars($session['venue']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold">Capacity</div>
                                <div class="fw-bold fs-5"><?php echo (int)$session['max_participants']; ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded-3 p-3">
                                <div class="text-muted small text-uppercase fw-semibold">Description</div>
                                <div><?php echo nl2br(htmlspecialchars($session['description'] ?? 'No description provided.')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Attendance Summary</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 text-center">
                                <div class="text-muted small text-uppercase fw-semibold">Enrolled</div>
                                <div class="display-6 fw-bold"><?php echo $enrolledCount; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 text-center">
                                <div class="text-muted small text-uppercase fw-semibold">Present</div>
                                <div class="display-6 fw-bold text-success"><?php echo $attendedCount; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 text-center">
                                <div class="text-muted small text-uppercase fw-semibold">Absent</div>
                                <div class="display-6 fw-bold text-danger"><?php echo $absentCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body p-4 d-grid gap-2">
                    <a href="session_attendance.php?id=<?php echo $session['id']; ?>" class="btn btn-primary">Open Attendance Screen</a>
                    <a href="upload_material.php?session_id=<?php echo $session['id']; ?>" class="btn btn-outline-warning">Upload Material</a>
                    <a href="reports.php" class="btn btn-outline-info">View Reports</a>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Session Status</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-2"><strong>QR Token:</strong> <span class="text-muted small"><?php echo htmlspecialchars($session['qr_token']); ?></span></div>
                    <div class="mb-2"><strong>Created At:</strong> <?php echo htmlspecialchars($session['created_at']); ?></div>
                    <div class="mb-2"><strong>Updated At:</strong> <?php echo htmlspecialchars($session['updated_at']); ?></div>
                    <div><strong>Status:</strong> <?php echo htmlspecialchars($session['status']); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>