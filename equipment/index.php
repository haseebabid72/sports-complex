<?php
// Equipment module entry point
require_once '../includes/db.php';

// Fetch equipment
$equipment = $conn->query("SELECT * FROM equipment");

// Fetch check-in/out history
$history = $conn->query("SELECT e.name, h.action, h.timestamp, h.condition, h.handled_by FROM equipment_history h JOIN equipment e ON h.equipment_id = e.equipment_id ORDER BY h.timestamp DESC LIMIT 50");

// Inventory alert
$low_inventory = $conn->query("SELECT name FROM equipment WHERE status != 'available' OR `condition` = 'damaged'");
$alerts = [];
while ($row = $low_inventory->fetch_assoc()) {
    $alerts[] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Tracker</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
        .damaged { background: #fbe7e7; color: #a00; }
        .alert { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>Sports Equipment Tracker</h2>
    <?php if ($alerts): ?>
        <div class="alert">
            <strong>Alert:</strong> Attention required for: <?php echo htmlspecialchars(implode(', ', $alerts)); ?>
        </div>
    <?php endif; ?>
    <h3>Equipment Inventory</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Condition</th>
        </tr>
        <?php while ($row = $equipment->fetch_assoc()): ?>
        <tr class="<?php echo $row['condition']==='damaged' ? 'damaged' : ''; ?>">
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['type']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
            <td><?php echo htmlspecialchars($row['condition']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <h3>Check-in / Check-out History</h3>
    <table>
        <tr>
            <th>Equipment</th>
            <th>Action</th>
            <th>Condition</th>
            <th>Handled By</th>
            <th>Timestamp</th>
        </tr>
        <?php while ($h = $history->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($h['name']); ?></td>
            <td><?php echo htmlspecialchars($h['action']); ?></td>
            <td><?php echo htmlspecialchars($h['condition']); ?></td>
            <td><?php echo htmlspecialchars($h['handled_by']); ?></td>
            <td><?php echo htmlspecialchars($h['timestamp']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
