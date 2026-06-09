<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['president', 'admin', 'super_admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$isPresident = $_SESSION['user_type'] === 'president';
$userId = (int)$_SESSION['user_id'];
$editTypeId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

ensure_session_types_table($conn);
seed_default_session_types($conn);

$error = '';
$success = '';

if ($isPresident && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $typeId = isset($_POST['type_id']) ? (int)$_POST['type_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($action === 'add' && $name !== '') {
        $stmt = $conn->prepare("INSERT INTO session_types (name, description, is_active, created_by, updated_by) VALUES (?, ?, 1, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssii', $name, $description, $userId, $userId);
            if ($stmt->execute()) {
                $success = 'Session type added successfully.';
            } else {
                $error = 'Unable to add session type.';
            }
            $stmt->close();
        }
    } elseif (in_array($action, ['update', 'toggle', 'delete'], true) && $typeId > 0) {
        if ($action === 'update' && $name !== '') {
            $stmt = $conn->prepare("UPDATE session_types SET name = ?, description = ?, updated_by = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('ssii', $name, $description, $userId, $typeId);
                if ($stmt->execute()) {
                    $success = 'Session type updated successfully.';
                } else {
                    $error = 'Unable to update session type.';
                }
                $stmt->close();
            }
        } elseif ($action === 'toggle') {
            $stmt = $conn->prepare("UPDATE session_types SET is_active = 1 - is_active, updated_by = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $typeId);
                if ($stmt->execute()) {
                    $success = 'Session type status updated successfully.';
                } else {
                    $error = 'Unable to change session type status.';
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM session_types WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $typeId);
                if ($stmt->execute()) {
                    $success = 'Session type deleted successfully.';
                } else {
                    $error = 'Unable to delete session type.';
                }
                $stmt->close();
            }
        }
    }
}

$sessionTypes = get_all_session_types($conn);
$editingType = null;

if ($isPresident && $editTypeId > 0) {
    foreach ($sessionTypes as $type) {
        if ((int)$type['id'] === $editTypeId) {
            $editingType = $type;
            break;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Session Types</h2>
            <p class="text-muted mb-0">Manage the reusable categories used when creating sessions.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($isPresident && $editingType): ?>
        <div class="card shadow-sm mb-4 border-warning">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Edit Session Type</h5>
                    <a href="session_types.php" class="btn btn-sm btn-outline-secondary">Cancel</a>
                </div>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="type_id" value="<?php echo (int)$editingType['id']; ?>">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($editingType['name']); ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($editingType['description'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-warning w-100 fw-bold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isPresident): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Add New Session Type</h5>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Mobile Development">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Optional description">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Add Type</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Available Session Types</h5>
            <span class="badge bg-secondary"><?php echo count($sessionTypes); ?> total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sessionTypes)): ?>
                            <?php foreach ($sessionTypes as $type): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($type['name']); ?></td>
                                    <td><?php echo htmlspecialchars($type['description'] ?? ''); ?></td>
                                    <td>
                                        <?php if ((int)$type['is_active'] === 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($type['updated_at']); ?></td>
                                    <td class="pe-4">
                                        <?php if ($isPresident): ?>
                                            <a href="session_types.php?edit=<?php echo (int)$type['id']; ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="type_id" value="<?php echo (int)$type['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Toggle</button>
                                            </form>
                                            <form method="POST" class="d-inline ms-1" onsubmit="return confirm('Delete this session type?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="type_id" value="<?php echo (int)$type['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">View only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No session types found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>