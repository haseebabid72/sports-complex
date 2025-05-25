<?php
// Admin module entry point
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /sports-complex/user/login.php');
    exit;
}
require_once '../includes/db.php';

// Handle add, update, delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add facility
    if (isset($_POST['add_facility'])) {
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $capacity = (int)$_POST['capacity'];
        $availability = $_POST['availability'];
        $maintenance = $_POST['maintenance'];
        $stmt = $conn->prepare("INSERT INTO facilities (name, type, capacity, availability, maintenance) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssiss', $name, $type, $capacity, $availability, $maintenance);
        $stmt->execute();
    }
    // Update facility
    if (isset($_POST['update_facility'])) {
        $id = (int)$_POST['facility_id'];
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $capacity = (int)$_POST['capacity'];
        $availability = $_POST['availability'];
        $maintenance = $_POST['maintenance'];
        $stmt = $conn->prepare("UPDATE facilities SET name=?, type=?, capacity=?, availability=?, maintenance=? WHERE facility_id=?");
        $stmt->bind_param('ssissi', $name, $type, $capacity, $availability, $maintenance, $id);
        $stmt->execute();
    }
    // Delete facility
    if (isset($_POST['delete_facility'])) {
        $id = (int)$_POST['facility_id'];
        $stmt = $conn->prepare("DELETE FROM facilities WHERE facility_id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
    header('Location: index.php');
    exit;
}
// Fetch all facilities
$facilities = $conn->query("SELECT * FROM facilities");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .registration-container { display: flex; align-items: center; flex-direction: column; }
        table { width: 80vw; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
        .actions { display: flex; gap: 8px; justify-content: center; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</h2>
    <h3>Sports Facilities</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Capacity</th>
            <th>Availability</th>
            <th>Maintenance</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $facilities->fetch_assoc()): ?>
        <tr>
            <form method="post">
            <td><input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required></td>
            <td><input type="text" name="type" value="<?php echo htmlspecialchars($row['type']); ?>" required></td>
            <td><input type="number" name="capacity" value="<?php echo (int)$row['capacity']; ?>" min="1" required></td>
            <td>
                <select name="availability">
                    <option value="available" <?php if($row['availability']==='available') echo 'selected'; ?>>Available</option>
                    <option value="unavailable" <?php if($row['availability']==='unavailable') echo 'selected'; ?>>Unavailable</option>
                </select>
            </td>
            <td>
                <select name="maintenance">
                    <option value="none" <?php if($row['maintenance']==='none') echo 'selected'; ?>>None</option>
                    <option value="scheduled" <?php if($row['maintenance']==='scheduled') echo 'selected'; ?>>Scheduled</option>
                    <option value="in_progress" <?php if($row['maintenance']==='in_progress') echo 'selected'; ?>>In Progress</option>
                </select>
            </td>
            <td class="actions">
                <input type="hidden" name="facility_id" value="<?php echo (int)$row['facility_id']; ?>">
                <button type="submit" name="update_facility">Update</button>
                <button type="submit" name="delete_facility" onclick="return confirm('Delete this facility?');">Delete</button>
            </td>
            </form>
        </tr>
        <?php endwhile; ?>
        <tr>
            <form method="post">
            <td><input type="text" name="name" required></td>
            <td><input type="text" name="type" required></td>
            <td><input type="number" name="capacity" min="1" required></td>
            <td>
                <select name="availability">
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </td>
            <td>
                <select name="maintenance">
                    <option value="none">None</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress</option>
                </select>
            </td>
            <td class="actions">
                <button type="submit" name="add_facility">Add</button>
            </td>
            </form>
        </tr>
    </table>
</div>
</body>
</html>
