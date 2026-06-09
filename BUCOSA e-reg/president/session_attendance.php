<?php
require_once '../includes/db_connect.php';
require_once '../includes/phpqrcode.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['president', 'admin', 'super_admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userType = $_SESSION['user_type'];
$userId = $_SESSION['user_id'];
$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sessionId <= 0) {
    header("Location: dashboard.php");
    exit();
}

$canManageSession = $userType !== 'president';
$sessionSql = $canManageSession
    ? "SELECT * FROM sessions WHERE id = $sessionId"
    : "SELECT * FROM sessions WHERE id = $sessionId AND created_by = $userId";
$sessionResult = $conn->query($sessionSql);

if (!$sessionResult || $sessionResult->num_rows === 0) {
    die("Session not found or unauthorized.");
}

$session = $sessionResult->fetch_assoc();
$error = '';
$windowColumns = get_attendance_window_columns($conn);

function buildSessionUpdateSql($sessionId, $token, $startedAt, $expiresAt, $windowColumns) {
    $updates = [
        "qr_token = '$token'",
        "status = 'ongoing'"
    ];

    if ($windowColumns['started']) {
        $updates[] = $windowColumns['started'] . " = '$startedAt'";
    }

    if ($windowColumns['expires']) {
        $updates[] = $windowColumns['expires'] . " = '$expiresAt'";
    }

    if ($windowColumns['close_time'] && $windowColumns['close_time'] !== $windowColumns['expires']) {
        $updates[] = $windowColumns['close_time'] . " = '$expiresAt'";
    }

    return "UPDATE sessions SET " . implode(', ', $updates) . " WHERE id = $sessionId";
}

function resolveAttendanceExpiry($session, $windowColumns) {
    if (!empty($windowColumns['expires']) && !empty($session[$windowColumns['expires']])) {
        return $session[$windowColumns['expires']];
    }

    return $session['attendance_close_time'] ?? null;
}

if (isset($_GET['stats']) && $_GET['stats'] === '1') {
    $startedAt = !empty($windowColumns['started']) ? ($session[$windowColumns['started']] ?? null) : null;
    $expiresAt = resolveAttendanceExpiry($session, $windowColumns);
    $now = time();
    $expiryTimestamp = $expiresAt ? strtotime($expiresAt) : 0;
    $isActive = $session['status'] === 'ongoing' && $expiryTimestamp >= $now;

    $response = [
        'isActive' => $isActive,
        'attendanceStartedAt' => $startedAt,
        'attendanceExpiresAt' => $expiresAt,
        'remainingSeconds' => $isActive ? max(0, $expiryTimestamp - $now) : 0,
        'enrolledCount' => (int)$conn->query("SELECT COUNT(*) AS total FROM enrollments WHERE session_id = $sessionId")->fetch_assoc()['total'],
        'attendedCount' => (int)$conn->query("SELECT COUNT(*) AS total FROM attendance WHERE session_id = $sessionId")->fetch_assoc()['total'],
    ];
    $response['absentCount'] = max(0, $response['enrolledCount'] - $response['attendedCount']);

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_attendance'])) {
    $attendanceToken = bin2hex(random_bytes(16));
    $startedAt = date('Y-m-d H:i:s');
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $updateSql = buildSessionUpdateSql($sessionId, $attendanceToken, $startedAt, $expiresAt, $windowColumns);

    if ($conn->query($updateSql) === TRUE) {
        $session['qr_token'] = $attendanceToken;
        $session[$windowColumns['started']] = $startedAt;
        if ($windowColumns['expires']) {
            $session[$windowColumns['expires']] = $expiresAt;
        }
        $session['attendance_close_time'] = $expiresAt;
        $session['status'] = 'ongoing';
        header("Location: session_attendance.php?id=" . $sessionId);
        exit();
    } else {
        $error = 'Unable to start attendance at the moment.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_attendance'])) {
    $closedAt = date('Y-m-d H:i:s');
    $updates = ["status = 'completed'"];

    if ($windowColumns['expires']) {
        $updates[] = $windowColumns['expires'] . " = '$closedAt'";
    }

    if ($windowColumns['close_time']) {
        $updates[] = $windowColumns['close_time'] . " = '$closedAt'";
    }

    $closeSql = "UPDATE sessions SET " . implode(', ', $updates) . " WHERE id = $sessionId";

    if ($conn->query($closeSql) === TRUE) {
        $session['status'] = 'completed';
        if ($windowColumns['expires']) {
            $session[$windowColumns['expires']] = $closedAt;
        }
        $session['attendance_close_time'] = $closedAt;
        header("Location: session_attendance.php?id=" . $sessionId);
        exit();
    } else {
        $error = 'Unable to close attendance at the moment.';
    }
}

$attendanceExpiresAt = resolveAttendanceExpiry($session, $windowColumns);
$attendanceStartedAt = $session[$windowColumns['started']] ?? null;
$isActive = $session['status'] === 'ongoing' && !empty($attendanceExpiresAt) && strtotime($attendanceExpiresAt) >= time();

$enrolledCountSql = "SELECT COUNT(*) AS total FROM enrollments WHERE session_id = $sessionId";
$attendedCountSql = "SELECT COUNT(*) AS total FROM attendance WHERE session_id = $sessionId";
$enrolledCount = (int)$conn->query($enrolledCountSql)->fetch_assoc()['total'];
$attendedCount = (int)$conn->query($attendedCountSql)->fetch_assoc()['total'];
$absentCount = max(0, $enrolledCount - $attendedCount);

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$appPath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $appPath;
$attendanceUrl = $baseUrl . '/attendance/scan.php?token=' . urlencode($session['qr_token']);

$qrDir = '../uploads/qrcodes/';
if (!file_exists($qrDir)) {
    mkdir($qrDir, 0777, true);
}
$qrFile = $qrDir . 'session_' . $sessionId . '_attendance.png';
QRcode::png($attendanceUrl, $qrFile, QR_ECLEVEL_L, 10, 2);
$qrWebPath = '../uploads/qrcodes/session_' . $sessionId . '_attendance.png?v=' . filemtime($qrFile);

require_once '../includes/header.php';
?>

<style>
    .attendance-screen {
        max-width: 1180px;
        margin: 0 auto;
    }

    .attendance-panel {
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.14);
        border: 1px solid rgba(255, 106, 0, 0.14);
        background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
        color: #fff;
    }

    .attendance-meta {
        color: rgba(255, 255, 255, 0.72);
    }

    .qr-card {
        border-radius: 24px;
        background: #fff;
        padding: 24px;
    }

    .qr-frame {
        max-width: 420px;
        margin: 0 auto;
    }

    .countdown-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.08);
    }

    .stat-card {
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 18px;
        height: 100%;
    }

    .attendance-expired {
        background: linear-gradient(180deg, #fffdf8 0%, #fff4e6 100%);
        border: 1px solid rgba(255, 106, 0, 0.14);
        color: #1f2937;
    }
</style>

<div class="container mt-4 attendance-screen">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="attendance-panel shadow-sm mb-4">
        <div class="p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="badge bg-success mb-3"><?php echo $isActive ? 'Attendance Active' : 'Attendance Inactive'; ?></div>
                    <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($session['title']); ?></h2>
                    <div class="attendance-meta">
                        <div><?php echo date('F d, Y', strtotime($session['session_date'])); ?> at <?php echo date('h:i A', strtotime($session['session_time'])); ?></div>
                        <div><?php echo htmlspecialchars($session['venue']); ?></div>
                    </div>
                </div>
                <div class="text-md-end">
                    <div class="countdown-pill mb-2">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Closes in</span>
                        <strong id="countdown">--:--:--</strong>
                    </div>
                    <div class="attendance-meta small">Valid until <span id="closeTimeText"><?php echo htmlspecialchars($attendanceExpiresAt ? date('h:i A', strtotime($attendanceExpiresAt)) : '--:--'); ?></span></div>
                </div>
            </div>

            <?php if ($isActive): ?>
                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-7">
                        <div class="qr-card text-center h-100">
                            <div class="mb-3 text-dark fw-bold">Scan this QR code to mark attendance</div>
                            <div class="qr-frame">
                                <img src="<?php echo htmlspecialchars($qrWebPath); ?>" alt="Attendance QR Code" class="img-fluid w-100">
                            </div>
                            <div class="mt-3 text-muted small">Attendance token: <strong><?php echo htmlspecialchars($session['qr_token']); ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="row g-3 h-100">
                            <div class="col-12">
                                <div class="stat-card">
                                    <div class="text-white-50 small text-uppercase fw-semibold">Students Present</div>
                                    <div class="display-5 fw-bold mb-0"><?php echo $attendedCount; ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="stat-card">
                                    <div class="text-white-50 small text-uppercase fw-semibold">Students Enrolled</div>
                                    <div class="display-5 fw-bold mb-0"><?php echo $enrolledCount; ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="stat-card">
                                    <div class="text-white-50 small text-uppercase fw-semibold">Students Absent</div>
                                    <div class="display-5 fw-bold mb-0"><?php echo $absentCount; ?></div>
                                </div>
                            </div>
                            <div class="col-12 mt-auto">
                                <form method="POST" class="d-grid gap-2">
                                    <button type="submit" name="close_attendance" class="btn btn-danger btn-lg fw-bold">
                                        <i class="fas fa-stop-circle me-2"></i>Close Attendance
                                    </button>
                                    <button type="submit" name="start_attendance" class="btn btn-light btn-lg fw-bold">
                                        <i class="fas fa-sync-alt me-2"></i>Generate New QR
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-light btn-lg">Back to Dashboard</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="attendance-expired rounded-4 p-4 p-md-5">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-8">
                            <div class="badge bg-warning text-dark mb-3">Attendance Inactive</div>
                            <h3 class="fw-bold mb-2">Start attendance to display the QR code</h3>
                            <p class="mb-0">This session currently has no active attendance window. Click the button below to generate a live QR code that expires after 30 minutes.</p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <form method="POST">
                                <button type="submit" name="start_attendance" class="btn btn-primary btn-lg fw-bold w-100">
                                    <i class="fas fa-qrcode me-2"></i>Start Attendance
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const closeTime = new Date('<?php echo $attendanceExpiresAt ? date('c', strtotime($attendanceExpiresAt)) : date('c'); ?>');
    const countdownEl = document.getElementById('countdown');
    const statsEndpoint = new URL(window.location.href);
    statsEndpoint.searchParams.set('stats', '1');

    async function refreshAttendanceStats() {
        try {
            const response = await fetch(statsEndpoint.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await response.json();

            if (typeof data.attendedCount === 'number') {
                document.querySelectorAll('.display-5.fw-bold.mb-0')[0].textContent = data.attendedCount;
            }

            if (typeof data.enrolledCount === 'number') {
                document.querySelectorAll('.display-5.fw-bold.mb-0')[1].textContent = data.enrolledCount;
            }

            if (typeof data.absentCount === 'number') {
                document.querySelectorAll('.display-5.fw-bold.mb-0')[2].textContent = data.absentCount;
            }

            if (typeof data.remainingSeconds === 'number') {
                const hours = String(Math.floor(data.remainingSeconds / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((data.remainingSeconds % 3600) / 60)).padStart(2, '0');
                const seconds = String(data.remainingSeconds % 60).padStart(2, '0');
                countdownEl.textContent = `${hours}:${minutes}:${seconds}`;
            }

            if (!data.isActive) {
                window.location.reload();
            }
        } catch (error) {
            // Keep the last known values if the stats refresh fails.
        }
    }

    function updateCountdown() {
        const remaining = closeTime.getTime() - Date.now();

        if (remaining <= 0) {
            countdownEl.textContent = '00:00:00';
            return;
        }

        const totalSeconds = Math.floor(remaining / 1000);
        const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const seconds = String(totalSeconds % 60).padStart(2, '0');
        countdownEl.textContent = `${hours}:${minutes}:${seconds}`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
    setInterval(refreshAttendanceStats, 10000);
</script>

<?php require_once '../includes/footer.php'; ?>