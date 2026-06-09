<?php
require_once '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch stats
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$activeSessions = $conn->query("SELECT COUNT(*) as total FROM sessions WHERE status IN ('upcoming', 'ongoing')")->fetch_assoc()['total'];
$totalLogs = $conn->query("SELECT COUNT(*) as total FROM system_logs")->fetch_assoc()['total'];

// Fetch recent logs
$logsResult = $conn->query("SELECT l.*, u.full_name FROM system_logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 15");

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h2 class="fw-bold mb-0">System Administration</h2>
        <p class="text-muted mb-0">Overview and System Monitoring</p>
    </div>
    <div>
        <a href="manage_users.php" class="btn btn-primary"><i class="fas fa-users-cog me-2"></i>Manage Users</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid var(--primary-color) !important;">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-users fa-2x" style="color: var(--primary-color);"></i>
                </div>
                <h2 class="fw-bold display-5"><?php echo $totalUsers; ?></h2>
                <p class="text-muted text-uppercase fw-semibold mb-0">Total Accounts</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #198754 !important;">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-calendar-alt fa-2x text-success"></i>
                </div>
                <h2 class="fw-bold display-5"><?php echo $activeSessions; ?></h2>
                <p class="text-muted text-uppercase fw-semibold mb-0">Active Sessions</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #6c757d !important;">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-clipboard-list fa-2x text-secondary"></i>
                </div>
                <h2 class="fw-bold display-5"><?php echo $totalLogs; ?></h2>
                <p class="text-muted text-uppercase fw-semibold mb-0">System Events</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Recent System Activity</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Activity</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logsResult && $logsResult->num_rows > 0): ?>
                        <?php while ($log = $logsResult->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($log['full_name']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['user_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['activity']); ?></td>
                                <td class="font-monospace small"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No system activity logged yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
