<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
// Fetch user bookings with facility and equipment info
$sql = "SELECT b.booking_time, b.duration_minutes, f.name AS facility, f.type AS facility_type, e.name AS equipment
        FROM bookings b
        JOIN facilities f ON b.facility_id = f.facility_id
        LEFT JOIN equipment e ON b.equipment_id = e.equipment_id
        WHERE b.player_id = ?
        ORDER BY b.booking_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Booking History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>My Booking History</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Duration (min)</th>
            <th>Facility</th>
            <th>Facility Type</th>
            <th>Equipment Used</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()):
            $dt = new DateTime($row['booking_time']); ?>
        <tr>
            <td><?php echo $dt->format('Y-m-d'); ?></td>
            <td><?php echo $dt->format('H:i'); ?></td>
            <td><?php echo (int)$row['duration_minutes']; ?></td>
            <td><?php echo htmlspecialchars($row['facility']); ?></td>
            <td><?php echo htmlspecialchars($row['facility_type']); ?></td>
            <td><?php echo $row['equipment'] ? htmlspecialchars($row['equipment']) : '-'; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="member_dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>