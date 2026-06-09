<?php
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = $conn->real_escape_string($_POST['full_name']);
    $regNumber = $conn->real_escape_string($_POST['reg_number']);
    $course = $conn->real_escape_string($_POST['course']);
    $yearOfStudy = $conn->real_escape_string($_POST['year_of_study']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Basic validation to check if email or reg number exists in users table
    $checkSql = "SELECT id FROM users WHERE email='$email' OR reg_number='$regNumber'";
    $result = $conn->query($checkSql);

    if ($result->num_rows > 0) {
        $error = 'Email or Registration Number already exists!';
    } else {
        $sql = "INSERT INTO users (full_name, email, phone, password, role, reg_number, course, year_of_study) 
                VALUES ('$fullName', '$email', '$phone', '$password', 'student', '$regNumber', '$course', '$yearOfStudy')";
        
        if ($conn->query($sql) === TRUE) {
            $success = 'Registration successful! You can now access the system.';
        } else {
            $error = 'Error: ' . $conn->error;
        }
    }
}
?>

<div class="auth-sidebar shadow-lg">
    <div class="auth-sidebar-content">
        <h1 class="display-5 fw-bold mb-4">Join BUCOSA</h1>
        <p class="lead mb-0" style="color: rgba(255,255,255,0.85);">
            Register your account to access technology training sessions, workshops, and exclusive learning materials.
        </p>
    </div>
</div>

<div class="auth-main">
    <div class="auth-card" style="max-width: 550px;">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-dark mb-1">Student Enrollment</h3>
            <p class="text-muted">Create your BUCOSA system account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-3 border-0 shadow-sm text-center">
                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i><br>
                <?php echo $success; ?>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary px-5 btn-auth fw-bold d-inline-flex align-items-center justify-content-center"><i class="fas fa-sign-in-alt me-2"></i> Proceed to Login</a>
                </div>
            </div>
        <?php else: ?>
            <form action="" method="POST">
                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <input type="text" name="full_name" class="form-control" required placeholder="Full Legal Name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <input type="text" name="reg_number" class="form-control" required placeholder="Reg Number (e.g. 19/BSU/BSC/001)">
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <input type="text" name="course" class="form-control" required placeholder="Course (e.g. Computer Science)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating-custom" style="position: relative;">
                            <select name="year_of_study" class="form-control" required style="height: 60px; border-radius: 12px; border: 1px solid var(--border-color); padding: 10px 20px; background-color: #f8fafc; font-size: 1.05rem; appearance: none;">
                                <option value="" disabled selected>Year of Study</option>
                                <option value="Year 1">Year 1</option>
                                <option value="Year 2">Year 2</option>
                                <option value="Year 3">Year 3</option>
                                <option value="Year 4">Year 4</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <input type="text" name="phone" class="form-control" required placeholder="Phone Number">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating-custom">
                            <input type="email" name="email" class="form-control" required placeholder="Email Address">
                        </div>
                    </div>
                </div>
                <div class="form-floating-custom mb-4">
                    <input type="password" name="password" class="form-control" required minlength="6" placeholder="Secure Password">
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-auth text-white fw-bold mb-4">
                    <i class="fas fa-user-plus me-2"></i> Submit Registration
                </button>
            </form>
            <div class="text-center">
                <p class="text-muted fw-semibold">Already registered? <a href="login.php" class="text-primary text-decoration-none border-bottom border-primary border-2 pb-1">Login to System</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
