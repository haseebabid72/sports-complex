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
</body>
</html>
