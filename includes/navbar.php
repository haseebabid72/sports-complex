<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user_role = $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;
$base = '/sports-complex';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Complex</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f4f8fb; }
        .registration-container, .card { box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-radius: 10px; }
        .card { margin-bottom: 32px; }
        label { font-weight: 500; }
        input, select, textarea { border-radius: 6px !important; }
        .btn-primary, .btn-success, .btn-danger { border-radius: 6px; }
        .table th, .table td { vertical-align: middle; }
        .message { background: #e7f5e6; color: #256029; border: 1px solid #b6e2b6; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?=$base?>/index.php">Sports Complex</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="<?=$base?>/index.php">Home</a></li>
        <?php if ($user_role === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/admin/index.php">Admin Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/admin/reports.php">Reports</a></li>
        <?php endif; ?>
        <?php if ($user_role === 'staff'): ?>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/user/staff_dashboard.php">Staff Dashboard</a></li>
        <?php endif; ?>
        <?php if ($user_role === 'member'): ?>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/user/member_dashboard.php">Member Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/user/booking_history.php">My Bookings</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?=$base?>/booking/index.php">Book Facility</a></li>
        <li class="nav-item"><a class="nav-link" href="<?=$base?>/equipment/index.php">Equipment</a></li>
        <?php if ($user_name): ?>
          <li class="nav-item"><span class="nav-link disabled">Hello, <?php echo htmlspecialchars($user_name); ?></span></li>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/user/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/user/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="<?=$base?>/user/index.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
</body>
</html>
