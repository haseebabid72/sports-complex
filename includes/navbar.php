<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user_role = $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;
$base = '/sports-complex';
?>
<nav class="navbar">
    <a href="<?=$base?>/index.php" class="nav-logo">Sports Complex</a>
    <div class="nav-toggle" tabindex="0" aria-label="Toggle navigation">
        <span></span><span></span><span></span>
    </div>
    <div class="nav-links">
        <a href="<?=$base?>/index.php">Home</a>
        <?php if ($user_role === 'admin'): ?>
            <a href="<?=$base?>/admin/index.php">Admin Dashboard</a>
            <a href="<?=$base?>/admin/reports.php">Reports</a>
        <?php endif; ?>
        <?php if ($user_role === 'staff'): ?>
            <a href="<?=$base?>/user/staff_dashboard.php">Staff Dashboard</a>
        <?php endif; ?>
        <?php if ($user_role === 'member'): ?>
            <a href="<?=$base?>/user/member_dashboard.php">Member Dashboard</a>
            <a href="<?=$base?>/user/booking_history.php">My Bookings</a>
        <?php endif; ?>
        <a href="<?=$base?>/booking/index.php">Book Facility</a>
        <a href="<?=$base?>/equipment/index.php">Equipment</a>
        <?php if ($user_name): ?>
            <span class="nav-user">Hello, <?php echo htmlspecialchars($user_name); ?></span>
            <a href="<?=$base?>/user/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?=$base?>/user/login.php">Login</a>
            <a href="<?=$base?>/user/index.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
<style>
.navbar {
    background: #222;
    color: #fff;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 56px;
    margin-bottom: 32px;
}
.navbar .nav-logo {
    font-size: 1.2em;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
}
.navbar .nav-links {
    display: flex;
    gap: 18px;
    align-items: center;
}
.navbar .nav-links a {
    color: #fff;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background 0.2s;
    font-size: 1em;
}
.navbar .nav-links a:hover {
    background: #007bff;
}
.navbar .nav-user {
    color: #b6d4fe;
    margin-right: 8px;
    font-size: 0.98em;
}
.navbar .nav-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
}
.navbar .nav-toggle span {
    height: 3px;
    width: 25px;
    background: #fff;
    margin: 4px 0;
    border-radius: 2px;
}
@media (max-width: 700px) {
    .navbar .nav-links {
        display: none;
        flex-direction: column;
        background: #222;
        position: absolute;
        top: 56px;
        right: 0;
        width: 180px;
        z-index: 100;
    }
    .navbar .nav-links.active {
        display: flex;
    }
    .navbar .nav-toggle {
        display: flex;
    }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.querySelector('.nav-toggle');
    const links = document.querySelector('.nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            links.classList.toggle('active');
        });
    }
});
</script>
