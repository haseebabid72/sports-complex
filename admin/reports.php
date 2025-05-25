<?php
require_once '../includes/db.php';

// Facility usage analytics
$facilityUsage = $conn->query("SELECT f.name, COUNT(b.booking_id) as bookings FROM facilities f LEFT JOIN bookings b ON f.facility_id = b.facility_id GROUP BY f.facility_id");

// Equipment utilization
$equipmentUtil = $conn->query("SELECT e.name, COUNT(h.action) as uses FROM equipment e LEFT JOIN equipment_history h ON e.equipment_id = h.equipment_id AND h.action='checked out' GROUP BY e.equipment_id");

// Peak usage times
$peakTimes = $conn->query("SELECT HOUR(booking_time) as hour, COUNT(*) as count FROM bookings GROUP BY hour ORDER BY count DESC");

// Revenue by facility type (assume each booking = $20, adjust as needed)
$revenue = $conn->query("SELECT f.type, COUNT(b.booking_id)*20 as revenue FROM facilities f LEFT JOIN bookings b ON f.facility_id = b.facility_id GROUP BY f.type");
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Reports & Analytics</title>
    <link rel='stylesheet' href='../assets/css/style.css'>
    <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
    <style>
        .chart-container { width: 90%; max-width: 700px; margin: 32px auto; }
        table { width: 90%; margin: 32px auto; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class='registration-container'>
    <h2>Reports & Analytics</h2>
    <a href='../index.php'>Back</a>
</div>
<div class='chart-container'>
    <h3>Facility Usage Analytics</h3>
    <canvas id='facilityUsageChart'></canvas>
    <table>
        <tr><th>Facility</th><th>Bookings</th></tr>
        <?php $facNames=[];$facCounts=[];while($row=$facilityUsage->fetch_assoc()):$facNames[]=$row['name'];$facCounts[]=$row['bookings']; ?>
        <tr><td><?=htmlspecialchars($row['name'])?></td><td><?=$row['bookings']?></td></tr>
        <?php endwhile; ?>
    </table>
</div>
<div class='chart-container'>
    <h3>Equipment Utilization</h3>
    <canvas id='equipmentUtilChart'></canvas>
    <table>
        <tr><th>Equipment</th><th>Check-outs</th></tr>
        <?php $eqNames=[];$eqCounts=[];while($row=$equipmentUtil->fetch_assoc()):$eqNames[]=$row['name'];$eqCounts[]=$row['uses']; ?>
        <tr><td><?=htmlspecialchars($row['name'])?></td><td><?=$row['uses']?></td></tr>
        <?php endwhile; ?>
    </table>
</div>
<div class='chart-container'>
    <h3>Peak Usage Times</h3>
    <canvas id='peakTimesChart'></canvas>
    <table>
        <tr><th>Hour</th><th>Bookings</th></tr>
        <?php $hours=[];$hourCounts=[];while($row=$peakTimes->fetch_assoc()):$hours[]=$row['hour'];$hourCounts[]=$row['count']; ?>
        <tr><td><?=$row['hour']?>:00</td><td><?=$row['count']?></td></tr>
        <?php endwhile; ?>
    </table>
</div>
<div class='chart-container'>
    <h3>Revenue by Facility Type</h3>
    <canvas id='revenueChart'></canvas>
    <table>
        <tr><th>Facility Type</th><th>Revenue ($)</th></tr>
        <?php $revTypes=[];$revCounts=[];while($row=$revenue->fetch_assoc()):$revTypes[]=$row['type'];$revCounts[]=$row['revenue']; ?>
        <tr><td><?=htmlspecialchars($row['type'])?></td><td><?=$row['revenue']?></td></tr>
        <?php endwhile; ?>
    </table>
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
// Revenue by Facility Type
const revTypes = <?php echo json_encode($revTypes); ?>;
const revCounts = <?php echo json_encode($revCounts); ?>;
new Chart(document.getElementById('revenueChart'), {
    type: 'pie', data: { labels: revTypes, datasets: [{ label: 'Revenue', data: revCounts, backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#6c757d'] }] }, options: { responsive: true }
});
</script>
</body>
</html>