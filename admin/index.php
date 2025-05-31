<?php
// Admin module entry point
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /sports-complex/user/login.php');
    exit;
}
require_once '../includes/db.php';
require_once '../includes/audit.php';

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
        log_action($_SESSION['user_id'], 'add_facility', $name . ' (' . $type . ')');
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
        log_action($_SESSION['user_id'], 'update_facility', $name . ' (' . $type . ')');
    }
    // Delete facility
    if (isset($_POST['delete_facility'])) {
        $id = (int)$_POST['facility_id'];
        $stmt = $conn->prepare("DELETE FROM facilities WHERE facility_id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        log_action($_SESSION['user_id'], 'delete_facility', $id);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .registration-container {
            display: flex;
            align-items: flex-start;
            flex-direction: column;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .hero-header {
            background: linear-gradient(90deg, #4e54c8 0%, #8f94fb 100%);
            color: #fff;
            padding: 2.5rem 1rem 2rem 1rem;
            border-radius: 1.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 8px 32px rgba(78,84,200,0.18);
            position: relative;
            overflow: hidden;
            min-width: 0;
        }
        .hero-header i {
            position: absolute;
            opacity: 0.13;
            font-size: 4rem;
            right: 2rem;
            bottom: 1rem;
        }
        .admin-table-section {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            margin-bottom: 2.5rem;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            width: 100%;
        }
        table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            margin-top: 24px;
        }
        th, td {
            border: 1px solid #e3e6f0;
            padding: 12px 10px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #f4f7fa;
            font-size: 1.1em;
        }
        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .actions button {
            min-width: 90px;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8fafd;
        }
        .table-hover tbody tr:hover {
            background: #f0f4ff;
            transition: background 0.2s;
        }
        .add-row {
            background: #eaf6ff;
        }
        .admin-table-section h3 {
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .admin-table-section form input,
        .admin-table-section form select {
            min-width: 120px;
            max-width: 220px;
            margin: 0 auto;
        }
        @media (max-width: 1200px) {
            table, .admin-table-section { min-width: 700px; }
        }
        @media (max-width: 900px) {
            table, .admin-table-section { min-width: 350px; }
            .registration-container { padding: 0; }
        }
    </style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <div class="hero-header position-relative mb-4 w-100">
        <div class="d-flex align-items-center">
            <i class="bi bi-speedometer2 me-3" style="font-size:2.8rem;"></i>
            <div>
                <h1 class="mb-1" style="font-weight:700; letter-spacing:1px;">Admin Dashboard</h1>
                <p class="mb-0" style="font-size:1.2rem; opacity:0.95;">Overview of all facilities, bookings, users, and analytics.</p>
            </div>
        </div>
        <i class="bi bi-graph-up"></i>
    </div>
    <div class="admin-table-section">
        <h3><i class="bi bi-building"></i> Sports Facilities</h3>
        <div class="table-responsive">
        <table class="table table-striped table-hover mt-4 align-middle">
            <thead>
            <tr>
                <th><i class="bi bi-building"></i> Name</th>
                <th><i class="bi bi-tags"></i> Type</th>
                <th><i class="bi bi-people"></i> Capacity</th>
                <th><i class="bi bi-check2-circle"></i> Availability</th>
                <th><i class="bi bi-tools"></i> Maintenance</th>
                <th><i class="bi bi-gear"></i> Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $facilities->fetch_assoc()): ?>
            <tr>
                <form method="post">
                <td><input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" class="form-control form-control-sm" required></td>
                <td><input type="text" name="type" value="<?php echo htmlspecialchars($row['type']); ?>" class="form-control form-control-sm" required></td>
                <td><input type="number" name="capacity" value="<?php echo (int)$row['capacity']; ?>" min="1" class="form-control form-control-sm" required></td>
                <td>
                    <select name="availability" class="form-select form-select-sm">
                        <option value="available" <?php if($row['availability']==='available') echo 'selected'; ?>>Available</option>
                        <option value="unavailable" <?php if($row['availability']==='unavailable') echo 'selected'; ?>>Unavailable</option>
                    </select>
                </td>
                <td>
                    <select name="maintenance" class="form-select form-select-sm">
                        <option value="none" <?php if($row['maintenance']==='none') echo 'selected'; ?>>None</option>
                        <option value="scheduled" <?php if($row['maintenance']==='scheduled') echo 'selected'; ?>>Scheduled</option>
                        <option value="in_progress" <?php if($row['maintenance']==='in_progress') echo 'selected'; ?>>In Progress</option>
                    </select>
                </td>
                <td class="actions">
                    <input type="hidden" name="facility_id" value="<?php echo (int)$row['facility_id']; ?>">
                    <button type="submit" name="update_facility" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="Update Facility"><i class="bi bi-save"></i> Update</button>
                    <button type="submit" name="delete_facility" onclick="return confirm('Delete this facility?');" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" data-bs-title="Delete Facility"><i class="bi bi-trash"></i> Delete</button>
                </form>
                </td>
            </tr>
            <?php endwhile; ?>
            <tr class="add-row">
                <form method="post">
                <td><input type="text" name="name" class="form-control form-control-sm" required></td>
                <td><input type="text" name="type" class="form-control form-control-sm" required></td>
                <td><input type="number" name="capacity" min="1" class="form-control form-control-sm" required></td>
                <td>
                    <select name="availability" class="form-select form-select-sm">
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </td>
                <td>
                    <select name="maintenance" class="form-select form-select-sm">
                        <option value="none">None</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                    </select>
                </td>
                <td class="actions">
                    <button type="submit" name="add_facility" class="btn btn-success btn-sm" data-bs-toggle="tooltip" data-bs-title="Add Facility"><i class="bi bi-plus-circle"></i> Add</button>
                </td>
                </form>
            </tr>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
</body>
</html>
