<?php
require_once '../includes/db.php';
// Only allow admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /sports-complex/user/login.php');
    exit;
}
$result = $conn->query("SELECT a.*, p.name FROM audit_log a LEFT JOIN players p ON a.user_id = p.player_id ORDER BY a.timestamp DESC LIMIT 100");
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 90%; margin: 32px auto; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>Audit Log</h2>
    <a href="reports.php">Back to Reports</a>
    <table>
        <tr><th>User</th><th>Action</th><th>Details</th><th>Timestamp</th></tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?=htmlspecialchars($row['name'] ?? 'Unknown')?></td>
            <td><?=htmlspecialchars($row['action'])?></td>
            <td><?=htmlspecialchars($row['details'])?></td>
            <td><?=htmlspecialchars($row['timestamp'])?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
