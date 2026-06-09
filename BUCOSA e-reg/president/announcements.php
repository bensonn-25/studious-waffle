<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'president') {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['message']);
    $type = $conn->real_escape_string($_POST['announcement_type']);
    $user_id = $_SESSION['user_id'];
    
    $sql = "INSERT INTO announcements (title, message, announcement_type, created_by) VALUES ('$title', '$content', '$type', $user_id)";
    if ($conn->query($sql) === TRUE) {
        log_activity($conn, $user_id, 'president', "Posted a new announcement: $title");
        $message = '<div class="alert alert-success py-2">Announcement posted successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger py-2">Error posting announcement: ' . $conn->error . '</div>';
    }
}

// Fetch past announcements
$announcementsSql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20";
$announcementsResult = $conn->query($announcementsSql);

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h2 class="fw-bold mb-0">System Announcements</h2>
        <p class="text-muted mb-0">Broadcast messages to all students</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-bullhorn me-2 text-primary"></i>New Broadcast</h5>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Upcoming Hackathon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Announcement Type</label>
                        <select name="announcement_type" class="form-select" required>
                            <option value="general">General Information</option>
                            <option value="event">Event Alert</option>
                            <option value="urgent">Urgent Notice</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-muted small">Message Content</label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Type your message here..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fas fa-paper-plane me-2"></i>Publish Announcement</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Past Broadcasts</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if ($announcementsResult && $announcementsResult->num_rows > 0): ?>
                        <?php while ($ann = $announcementsResult->fetch_assoc()): ?>
                            <?php
                                $badgeClass = 'bg-secondary';
                                if ($ann['announcement_type'] == 'event') $badgeClass = 'bg-info';
                                if ($ann['announcement_type'] == 'urgent') $badgeClass = 'bg-danger';
                            ?>
                            <div class="list-group-item p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($ann['title']); ?></h5>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo htmlspecialchars($ann['announcement_type']); ?></span>
                                </div>
                                <p class="mb-2 text-muted"><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
                                <small class="text-muted"><i class="far fa-clock me-1"></i> Posted on <?php echo date('M d, Y h:i A', strtotime($ann['created_at'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="far fa-comment-dots fa-3x mb-3 opacity-50"></i>
                            <p>No announcements have been posted yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
