<?php
require_once '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'president') {
    header("Location: ../auth/login.php");
    exit();
}

$president_id = $_SESSION['user_id'];

if (!isset($_GET['session_id'])) {
    header("Location: dashboard.php");
    exit();
}

$session_id = (int)$_GET['session_id'];

// Check if session belongs to president
$sql = "SELECT title FROM sessions WHERE id = $session_id AND created_by = $president_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Session not found or unauthorized.");
}
$session = $result->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['material_file'])) {
    $title = $conn->real_escape_string($_POST['title']);
    
    $target_dir = "../uploads/materials/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Create safe filename
    $file_extension = pathinfo($_FILES["material_file"]["name"], PATHINFO_EXTENSION);
    $safe_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES["material_file"]["name"]);
    $target_file = $target_dir . $safe_filename;
    
    // Check file size (10MB limit)
    if ($_FILES["material_file"]["size"] > 10000000) {
        $error = "Sorry, your file is too large. Maximum size is 10MB.";
    } else {
        if (move_uploaded_file($_FILES["material_file"]["tmp_name"], $target_file)) {
            // Save relative path to database
            $db_path = "uploads/materials/" . $safe_filename;
            
            $insertSql = "INSERT INTO materials (session_id, title, file_path, uploaded_by) 
                          VALUES ($session_id, '$title', '$db_path', $president_id)";
            
            if ($conn->query($insertSql) === TRUE) {
                $success = "The file has been uploaded successfully.";
            } else {
                $error = "Database error: " . $conn->error;
            }
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch existing materials
$materialsSql = "SELECT * FROM materials WHERE session_id = $session_id ORDER BY created_at DESC";
$materialsResult = $conn->query($materialsSql);

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Upload Materials</h2>
        <a href="dashboard.php" class="btn btn-sm btn-light text-primary">Back to Dashboard</a>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">New Material for: <?php echo htmlspecialchars($session['title']); ?></h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Material Title / Description</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Slide Deck - Intro to React">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select File (PDF, PPT, DOC, ZIP)</label>
                            <input type="file" name="material_file" class="form-control" required>
                            <div class="form-text">Max file size: 10MB</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Upload File</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Uploaded Materials</h5>
                </div>
                <div class="card-body">
                    <?php if ($materialsResult && $materialsResult->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($material = $materialsResult->fetch_assoc()): ?>
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($material['title']); ?></h6>
                                        <small class="text-muted">Uploaded on <?php echo date('M d, Y', strtotime($material['created_at'])); ?></small>
                                    </div>
                                    <a href="/BUCOSA e-reg/<?php echo $material['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i>Download</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3 text-light"></i>
                            <p>No materials uploaded for this session yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
