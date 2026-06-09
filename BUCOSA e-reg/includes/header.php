<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUCOSA e-Reg System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/BUCOSA e-reg/assets/css/style.css">
</head>
<body>

<?php if ($isLoggedIn): ?>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-laptop-code me-2"></i>BUCOSA e-Reg</h3>
            </div>

            <ul class="list-unstyled components">
                <?php if ($userType === 'president'): ?>
                    <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                        <a href="/BUCOSA e-reg/president/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'create_session.php') !== false ? 'active' : ''; ?>">
                        <a href="/BUCOSA e-reg/president/create_session.php"><i class="fas fa-plus-circle"></i> Create Session</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'reports.php') !== false ? 'active' : ''; ?>">
                        <a href="/BUCOSA e-reg/president/reports.php"><i class="fas fa-chart-pie"></i> Reports</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'session_types.php') !== false ? 'active' : ''; ?>">
                        <a href="/BUCOSA e-reg/president/session_types.php"><i class="fas fa-tags"></i> Session Types</a>
                    </li>
                <?php elseif ($userType === 'student'): ?>
                    <li class="<?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                        <a href="/BUCOSA e-reg/student/dashboard.php"><i class="fas fa-home"></i> My Dashboard</a>
                    </li>
                <?php elseif ($userType === 'admin'): ?>
                    <li>
                        <a href="#"><i class="fas fa-home"></i> Admin Panel</a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="/BUCOSA e-reg/auth/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <button type="button" id="sidebarCollapse" class="navbar-btn">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="user-profile-menu">
                    <span class="d-none d-md-inline fw-bold"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="user-role-badge"><?php echo htmlspecialchars($userType); ?></span>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="main-content-area">
<?php else: ?>
    <!-- Non-logged in users (Auth Pages) -->
    <div class="auth-wrapper">
<?php endif; ?>
