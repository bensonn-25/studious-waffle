<?php
require_once '../includes/db_connect.php';

// Check if user is logged in and is president
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'president') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch all sessions
$sessionsSql = "SELECT s.*, 
                (SELECT COUNT(*) FROM enrollments WHERE session_id = s.id) as enrolled_count,
                (SELECT COUNT(*) FROM attendance WHERE session_id = s.id) as attended_count
                FROM sessions s ORDER BY session_date DESC, session_time DESC LIMIT 10";
$sessionsResult = $conn->query($sessionsSql);

// Fetch recent announcements
$announcementsSql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
$announcementsResult = $conn->query($announcementsSql);

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">President Dashboard</h2>
        <div>
            <a href="announcements.php" class="btn btn-outline-info me-2"><i class="fas fa-bullhorn me-2"></i>Announcements</a>
            <a href="reports.php" class="btn btn-outline-primary me-2"><i class="fas fa-chart-pie me-2"></i>Reports</a>
            <a href="session_types.php" class="btn btn-outline-secondary me-2"><i class="fas fa-tags me-2"></i>Session Types</a>
            <a href="create_session.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Create New Session</a>
        </div>
    </div>

    <!-- Announcements Section -->
    <?php if ($announcementsResult && $announcementsResult->num_rows > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 border-start border-info border-4 bg-light">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-info mb-3"><i class="fas fa-bullhorn me-2"></i>Recent Announcements Broadcasted</h6>
                    <div class="row g-3">
                        <?php while($ann = $announcementsResult->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="bg-white p-3 rounded shadow-sm h-100">
                                    <div class="fw-bold mb-1"><?php echo htmlspecialchars($ann['title']); ?></div>
                                    <p class="small text-muted mb-0 text-truncate"><?php echo htmlspecialchars($ann['message']); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Recent Sessions</h5>
                </div>
                <div class="card-body">
                    <?php if ($sessionsResult && $sessionsResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Date & Time</th>
                                        <th>Venue</th>
                                        <th>Enrolled</th>
                                        <th>Attended</th>
                                        <th>Attendance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($session = $sessionsResult->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($session['title']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($session['type']); ?></span></td>
                                            <td>
                                                <div><?php echo date('M d, Y', strtotime($session['session_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($session['session_time'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($session['venue']); ?></td>
                                            <td><?php echo $session['enrolled_count']; ?></td>
                                            <td><?php echo $session['attended_count']; ?></td>
                                            <td>
                                                <?php if ($session['status'] === 'ongoing' && !empty($session['attendance_close_time']) && strtotime($session['attendance_close_time']) >= time()): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_session.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-info text-white" title="View Details">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                                <a href="session_attendance.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-success" title="Start Attendance"><i class="fas fa-qrcode me-1"></i>Start Attendance</a>
                                                <a href="upload_material.php?session_id=<?php echo $session['id']; ?>" class="btn btn-sm btn-warning" title="Upload Material"><i class="fas fa-upload"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No sessions have been created yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
