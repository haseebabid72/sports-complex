<?php
// Equipment module entry point
require_once '../includes/db.php';
include __DIR__ . '/../includes/auth.php';
session_start(); // Ensure session is started before using session variables
if (!is_logged_in()) {
    header('Location: /sports-complex/user/login.php');
    exit;
}

// Only show management UI for admin
$is_admin = (get_user_role() === 'admin');

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

// Equipment management (add, update, delete)
if (isset($_POST['add_equipment'])) {
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $status = $_POST['status'];
    $condition = $_POST['condition'];
    $facility_id = !empty($_POST['facility_id']) ? (int)$_POST['facility_id'] : null;
    $stmt = $conn->prepare("INSERT INTO equipment (name, type, status, `condition`, facility_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssi', $name, $type, $status, $condition, $facility_id);
    $stmt->execute();
    header('Location: index.php'); exit;
}
if (isset($_POST['delete_equipment'])) {
    $id = (int)$_POST['equipment_id'];
    $stmt = $conn->prepare("DELETE FROM equipment WHERE equipment_id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: index.php'); exit;
}

// Equipment check-in/check-out (admin only)
if ($is_admin && isset($_POST['equipment_action']) && isset($_POST['equipment_id'])) {
    $equipment_id = (int)$_POST['equipment_id'];
    $action = $_POST['equipment_action'];
    $handled_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $new_status = ($action === 'check_out') ? 'in use' : 'available';
    $new_condition = $_POST['new_condition'] ?? null;
    if ($action === 'check_out' || $action === 'check_in') {
        // Update equipment status and condition
        $stmt = $conn->prepare("UPDATE equipment SET status=?, `condition`=? WHERE equipment_id=?");
        $stmt->bind_param('ssi', $new_status, $new_condition, $equipment_id);
        $stmt->execute();
        // Log in equipment_history
        $stmt = $conn->prepare("INSERT INTO equipment_history (equipment_id, action, `condition`, handled_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('issi', $equipment_id, $action === 'check_out' ? 'checked out' : 'checked in', $new_condition, $handled_by);
        $stmt->execute();
        header('Location: index.php'); exit;
    }
}

// Fetch facilities for dropdown
$facilitiesList = $conn->query("SELECT facility_id, name FROM facilities");
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
    <?php if ($is_admin): ?>
    <h3>Add Equipment</h3>
    <form method="post" style="margin-bottom:24px;">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" required>
        <label for="type">Type</label>
        <input type="text" name="type" id="type" required>
        <label for="status">Status</label>
        <select name="status" id="status" required>
            <option value="available">Available</option>
            <option value="in use">In Use</option>
            <option value="maintenance">Maintenance</option>
        </select>
        <label for="condition">Condition</label>
        <select name="condition" id="condition" required>
            <option value="good">Good</option>
            <option value="damaged">Damaged</option>
            <option value="needs repair">Needs Repair</option>
        </select>
        <label for="facility_id">Facility</label>
        <select name="facility_id" id="facility_id">
            <option value="">None</option>
            <?php if ($facilitiesList) while ($f = $facilitiesList->fetch_assoc()): ?>
                <option value="<?php echo $f['facility_id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit" name="add_equipment">Add Equipment</button>
    </form>
    <?php endif; ?>
    <h3>Equipment Inventory</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Condition</th>
            <th>Facility</th>
            <?php if ($is_admin): ?><th>Action</th><?php endif; ?>
        </tr>
        <?php
        $equipment->data_seek(0);
        while ($row = $equipment->fetch_assoc()): ?>
        <tr class="<?php echo $row['condition']==='damaged' ? 'damaged' : ''; ?>">
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['type']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
            <td><?php echo htmlspecialchars($row['condition']); ?></td>
            <td><?php echo isset($row['facility_id']) ? (int)$row['facility_id'] : '-'; ?></td>
            <?php if ($is_admin): ?>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="equipment_id" value="<?php echo (int)$row['equipment_id']; ?>">
                    <select name="new_condition" required style="margin-bottom:4px;">
                        <option value="good" <?php if($row['condition']==='good')echo'selected';?>>Good</option>
                        <option value="damaged" <?php if($row['condition']==='damaged')echo'selected';?>>Damaged</option>
                        <option value="needs repair" <?php if($row['condition']==='needs repair')echo'selected';?>>Needs Repair</option>
                    </select><br>
                    <?php if ($row['status'] === 'available'): ?>
                        <button type="submit" name="equipment_action" value="check_out">Check Out</button>
                    <?php else: ?>
                        <button type="submit" name="equipment_action" value="check_in">Check In</button>
                    <?php endif; ?>
                    <button type="submit" name="delete_equipment" onclick="return confirm('Delete this equipment?');">Delete</button>
                </form>
            </td>
            <?php endif; ?>
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
