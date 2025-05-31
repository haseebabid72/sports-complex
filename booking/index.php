<?php
// AJAX endpoint for available time slots (returns only future slots for today, all for future dates)
if (isset($_GET['facility_id']) && isset($_GET['date'])) {
    require_once '../includes/db.php';
    $facility_id = (int)$_GET['facility_id'];
    $date = $_GET['date'];
    $slots = [];
    // Use server timezone for correct slot filtering
    $now = new DateTime('now');
    $today = $now->format('Y-m-d');
    $currentHour = (int)$now->format('H');
    for ($h = 6; $h <= 22; $h++) {
        if ($date === $today && $h <= $currentHour) continue;
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

// Booking module entry point
require_once '../includes/db.php';
require_once '../includes/auth.php';
session_start();

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
    // Use session user id for booking
    if (!isset($_SESSION['user_id'])) {
        $booking_message = 'You must be logged in to book a facility.';
    } else {
        $player_id = $_SESSION['user_id'];
        // Check if facility is under maintenance
        if (isset($maintenance[$facility_id])) {
            $booking_message = 'This facility is under maintenance and cannot be booked.';
        } else {
            // Check for double booking and capacity
            // Get facility capacity
            $stmt = $conn->prepare("SELECT capacity FROM facilities WHERE facility_id=?");
            $stmt->bind_param('i', $facility_id);
            $stmt->execute();
            $stmt->bind_result($capacity);
            $stmt->fetch();
            $stmt->close();
            // Count bookings for this slot
            $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE facility_id=? AND booking_time=?");
            $stmt->bind_param('is', $facility_id, $booking_time);
            $stmt->execute();
            $stmt->bind_result($cnt);
            $stmt->fetch();
            $stmt->close();
            // Prevent double booking by the same user
            $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE player_id=? AND facility_id=? AND booking_time=?");
            $stmt->bind_param('iis', $player_id, $facility_id, $booking_time);
            $stmt->execute();
            $stmt->bind_result($user_cnt);
            $stmt->fetch();
            $stmt->close();
            if ($user_cnt > 0) {
                $booking_message = 'You have already booked this slot.';
            } elseif ($cnt >= $capacity) {
                $booking_message = 'This slot is fully booked. <form method="post" style="display:inline;"><input type="hidden" name="facility_id" value="' . $facility_id . '"><input type="hidden" name="date" value="' . htmlspecialchars($date) . '"><input type="hidden" name="time" value="' . htmlspecialchars($time) . '"><button type="submit" name="waitlist_facility">Join Waitlist</button></form>';
            } else {
                $stmt = $conn->prepare("INSERT INTO bookings (player_id, facility_id, booking_time, duration_minutes) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iisi', $player_id, $facility_id, $booking_time, $duration);
                if ($stmt->execute()) {
                    $booking_message = 'Booking successful!';
                    send_booking_notification($player_id, $facility_id, $date, $time);
                } else {
                    $booking_message = 'Booking failed: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
// Waitlist for fully booked slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['waitlist_facility'])) {
    $facility_id = (int)$_POST['facility_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id && $facility_id && $date && $time) {
        $stmt = $conn->prepare("INSERT INTO waitlist (facility_id, user_id, date, time, request_time) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('iiss', $facility_id, $user_id, $date, $time);
        $stmt->execute();
        $stmt->close();
    }
}
// Recurring bookings for regular users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recurring_booking'])) {
    $facility_id = (int)$_POST['facility_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $repeat = (int)$_POST['repeat']; // number of weeks
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id && $facility_id && $date && $time && $repeat > 0) {
        for ($i = 0; $i < $repeat; $i++) {
            $booking_date = date('Y-m-d', strtotime("$date +$i week"));
            $booking_time = $booking_date . ' ' . $time . ':00';
            // Check for conflicts as in normal booking
            $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE facility_id=? AND booking_time=?");
            $stmt->bind_param('is', $facility_id, $booking_time);
            $stmt->execute();
            $stmt->bind_result($cnt);
            $stmt->fetch();
            $stmt->close();
            if ($cnt == 0) {
                $stmt = $conn->prepare("INSERT INTO bookings (player_id, facility_id, booking_time, duration_minutes) VALUES (?, ?, ?, 60)");
                $stmt->bind_param('iisi', $user_id, $facility_id, $booking_time, $duration = 60);
                $stmt->execute();
                $stmt->close();
            }
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
// Send booking confirmation notification (placeholder for email/SMS)
function send_booking_notification($user_id, $facility_id, $date, $time) {
    // In a real system, fetch user email/phone and send notification
    // For now, just log the action
    log_action($user_id, 'booking_notification', "Facility: $facility_id, Date: $date, Time: $time");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Facility Booking Timetable</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>Facility Timetable</h2>
    <?php if (!empty($booking_message)): ?>
        <div class="message"><?php echo htmlspecialchars($booking_message); ?></div>
    <?php endif; ?>
    <form method="post" id="bookingForm" class="card p-4 shadow-sm border-0 mb-4">
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
        <button type="submit" name="book_facility" class="btn btn-primary w-100">Book</button>
    </form>
    <form method="post" id="recurringBookingForm" style="margin-top:24px;">
        <h3>Recurring Booking</h3>
        <label for="facility_id_rec">Facility</label>
        <select name="facility_id" id="facility_id_rec" required>
            <option value="">Select facility</option>
            <?php $facilities->data_seek(0); while ($f = $facilities->fetch_assoc()): ?>
                <option value="<?php echo $f['facility_id']; ?>"><?php echo htmlspecialchars($f['name'] . ' (' . $f['type'] . ')'); ?></option>
            <?php endwhile; ?>
        </select>
        <label for="date_rec">Start Date</label>
        <input type="date" name="date" id="date_rec" min="<?php echo date('Y-m-d'); ?>" required>
        <label for="time_rec">Time Slot</label>
        <select name="time" id="time_rec" required>
            <option value="">Select a facility and date</option>
        </select>
        <label for="repeat">Repeat for (weeks)</label>
        <input type="number" name="repeat" id="repeat" min="1" max="12" value="4" required>
        <button type="submit" name="recurring_booking" class="btn btn-primary w-100">Book Recurring</button>
    </form>
    <div style="margin-top:24px;">
        <h3>Maintenance Schedule</h3>
        <table class="calendar-table table table-striped table-hover mt-4">
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
    <a href="../index.php" class="btn btn-secondary mt-3">Back</a>
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
                if (slots.length === 0) {
                    timeSelect.innerHTML = '<option value="">No available slots</option>';
                } else {
                    timeSelect.innerHTML = '<option value="">Select time slot</option>';
                    slots.forEach(slot => {
                        const [hour] = slot.split(':');
                        const nextHour = parseInt(hour) + 1;
                        timeSelect.innerHTML += `<option value="${slot}">${slot} - ${(nextHour < 10 ? '0' : '') + nextHour}:00</option>`;
                    });
                }
            })
            .catch(() => {
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
            });
    } else {
        timeSelect.innerHTML = '<option value="">Select a facility and date</option>';
    }
}
</script>
</body>
</html>
