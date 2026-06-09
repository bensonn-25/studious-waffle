<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_id = (int)$_POST['user_id'];
    
    // Prevent self-modification
    if ($target_id === $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger py-2">You cannot modify your own account from this view.</div>';
    } else {
        if ($_POST['action'] === 'change_status') {
            $new_status = $conn->real_escape_string($_POST['new_status']);
            $conn->query("UPDATE users SET status = '$new_status' WHERE id = $target_id");
            log_activity($conn, $_SESSION['user_id'], $_SESSION['user_type'], "Changed user ID $target_id status to $new_status");
            $message = '<div class="alert alert-success py-2">User status updated successfully.</div>';
        } elseif ($_POST['action'] === 'change_role') {
            $new_role = $conn->real_escape_string($_POST['new_role']);
            $conn->query("UPDATE users SET role = '$new_role' WHERE id = $target_id");
            log_activity($conn, $_SESSION['user_id'], $_SESSION['user_type'], "Changed user ID $target_id role to $new_role");
            $message = '<div class="alert alert-success py-2">User role updated successfully.</div>';
        }
    }
}

// Fetch all users
$usersSql = "SELECT id, full_name, email, role, status, created_at, last_login FROM users ORDER BY created_at DESC";
$usersResult = $conn->query($usersSql);

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h2 class="fw-bold mb-0">User Management</h2>
        <p class="text-muted mb-0">Manage roles and account statuses</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
</div>

<?php echo $message; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usersResult && $usersResult->num_rows > 0): ?>
                        <?php while ($u = $usersResult->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($u['email']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-uppercase"><?php echo htmlspecialchars($u['role']); ?></span>
                                </td>
                                <td>
                                    <?php
                                        $statusClass = 'bg-success';
                                        if ($u['status'] == 'inactive') $statusClass = 'bg-secondary';
                                        if ($u['status'] == 'suspended') $statusClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?> text-uppercase"><?php echo htmlspecialchars($u['status']); ?></span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo $u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never'; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <!-- Role Change -->
                                    <form action="" method="POST" class="d-inline-block me-1">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="new_role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Role</option>
                                            <option value="student">Student</option>
                                            <option value="president">President</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </form>
                                    
                                    <!-- Status Change -->
                                    <form action="" method="POST" class="d-inline-block">
                                        <input type="hidden" name="action" value="change_status">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="new_status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="suspended">Suspended</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
