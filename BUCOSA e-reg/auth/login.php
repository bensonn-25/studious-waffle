<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Check status
            if ($user['status'] !== 'active') {
                $error = 'Your account is ' . $user['status'] . '. Please contact administration.';
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];

                // Update last login
                $updateLogin = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = " . $user['id'];
                $conn->query($updateLogin);

                log_activity($conn, $user['id'], $user['role'], "User logged into the system.");

                // Redirect based on role
                if ($user['role'] === 'student') {
                    header("Location: ../student/dashboard.php");
                } else if ($user['role'] === 'president') {
                    header("Location: ../president/dashboard.php");
                } else if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                    header("Location: ../admin/dashboard.php");
                }
                exit();
            }
        } else {
            $error = 'Invalid password.';
        }
    } else {
        $error = 'No account found with that email.';
    }
}
?>

<div class="auth-sidebar shadow-lg">
    <div class="auth-sidebar-content">
        <h1 class="display-5 fw-bold mb-4">BUCOSA<br>e-Reg System</h1>
        <p class="lead mb-0" style="color: rgba(255,255,255,0.85);">
            Centralized platform for training sessions, workshops, and student attendance management.
        </p>
    </div>
</div>

<div class="auth-main">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fas fa-laptop-code text-center d-block mx-auto"></i>
        </div>
        
        <div class="text-center mb-5">
            <h3 class="fw-bold text-dark mb-1">Welcome Back</h3>
            <p class="text-muted">Enter your credentials to access your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-floating-custom mb-3">
                <input type="email" name="email" class="form-control" required placeholder="name@bucosa.com">
            </div>
            <div class="form-floating-custom mb-4">
                <input type="password" name="password" class="form-control" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-auth text-white fw-bold mb-4">
                <i class="fas fa-sign-in-alt me-2"></i> Secure Login
            </button>
        </form>
        
        <div class="text-center">
            <p class="text-muted fw-semibold">New student? <a href="register.php" class="text-primary text-decoration-none border-bottom border-primary border-2 pb-1">Create an Account</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
