<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'president') {
    header("Location: ../auth/login.php");
    exit();
}

$sessionTypes = get_active_session_types($conn);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $type = $conn->real_escape_string($_POST['type']);
    $session_date = $conn->real_escape_string($_POST['session_date']);
    $session_time = $conn->real_escape_string($_POST['session_time']);
    $venue = $conn->real_escape_string($_POST['venue']);
    $description = $conn->real_escape_string($_POST['description']);
    $created_by = $_SESSION['user_id'];

    $validType = false;
    foreach ($sessionTypes as $sessionType) {
        if ($sessionType['name'] === $_POST['type']) {
            $validType = true;
            break;
        }
    }

    if (!$validType) {
        $error = "Please select a valid active session type.";
    } else {
        // Generate a unique token for the QR code
        $qr_token = bin2hex(random_bytes(16));
    
        $sql = "INSERT INTO sessions (title, type, session_date, session_time, venue, description, qr_token, created_by) 
                VALUES ('$title', '$type', '$session_date', '$session_time', '$venue', '$description', '$qr_token', $created_by)";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Session created successfully!";
        } else {
            $error = "Error creating session: " . $conn->error;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create New Session</h5>
                    <div class="d-flex gap-2">
                        <a href="session_types.php" class="btn btn-sm btn-outline-light">Session Types</a>
                        <a href="dashboard.php" class="btn btn-sm btn-light text-primary">Back to Dashboard</a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($sessionTypes)): ?>
                        <div class="alert alert-warning">
                            No active session types found. Create or activate one in Session Types before creating a session.
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Session Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Intro to ReactJS">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Session Type</label>
                            <select name="type" class="form-select" required <?php echo empty($sessionTypes) ? 'disabled' : ''; ?>>
                                <option value="">Select a session type</option>
                                <?php foreach ($sessionTypes as $sessionType): ?>
                                    <option value="<?php echo htmlspecialchars($sessionType['name']); ?>"><?php echo htmlspecialchars($sessionType['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date</label>
                                <input type="date" name="session_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Time</label>
                                <input type="time" name="session_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Venue</label>
                            <input type="text" name="venue" class="form-control" required placeholder="e.g. Lab 4 or Zoom Link">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Description / Prerequisites</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Optional details about what students should expect or bring..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" <?php echo empty($sessionTypes) ? 'disabled' : ''; ?>>Create Session</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
