<?php
// Equipment module entry point
require_once '../includes/db.php';
require_once '../includes/audit.php';
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
    log_action($_SESSION['user_id'], 'add_equipment', $name . ' (' . $type . ')');
    header('Location: index.php'); exit;
}
if (isset($_POST['delete_equipment'])) {
    $id = (int)$_POST['equipment_id'];
    $stmt = $conn->prepare("DELETE FROM equipment WHERE equipment_id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    log_action($_SESSION['user_id'], 'delete_equipment', $id);
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
        log_action($_SESSION['user_id'], $action, 'equipment_id: ' . $equipment_id);
        header('Location: index.php'); exit;
    }
}

// Equipment maintenance tracking and user reporting
if (isset($_POST['report_equipment']) && isset($_POST['equipment_id'])) {
    $equipment_id = (int)$_POST['equipment_id'];
    $report = trim($_POST['report']);
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $stmt = $conn->prepare("INSERT INTO equipment_reports (equipment_id, user_id, report, report_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iis', $equipment_id, $user_id, $report);
    $stmt->execute();
    log_action($user_id, 'report_equipment', 'equipment_id: ' . $equipment_id . ', report: ' . $report);
    $stmt->close();
    header('Location: index.php'); exit;
}

// Fetch facilities for dropdown
$facilitiesList = $conn->query("SELECT facility_id, name FROM facilities");
// Build a facility map for quick lookup
$facilityMap = [];
if ($facilitiesList) {
    $facilitiesList->data_seek(0);
    while ($f = $facilitiesList->fetch_assoc()) {
        $facilityMap[$f['facility_id']] = $f['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Tracker</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: linear-gradient(120deg, #e0eafc 0%, #cfdef3 100%);
        }
        .hero-header {
            background: linear-gradient(90deg, #4e54c8 0%, #8f94fb 100%);
            color: #fff;
            padding: 2.5rem 1rem 2rem 1rem;
            border-radius: 1.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 24px rgba(78,84,200,0.12);
            position: relative;
            overflow: hidden;
        }
        .hero-header .bi {
            font-size: 3.5rem;
            opacity: 0.15;
            position: absolute;
            right: 2rem;
            bottom: 1rem;
        }
        .section-divider {
            border-top: 2px dashed #bfc9e0;
            margin: 2.5rem 0 2rem 0;
        }
        .card-section {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 2.5rem;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
        }
        .card-section h4 {
            font-weight: 600;
        }
        .wide-form {
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .table-responsive { overflow-x: auto; }
        table { width: 100% !important; min-width: 1200px; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #e3e6f0; padding: 10px; text-align: center; }
        th { background: #f4f7fa; }
        .table-hover tbody tr:hover { background: #f0f4ff; transition: background 0.2s; }
        .damaged, .table-danger { background: #fbe7e7 !important; color: #a00 !important; }
        .badge-status { font-size: 1em; }
        .badge-condition { font-size: 1em; }
        .btn-action { transition: transform 0.1s; }
        .btn-action:hover { transform: scale(1.08); }
        .alert { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
        .footer {
            text-align: center;
            color: #888;
            font-size: 0.95em;
            margin-top: 2.5rem;
            padding-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
    <div class="hero-header position-relative mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-basket me-3" style="font-size:2.8rem;"></i>
            <div>
                <h1 class="mb-1" style="font-weight:700; letter-spacing:1px;">Sports Equipment Tracker</h1>
                <p class="mb-0" style="font-size:1.2rem; opacity:0.95;">Effortlessly manage, track, and report all your sports equipment in one place.</p>
            </div>
        </div>
        <i class="bi bi-trophy"></i>
    </div>
    <?php if ($alerts): ?>
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><strong>Alert:</strong> Attention required for: <?php echo htmlspecialchars(implode(', ', $alerts)); ?></div>
        </div>
    <?php endif; ?>
    <?php if ($is_admin): ?>
    <div class="card-section border-0">
        <h4 class="mb-3 text-success"><i class="bi bi-plus-circle"></i> Add Equipment</h4>
        <form method="post" class="row g-3 wide-form bg-light p-3 rounded-3 border border-success-subtle shadow-sm">
            <div class="col-md-4">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="type" class="form-label">Type</label>
                <input type="text" name="type" id="type" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="available">Available</option>
                    <option value="in use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="condition" class="form-label">Condition</label>
                <select name="condition" id="condition" class="form-select" required>
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="needs repair">Needs Repair</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="facility_id" class="form-label">Facility</label>
                <select name="facility_id" id="facility_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($facilityMap as $fid => $fname): ?>
                        <option value="<?php echo $fid; ?>"><?php echo htmlspecialchars($fname); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" name="add_equipment" class="btn btn-primary btn-action"><i class="bi bi-plus-circle"></i> Add Equipment</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    <div class="section-divider"></div>
    <div class="card-section">
        <h4 class="mb-3 text-primary"><i class="bi bi-list-ul"></i> Equipment Inventory</h4>
        <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th><i class="bi bi-box-seam"></i> Name</th>
                    <th><i class="bi bi-tags"></i> Type</th>
                    <th><i class="bi bi-check2-circle"></i> Status</th>
                    <th><i class="bi bi-heart-pulse"></i> Condition</th>
                    <th><i class="bi bi-building"></i> Facility</th>
                    <?php if ($is_admin): ?><th><i class="bi bi-tools"></i> Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $equipment->data_seek(0);
            while ($row = $equipment->fetch_assoc()): ?>
            <tr class="<?php echo $row['condition']==='damaged' ? 'table-danger' : ''; ?>">
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['type']); ?></td>
                <td>
                    <?php
                    $status = $row['status'];
                    $statusClass = [
                        'available' => 'success',
                        'in use' => 'warning',
                        'maintenance' => 'secondary',
                    ];
                    ?>
                    <span class="badge bg-<?php echo $statusClass[$status] ?? 'light'; ?> badge-status">
                        <?php echo ucfirst($status); ?>
                    </span>
                </td>
                <td>
                    <?php
                    $cond = $row['condition'];
                    $condClass = [
                        'good' => 'success',
                        'damaged' => 'danger',
                        'needs repair' => 'warning',
                    ];
                    ?>
                    <span class="badge bg-<?php echo $condClass[$cond] ?? 'light'; ?> badge-condition">
                        <?php echo ucfirst($cond); ?>
                    </span>
                </td>
                <td><?php echo isset($row['facility_id']) && isset($facilityMap[$row['facility_id']]) ? htmlspecialchars($facilityMap[$row['facility_id']]) : '-'; ?></td>
                <?php if ($is_admin): ?>
                <td>
                    <form method="post" class="d-flex flex-column gap-1 align-items-center">
                        <input type="hidden" name="equipment_id" value="<?php echo (int)$row['equipment_id']; ?>">
                        <select name="new_condition" class="form-select form-select-sm w-auto" required title="Set condition">
                            <option value="good" <?php if($row['condition']==='good')echo'selected';?>>Good</option>
                            <option value="damaged" <?php if($row['condition']==='damaged')echo'selected';?>>Damaged</option>
                            <option value="needs repair" <?php if($row['condition]==='needs repair')echo'selected';?>>Needs Repair</option>
                        </select>
                        <?php if ($row['status'] === 'available'): ?>
                            <button type="submit" name="equipment_action" value="check_out" class="btn btn-success btn-sm w-100 btn-action" data-bs-toggle="tooltip" data-bs-title="Check Out this equipment"><i class="bi bi-box-arrow-up-right"></i> Check Out</button>
                        <?php else: ?>
                            <button type="submit" name="equipment_action" value="check_in" class="btn btn-info btn-sm w-100 btn-action" data-bs-toggle="tooltip" data-bs-title="Check In this equipment"><i class="bi bi-box-arrow-in-left"></i> Check In</button>
                        <?php endif; ?>
                        <button type="submit" name="delete_equipment" onclick="return confirm('Delete this equipment?');" class="btn btn-danger btn-sm w-100 btn-action" data-bs-toggle="tooltip" data-bs-title="Delete this equipment"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="section-divider"></div>
    <div class="card-section">
        <h4 class="mb-3 text-secondary"><i class="bi bi-clock-history"></i> Check-in / Check-out History</h4>
        <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-secondary">
                <tr>
                    <th>Equipment</th>
                    <th>Action</th>
                    <th>Condition</th>
                    <th>Handled By</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($h = $history->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($h['name']); ?></td>
                <td><?php echo htmlspecialchars($h['action']); ?></td>
                <td><?php echo htmlspecialchars($h['condition']); ?></td>
                <td><?php echo htmlspecialchars($h['handled_by']); ?></td>
                <td><?php echo htmlspecialchars($h['timestamp']); ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="section-divider"></div>
    <div class="card-section">
        <h4 class="card-title mb-3 text-warning"><i class="bi bi-flag"></i> Report Damaged or Missing Equipment</h4>
        <form method="post" class="row g-3 mb-4 wide-form bg-light p-3 rounded-3 border border-warning-subtle shadow-sm">
            <div class="col-md-6">
                <label for="equipment_id" class="form-label">Equipment</label>
                <select name="equipment_id" id="equipment_id" class="form-select" required>
                    <option value="">Select equipment</option>
                    <?php $equipment->data_seek(0); while ($e = $equipment->fetch_assoc()): ?>
                        <option value="<?php echo (int)$e['equipment_id']; ?>"><?php echo htmlspecialchars($e['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="report" class="form-label">Report</label>
                <textarea name="report" id="report" rows="2" class="form-control" required></textarea>
            </div>
            <div class="col-12">
                <button type="submit" name="report_equipment" class="btn btn-warning btn-action"><i class="bi bi-flag"></i> Submit Report</button>
            </div>
        </form>
    </div>
    <div class="section-divider"></div>
    <div class="card-section">
        <h4 class="card-title mb-3 text-info"><i class="bi bi-info-circle"></i> Recent Equipment Reports</h4>
        <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-info">
                <tr>
                    <th>Equipment</th>
                    <th>Reported By</th>
                    <th>Report</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $reports = $conn->query("SELECT r.*, e.name AS equipment_name, p.name AS user_name FROM equipment_reports r JOIN equipment e ON r.equipment_id = e.equipment_id LEFT JOIN players p ON r.user_id = p.player_id ORDER BY r.report_time DESC LIMIT 10");
            while ($r = $reports->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['equipment_name']); ?></td>
                <td><?php echo htmlspecialchars($r['user_name'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($r['report']); ?></td>
                <td><?php echo htmlspecialchars($r['report_time']); ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="footer">
        <span>&copy; <?php echo date('Y'); ?> Sports Complex Management System &mdash; Equipment Module</span>
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
