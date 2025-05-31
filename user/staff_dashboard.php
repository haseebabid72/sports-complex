<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'staff') {
    header('Location: /sports-complex/user/login.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Staff)</h2>
    <p>This is the staff dashboard.</p>
    <a href="logout.php">Logout</a>
</div>
<div class="hero-header position-relative mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-person-gear me-3" style="font-size:2.8rem;"></i>
        <div>
            <h1 class="mb-1" style="font-weight:700; letter-spacing:1px;">Staff Dashboard</h1>
            <p class="mb-0" style="font-size:1.2rem; opacity:0.95;">Manage facility operations, bookings, and equipment.</p>
        </div>
    </div>
    <i class="bi bi-tools"></i>
</div>
</body>
</html>
