<?php
/**
 * Logs system activity to the database.
 *
 * @param mysqli $conn Database connection
 * @param int $user_id ID of the user performing the action
 * @param string $user_type Role of the user
 * @param string $activity Description of the action
 */
function log_activity($conn, $user_id, $user_type, $activity) {
    $allowedTypes = ['student', 'president', 'admin', 'super_admin'];
    $normalizedType = in_array($user_type, $allowedTypes, true) ? $user_type : 'admin';

    // system_logs currently stores admin-level users under a single audit type.
    if ($normalizedType === 'super_admin') {
        $normalizedType = 'admin';
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, user_type, activity, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $normalizedType, $activity, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}

function table_column_exists($conn, $tableName, $columnName) {
    $safeTable = $conn->real_escape_string($tableName);
    $safeColumn = $conn->real_escape_string($columnName);
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$safeTable' AND COLUMN_NAME = '$safeColumn' LIMIT 1";
    $result = $conn->query($sql);

    return $result && $result->num_rows > 0;
}

function get_attendance_window_columns($conn) {
    return [
        'started' => table_column_exists($conn, 'sessions', 'attendance_started_at') ? 'attendance_started_at' : null,
        'expires' => table_column_exists($conn, 'sessions', 'attendance_expires_at') ? 'attendance_expires_at' : null,
        'close_time' => table_column_exists($conn, 'sessions', 'attendance_close_time') ? 'attendance_close_time' : null,
    ];
}

function ensure_session_types_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS session_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE,
        description VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by INT DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    )";

    return $conn->query($sql);
}

function seed_default_session_types($conn) {
    $defaults = [
        ['Web Development', 'Frontend and backend development sessions'],
        ['Cybersecurity', 'Security awareness, defense, and ethical hacking'],
        ['Networking', 'Computer networking and infrastructure'],
        ['AI & Machine Learning', 'Artificial intelligence and machine learning'],
        ['Programming Bootcamp', 'Intensive programming training sessions'],
        ['Workshop', 'General workshop or special event'],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO session_types (name, description, is_active) VALUES (?, ?, 1)");
    if (!$stmt) {
        return false;
    }

    foreach ($defaults as $defaultType) {
        $stmt->bind_param('ss', $defaultType[0], $defaultType[1]);
        $stmt->execute();
    }

    $stmt->close();
    return true;
}

function get_active_session_types($conn) {
    ensure_session_types_table($conn);
    seed_default_session_types($conn);

    $types = [];
    $result = $conn->query("SELECT id, name, description FROM session_types WHERE is_active = 1 ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
    }

    return $types;
}

function get_all_session_types($conn) {
    ensure_session_types_table($conn);
    seed_default_session_types($conn);

    $types = [];
    $result = $conn->query("SELECT * FROM session_types ORDER BY is_active DESC, name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
    }

    return $types;
}
?>
