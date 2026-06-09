<?php
require_once '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch upcoming sessions that the student has NOT enrolled in
$upcomingSql = "SELECT * FROM sessions 
                WHERE id NOT IN (SELECT session_id FROM enrollments WHERE student_id = $student_id)
                AND session_date >= CURDATE()
                ORDER BY session_date ASC, session_time ASC LIMIT 5";
$upcomingResult = $conn->query($upcomingSql);

// Fetch enrolled sessions
$enrolledSql = "SELECT s.*, a.scanned_at 
                FROM sessions s 
                JOIN enrollments e ON s.id = e.session_id 
                LEFT JOIN attendance a ON s.id = a.session_id AND a.student_id = $student_id
                WHERE e.student_id = $student_id 
                ORDER BY s.session_date DESC";
$enrolledResult = $conn->query($enrolledSql);

// Fetch recent announcements
$announcementsSql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
$announcementsResult = $conn->query($announcementsSql);

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="mb-4">
        <h2 class="fw-bold text-dark">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <p class="text-muted">Manage your BUCOSA training sessions and track your attendance.</p>
    </div>

    <!-- Announcements Section -->
    <?php if ($announcementsResult && $announcementsResult->num_rows > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 border-start border-primary border-4 bg-light">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-bullhorn me-2"></i>Recent Announcements</h6>
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
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Enrolled Sessions</h5>
                </div>
                <div class="card-body">
                    <?php if ($enrolledResult && $enrolledResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Session</th>
                                        <th>Date & Time</th>
                                        <th>Venue</th>
                                        <th>Attendance</th>
                                        <th>Materials</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($session = $enrolledResult->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($session['title']); ?></td>
                                            <td>
                                                <div><?php echo date('M d, Y', strtotime($session['session_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($session['session_time'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($session['venue']); ?></td>
                                            <td>
                                                <?php if ($session['scanned_at']): ?>
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Present</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_materials.php?session_id=<?php echo $session['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">You have not enrolled in any sessions yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">Upcoming Sessions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if ($upcomingResult && $upcomingResult->num_rows > 0): ?>
                            <?php while ($session = $upcomingResult->fetch_assoc()): ?>
                                <div class="list-group-item p-3">
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($session['title']); ?></h6>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i><?php echo date('M d', strtotime($session['session_date'])); ?></small>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($session['type']); ?></span>
                                    </div>
                                    <form action="enroll.php" method="POST">
                                        <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Enroll Now</button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <p class="text-muted mb-0">No upcoming sessions available for enrollment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
