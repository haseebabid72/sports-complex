<?php
require_once '../includes/db.php';

// Export to CSV functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_export.csv"');
    $output = fopen('php://output', 'w');
    // Facility Usage
    fputcsv($output, ['Facility', 'Bookings']);
    $facilityUsage = $conn->query("SELECT f.name, COUNT(b.booking_id) as bookings FROM facilities f LEFT JOIN bookings b ON f.facility_id = b.facility_id GROUP BY f.facility_id");
    while ($row = $facilityUsage->fetch_assoc()) {
        fputcsv($output, [$row['name'], $row['bookings']]);
    }
    fputcsv($output, []);
    // Equipment Utilization
    fputcsv($output, ['Equipment', 'Check-outs']);
    $equipmentUtil = $conn->query("SELECT e.name, COUNT(h.action) as uses FROM equipment e LEFT JOIN equipment_history h ON e.equipment_id = h.equipment_id AND h.action='checked out' GROUP BY e.equipment_id");
    while ($row = $equipmentUtil->fetch_assoc()) {
        fputcsv($output, [$row['name'], $row['uses']]);
    }
    fputcsv($output, []);
    // Peak Usage Times
    fputcsv($output, ['Hour', 'Bookings']);
    $peakTimes = $conn->query("SELECT HOUR(booking_time) as hour, COUNT(*) as count FROM bookings GROUP BY hour ORDER BY hour");
    while ($row = $peakTimes->fetch_assoc()) {
        fputcsv($output, [$row['hour'] . ':00', $row['count']]);
    }
    fclose($output);
    exit;
}

// Facility usage analytics
$where = "1=1";
$params = [];
$types = '';
if (isset($_GET['facility_type']) && $_GET['facility_type'] !== '') {
    $where .= " AND f.type = ?";
    $params[] = $_GET['facility_type'];
    $types .= 's';
}
$facilityUsageSql = "SELECT f.name, COUNT(b.booking_id) as bookings FROM facilities f LEFT JOIN bookings b ON f.facility_id = b.facility_id WHERE $where GROUP BY f.facility_id";
$facilityUsageStmt = $conn->prepare($facilityUsageSql);
if ($params) {
    $facilityUsageStmt->bind_param($types, ...$params);
}
$facilityUsageStmt->execute();
$facilityUsage = $facilityUsageStmt->get_result();

// Equipment utilization
$equipmentUtil = $conn->query("SELECT e.name, COUNT(h.action) as uses FROM equipment e LEFT JOIN equipment_history h ON e.equipment_id = h.equipment_id AND h.action='checked out' GROUP BY e.equipment_id");

// Peak usage times
$peakTimes = $conn->query("SELECT HOUR(booking_time) as hour, COUNT(*) as count FROM bookings GROUP BY hour ORDER BY count DESC");
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Reports & Analytics</title>
    <link rel='stylesheet' href='../assets/css/style.css'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .chart-container { width: 100%; max-width: 900px; margin: 32px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 32px 24px 24px 24px; }
        .chart-container h3 { color: #007bff; font-weight: 600; margin-bottom: 18px; }
        .chart-container form { margin-bottom: 18px; }
        .chart-container table { background: #f8f9fa; border-radius: 8px; overflow: hidden; }
        .chart-container canvas { margin-bottom: 18px; }
        .registration-container { background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 32px 24px 18px 24px; margin-bottom: 32px; }
        .registration-container h2 { color: #007bff; font-weight: 700; }
        .btn { min-width: 160px; }
        .hero-header { background: linear-gradient(135deg, #007bff 0%, #6610f2 100%); color: #fff; padding: 48px 24px; border-radius: 18px; position: relative; overflow: hidden; }
        .hero-header i { position: absolute; top: 20px; right: 20px; font-size: 4rem; opacity: 0.15; }
        @media (max-width: 900px) {
            .chart-container { padding: 18px 4vw; }
        }
    </style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class='registration-container text-center'>
    <h2><i class="bi bi-bar-chart-line"></i> Reports & Analytics</h2>
    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3 mb-2">
        <a href='../index.php' class="btn btn-secondary"><i class="bi bi-house"></i> Home</a>
        <a href='reports.php?export=csv' class="btn btn-success"><i class="bi bi-download"></i> Export All Reports (CSV)</a>
        <a href='audit_log.php' class="btn btn-info"><i class="bi bi-clipboard-data"></i> View Audit Log</a>
    </div>
</div>
<div class="hero-header position-relative mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-bar-chart-steps me-3" style="font-size:2.8rem;"></i>
        <div>
            <h1 class="mb-1" style="font-weight:700; letter-spacing:1px;">Reports Dashboard</h1>
            <p class="mb-0" style="font-size:1.2rem; opacity:0.95;">Advanced analytics, trends, and exportable reports for your sports complex.</p>
        </div>
    </div>
    <i class="bi bi-file-earmark-bar-graph"></i>
</div>
<div class='chart-container'>
    <h3><i class="bi bi-graph-up"></i> Facility Usage Analytics</h3>
    <form method="get" class="row g-2 align-items-center mb-3">
        <div class="col-auto">
            <label for="facility_type" class="col-form-label">Filter by Facility Type:</label>
        </div>
        <div class="col-auto">
            <select name="facility_type" id="facility_type" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <?php $types = $conn->query("SELECT DISTINCT type FROM facilities");
                while($t = $types->fetch_assoc()): ?>
                    <option value="<?=htmlspecialchars($t['type'])?>" <?php if(isset($_GET['facility_type']) && $_GET['facility_type']===$t['type']) echo 'selected'; ?>><?=htmlspecialchars($t['type'])?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
    <canvas id='facilityUsageChart' height="80"></canvas>
    <div class="table-responsive">
    <table class="table table-striped table-hover mt-4">
        <tr class="table-primary"><th>Facility</th><th>Bookings</th></tr>
        <?php $facNames=[];$facCounts=[];while($row=$facilityUsage->fetch_assoc()):$facNames[]=$row['name'];$facCounts[]=$row['bookings']; ?>
        <tr><td><?=htmlspecialchars($row['name'])?></td><td><?=$row['bookings']?></td></tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>
<div class='chart-container'>
    <h3><i class="bi bi-tools"></i> Equipment Utilization</h3>
    <canvas id='equipmentUtilChart' height="80"></canvas>
    <div class="table-responsive">
    <table class="table table-striped table-hover mt-4">
        <tr class="table-success"><th>Equipment</th><th>Check-outs</th></tr>
        <?php $eqNames=[];$eqCounts=[];while($row=$equipmentUtil->fetch_assoc()):$eqNames[]=$row['name'];$eqCounts[]=$row['uses']; ?>
        <tr><td><?=htmlspecialchars($row['name'])?></td><td><?=$row['uses']?></td></tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>
<div class='chart-container'>
    <h3><i class="bi bi-clock-history"></i> Peak Usage Times</h3>
    <canvas id='peakTimesChart' height="80"></canvas>
    <div class="table-responsive">
    <table class="table table-striped table-hover mt-4">
        <tr class="table-warning"><th>Hour</th><th>Bookings</th></tr>
        <?php $hours=[];$hourCounts=[];while($row=$peakTimes->fetch_assoc()):$hours[]=$row['hour'];$hourCounts[]=$row['count']; ?>
        <tr><td><?=$row['hour']?>:00</td><td><?=$row['count']?></td></tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>
<div class='chart-container'>
    <h3><i class="bi bi-person-lines-fill"></i> User Activity Analytics</h3>
    <canvas id='userActivityChart' height="80"></canvas>
    <div class="table-responsive">
    <table class="table table-striped table-hover mt-4">
        <tr class="table-info"><th>User</th><th>Bookings</th></tr>
        <?php 
        $userActivity = $conn->query("SELECT p.name AS username, COUNT(b.booking_id) as bookings FROM players p LEFT JOIN bookings b ON p.player_id = b.player_id GROUP BY p.player_id ORDER BY bookings DESC LIMIT 10");
        $userNames=[];$userBookings=[];
        while($row=$userActivity->fetch_assoc()):$userNames[]=$row['username'];$userBookings[]=$row['bookings']; ?>
        <tr><td><?=htmlspecialchars($row['username'])?></td><td><?=$row['bookings']?></td></tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>
<div class='chart-container'>
    <h3><i class="bi bi-calendar-range"></i> Booking Trends (Last 30 Days)</h3>
    <canvas id='bookingTrendsChart' height="80"></canvas>
    <div class="table-responsive">
    <table class="table table-striped table-hover mt-4">
        <tr class="table-secondary"><th>Date</th><th>Bookings</th></tr>
        <?php 
        $bookingTrends = $conn->query("SELECT DATE(booking_time) as bdate, COUNT(*) as count FROM bookings WHERE booking_time >= CURDATE() - INTERVAL 30 DAY GROUP BY bdate ORDER BY bdate");
        $trendDates=[];$trendCounts=[];
        while($row=$bookingTrends->fetch_assoc()):$trendDates[]=$row['bdate'];$trendCounts[]=$row['count']; ?>
        <tr><td><?=$row['bdate']?></td><td><?=$row['count']?></td></tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>
<script>
// Facility Usage
const facNames = <?php echo json_encode($facNames); ?>;
const facCounts = <?php echo json_encode($facCounts); ?>;
new Chart(document.getElementById('facilityUsageChart'), {
    type: 'bar', data: { labels: facNames, datasets: [{ label: 'Bookings', data: facCounts, backgroundColor: '#007bff' }] }, options: { responsive: true }
});
// Equipment Utilization
const eqNames = <?php echo json_encode($eqNames); ?>;
const eqCounts = <?php echo json_encode($eqCounts); ?>;
new Chart(document.getElementById('equipmentUtilChart'), {
    type: 'bar', data: { labels: eqNames, datasets: [{ label: 'Check-outs', data: eqCounts, backgroundColor: '#28a745' }] }, options: { responsive: true }
});
// Peak Usage Times
const hours = <?php echo json_encode($hours); ?>;
const hourCounts = <?php echo json_encode($hourCounts); ?>;
new Chart(document.getElementById('peakTimesChart'), {
    type: 'line', data: { labels: hours.map(h=>h+':00'), datasets: [{ label: 'Bookings', data: hourCounts, backgroundColor: '#ffc107', borderColor: '#ffc107', fill: false }] }, options: { responsive: true }
});
// User Activity Analytics
const userNames = <?php echo json_encode($userNames); ?>;
const userBookings = <?php echo json_encode($userBookings); ?>;
new Chart(document.getElementById('userActivityChart'), {
    type: 'bar', data: { labels: userNames, datasets: [{ label: 'Bookings', data: userBookings, backgroundColor: '#6c757d' }] }, options: { responsive: true }
});
// Booking Trends (Last 30 Days)
const trendDates = <?php echo json_encode($trendDates); ?>;
const trendCounts = <?php echo json_encode($trendCounts); ?>;
new Chart(document.getElementById('bookingTrendsChart'), {
    type: 'line', data: { labels: trendDates, datasets: [{ label: 'Bookings', data: trendCounts, backgroundColor: '#17a2b8', borderColor: '#17a2b8', fill: false }] }, options: { responsive: true }
});
</script>
</body>
</html>