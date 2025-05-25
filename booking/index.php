<?php
// Booking module entry point
require_once '../includes/db.php';

// Fetch all facilities
$facilities = $conn->query("SELECT * FROM facilities");
// Fetch all bookings
$bookings = $conn->query("SELECT facility_id, booking_time, duration_minutes FROM bookings");
$facilityBookings = [];
while ($b = $bookings->fetch_assoc()) {
    $fid = $b['facility_id'];
    $start = $b['booking_time'];
    $end = date('Y-m-d H:i:s', strtotime($b['booking_time']) + $b['duration_minutes']*60);
    $facilityBookings[$fid][] = ['start' => $start, 'end' => $end];
}
// Fetch maintenance schedule
$maintenance = [];
$facilitiesForMaintenance = $conn->query("SELECT facility_id, name, maintenance, maintenance_start, maintenance_end FROM facilities");
while ($row = $facilitiesForMaintenance->fetch_assoc()) {
    if ($row['maintenance'] !== 'none') {
        $maintenance[$row['facility_id']] = $row;
    }
}

// Handle booking and equipment reservation
$booking_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_facility'])) {
    $facility_id = (int)$_POST['facility_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $duration = 60; // 1 hour slots
    $booking_time = $date . ' ' . $time . ':00';
    $player_id = 1; // TODO: Replace with session user id
    // Check if facility is under maintenance
    if (isset($maintenance[$facility_id])) {
        $booking_message = 'This facility is under maintenance and cannot be booked.';
    } else {
        // Check for double booking and capacity
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt, f.capacity FROM bookings b JOIN facilities f ON b.facility_id=f.facility_id WHERE b.facility_id=? AND b.booking_time=?");
        $stmt->bind_param('is', $facility_id, $booking_time);
        $stmt->execute();
        $stmt->bind_result($cnt, $capacity);
        $stmt->fetch();
        $stmt->close();
        if ($cnt >= $capacity) {
            $booking_message = 'This slot is fully booked.';
        } else {
            $stmt = $conn->prepare("INSERT INTO bookings (player_id, facility_id, booking_time, duration_minutes) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iisi', $player_id, $facility_id, $booking_time, $duration);
            if ($stmt->execute()) {
                $booking_message = 'Booking successful!';
            } else {
                $booking_message = 'Booking failed: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
// Fetch available equipment for reservation
$equipmentList = $conn->query("SELECT * FROM equipment WHERE status='available'");
/**
 * Get available time slots for a facility and date, considering capacity and existing bookings.
 * @param mysqli $conn
 * @param int $facility_id
 * @param string $date (YYYY-MM-DD)
 * @return array Array of available time slots (e.g., ['06:00', '07:00', ...])
 */
function getAvailableTimeSlots($conn, $facility_id, $date) {
    $slots = [];
    for ($h = 6; $h <= 22; $h++) {
        $slots[] = sprintf('%02d:00', $h);
    }
    // Get facility capacity
    $stmt = $conn->prepare("SELECT capacity FROM facilities WHERE facility_id=?");
    $stmt->bind_param('i', $facility_id);
    $stmt->execute();
    $stmt->bind_result($capacity);
    $stmt->fetch();
    $stmt->close();
    // Get bookings for the date
    $stmt = $conn->prepare("SELECT booking_time FROM bookings WHERE facility_id=? AND DATE(booking_time)=?");
    $stmt->bind_param('is', $facility_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked = [];
    while ($row = $result->fetch_assoc()) {
        $time = date('H:00', strtotime($row['booking_time']));
        if (!isset($booked[$time])) $booked[$time] = 0;
        $booked[$time]++;
    }
    $stmt->close();
    // Only return slots where bookings < capacity
    $available = [];
    foreach ($slots as $slot) {
        if (!isset($booked[$slot]) || $booked[$slot] < $capacity) {
            $available[] = $slot;
        }
    }
    return $available;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Facility Booking Timetable</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .calendar-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .calendar-table th, .calendar-table td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        .calendar-table th { background: #f4f4f4; }
        .slot-available { background: #e7fbe7; cursor: pointer; }
        .slot-booked { background: #fbe7e7; color: #888; }
        .booking-form { max-width: 400px; margin: 40px auto; }
        .booking-form label { display: block; margin-top: 12px; }
        .booking-form select, .booking-form input { width: 100%; padding: 8px; margin-top: 4px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>Facility Timetable</h2>
    <?php if (!empty($booking_message)): ?>
        <div class="message"><?php echo htmlspecialchars($booking_message); ?></div>
    <?php endif; ?>
    <form method="post" id="bookingForm">
        <label for="facility_id">Facility</label>
        <select name="facility_id" id="facility_id" required>
            <option value="">Select facility</option>
            <?php $facilities->data_seek(0); while ($f = $facilities->fetch_assoc()): ?>
                <?php
                $isUnderMaintenance = isset($maintenance[$f['facility_id']]);
                $maintLabel = $isUnderMaintenance ? ' (Under Maintenance)' : '';
                ?>
                <option value="<?php echo $f['facility_id']; ?>" <?php if($isUnderMaintenance) echo 'disabled'; ?>>
                    <?php echo htmlspecialchars($f['name'] . ' (' . $f['type'] . ')' . $maintLabel); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <label for="date">Date</label>
        <input type="date" name="date" id="date" min="<?php echo date('Y-m-d'); ?>" required>
        <label for="time">Time Slot</label>
        <select name="time" id="time" required>
            <option value="">Select a facility and date</option>
        </select>
        <button type="submit" name="book_facility">Book</button>
    </form>
    <div style="margin-top:24px;">
        <h3>Maintenance Schedule</h3>
        <table class="calendar-table">
            <tr><th>Facility</th><th>Status</th><th>Maintenance Start</th><th>Maintenance End</th></tr>
            <?php foreach ($maintenance as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['name']); ?></td>
                    <td><?php echo htmlspecialchars($m['maintenance']); ?></td>
                    <td><?php echo htmlspecialchars($m['maintenance_start'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($m['maintenance_end'] ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div id="calendar"></div>
    <a href="../index.php">Back</a>
</div>
<script>
const facilities = <?php echo json_encode(iterator_to_array($facilities)); ?>;
const bookings = <?php echo json_encode($facilityBookings); ?>;
const playerLimits = {};
facilities.forEach(f => playerLimits[f.facility_id] = f.capacity);

function renderCalendar() {
    const calendar = document.getElementById('calendar');
    const now = new Date();
    const today = now.toISOString().slice(0,10);
    let html = '';
    facilities.forEach(facility => {
        html += `<h3>${facility.name} (${facility.type})</h3>`;
        html += '<table class="calendar-table"><tr><th>Time</th><th>Status</th></tr>';
        for (let h = 6; h <= 22; h++) { // 6am to 10pm
            const slot = `${today} ${(h<10?'0':'')+h}:00:00`;
            let isBooked = false;
            if (bookings[facility.facility_id]) {
                bookings[facility.facility_id].forEach(b => {
                    if (slot >= b.start && slot < b.end) isBooked = true;
                });
            }
            html += `<tr><td>${h}:00 - ${h+1}:00</td><td class="${isBooked?'slot-booked':'slot-available'}">${isBooked?'Booked':'Available'}</td></tr>`;
        }
        html += '</table>';
    });
    calendar.innerHTML = html;
}
document.addEventListener('DOMContentLoaded', renderCalendar);

// Update available time slots on facility or date change
document.getElementById('facility_id').addEventListener('change', updateAvailableSlots);
document.getElementById('date').addEventListener('change', updateAvailableSlots);

function updateAvailableSlots() {
    const facilityId = document.getElementById('facility_id').value;
    const date = document.getElementById('date').value;
    const timeSelect = document.getElementById('time');
    timeSelect.innerHTML = '<option value="">Loading...</option>';
    if (facilityId && date) {
        fetch(`?facility_id=${facilityId}&date=${date}`)
            .then(response => response.json())
            .then(slots => {
                timeSelect.innerHTML = '<option value="">Select time slot</option>';
                slots.forEach(slot => {
                    const [hour] = slot.split(':');
                    const nextHour = parseInt(hour) + 1;
                    timeSelect.innerHTML += `<option value="${slot}">${slot} - ${nextHour < 10 ? '0' : ''}${nextHour}:00</option>`;
                });
            });
    } else {
        timeSelect.innerHTML = '<option value="">Select a facility and date</option>';
    }
}
</script>
</body>
</html>
<?php
// AJAX endpoint for available time slots
require_once '../includes/db.php';
if (isset($_GET['facility_id']) && isset($_GET['date'])) {
    $facility_id = (int)$_GET['facility_id'];
    $date = $_GET['date'];
    // Use the getAvailableTimeSlots function if present, else inline logic
    $slots = [];
    for ($h = 6; $h <= 22; $h++) {
        $slots[] = sprintf('%02d:00', $h);
    }
    $stmt = $conn->prepare("SELECT capacity FROM facilities WHERE facility_id=?");
    $stmt->bind_param('i', $facility_id);
    $stmt->execute();
    $stmt->bind_result($capacity);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("SELECT booking_time FROM bookings WHERE facility_id=? AND DATE(booking_time)=?");
    $stmt->bind_param('is', $facility_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked = [];
    while ($row = $result->fetch_assoc()) {
        $time = date('H:00', strtotime($row['booking_time']));
        if (!isset($booked[$time])) $booked[$time] = 0;
        $booked[$time]++;
    }
    $stmt->close();
    $available = [];
    foreach ($slots as $slot) {
        if (!isset($booked[$slot]) || $booked[$slot] < $capacity) {
            $available[] = $slot;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($available);
    exit;
}
?>
