<?php
// Simple audit log utility for admin and critical user actions
require_once __DIR__ . '/db.php';
function log_action($user_id, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iss', $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
