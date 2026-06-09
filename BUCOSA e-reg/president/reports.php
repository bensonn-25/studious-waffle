<?php
require_once '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'president') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch all students count
$studentsCountSql = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$studentsCount = $conn->query($studentsCountSql)->fetch_assoc()['total'];

// Fetch total sessions count
$sessionsCountSql = "SELECT COUNT(*) as total FROM sessions";
$sessionsCount = $conn->query($sessionsCountSql)->fetch_assoc()['total'];

// Fetch overall attendance stats
$attendanceStatsSql = "SELECT s.title, s.session_date,
                      (SELECT COUNT(*) FROM enrollments e WHERE e.session_id = s.id) as enrolled,
                      (SELECT COUNT(*) FROM attendance a WHERE a.session_id = s.id) as attended
                      FROM sessions s ORDER BY s.session_date DESC LIMIT 10";
$attendanceStats = $conn->query($attendanceStatsSql);

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reports & Analytics</h2>
        <a href="dashboard.php" class="btn btn-sm btn-light text-primary">Back to Dashboard</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-primary text-white text-center p-4">
                <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                <h3 class="display-4 fw-bold"><?php echo $studentsCount; ?></h3>
                <p class="mb-0 text-uppercase tracking-wide">Total Registered Students</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-success text-white text-center p-4">
                <i class="fas fa-calendar-check fa-3x mb-3 opacity-50"></i>
                <h3 class="display-4 fw-bold"><?php echo $sessionsCount; ?></h3>
                <p class="mb-0 text-uppercase tracking-wide">Total Training Sessions</p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Session Participation Analytics</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Session Title</th>
                            <th>Date</th>
                            <th>Enrolled</th>
                            <th>Attended</th>
                            <th class="pe-4 text-end">Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendanceStats && $attendanceStats->num_rows > 0): ?>
                            <?php while ($stat = $attendanceStats->fetch_assoc()): ?>
                                <?php 
                                    $rate = $stat['enrolled'] > 0 ? round(($stat['attended'] / $stat['enrolled']) * 100) : 0; 
                                    $barClass = $rate >= 75 ? 'bg-success' : ($rate >= 50 ? 'bg-warning' : 'bg-danger');
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($stat['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($stat['session_date'])); ?></td>
                                    <td><?php echo $stat['enrolled']; ?></td>
                                    <td><?php echo $stat['attended']; ?></td>
                                    <td class="pe-4 text-end" style="min-width: 200px;">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <div class="progress flex-grow-1 me-2" style="height: 8px; max-width: 100px;">
                                                <div class="progress-bar <?php echo $barClass; ?>" role="progressbar" style="width: <?php echo $rate; ?>%" aria-valuenow="<?php echo $rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="fw-bold"><?php echo $rate; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No attendance data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
