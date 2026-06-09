<?php
require_once '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

if (!isset($_GET['session_id'])) {
    header("Location: dashboard.php");
    exit();
}

$session_id = (int)$_GET['session_id'];

// Check if enrolled
$enrollCheckSql = "SELECT id FROM enrollments WHERE student_id = $student_id AND session_id = $session_id";
if ($conn->query($enrollCheckSql)->num_rows == 0) {
    die("You must enroll in this session to view its materials.");
}

// Fetch session info
$sessionSql = "SELECT title FROM sessions WHERE id = $session_id";
$sessionResult = $conn->query($sessionSql);
$session = $sessionResult->fetch_assoc();

// Fetch materials
$materialsSql = "SELECT * FROM materials WHERE session_id = $session_id ORDER BY created_at DESC";
$materialsResult = $conn->query($materialsSql);

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Materials: <?php echo htmlspecialchars($session['title']); ?></h2>
        <a href="dashboard.php" class="btn btn-sm btn-light text-primary">Back to Dashboard</a>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <?php if ($materialsResult && $materialsResult->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($material = $materialsResult->fetch_assoc()): ?>
                                <div class="list-group-item p-4 d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="bg-light p-3 rounded me-3 text-primary">
                                            <i class="fas fa-file-alt fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($material['title']); ?></h5>
                                            <small class="text-muted">Uploaded on <?php echo date('M d, Y', strtotime($material['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <a href="/BUCOSA e-reg/<?php echo $material['file_path']; ?>" target="_blank" class="btn btn-primary px-4"><i class="fas fa-download me-2"></i>Download</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-4x mb-3 text-light"></i>
                            <h4>No Materials Yet</h4>
                            <p>The trainer has not uploaded any resources for this session.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
