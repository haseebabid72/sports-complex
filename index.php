<?php
// Entry point for the Sports Complex Management System
session_start();
if (isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: /sports-complex/admin/index.php');
            exit;
        case 'staff':
            header('Location: /sports-complex/user/staff_dashboard.php');
            exit;
        case 'member':
            header('Location: /sports-complex/user/member_dashboard.php');
            exit;
        case 'guest':
            header('Location: /sports-complex/user/guest_dashboard.php');
            exit;
    }
}
include __DIR__ . '/includes/navbar.php';
?>
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
.hero-section {
    background: linear-gradient(120deg, #007bff 0%, #00c6ff 100%);
    color: #fff;
    padding: 48px 0 32px 0;
    text-align: center;
    border-radius: 0 0 32px 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}
.hero-section h1 {
    font-size: 2.6em;
    font-weight: 700;
    margin-bottom: 12px;
}
.hero-section p {
    font-size: 1.25em;
    margin-bottom: 24px;
}
.quick-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 32px;
    margin: 40px 0 0 0;
}
.quick-link-card {
    background: #fff;
    color: #222;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    padding: 28px 32px;
    min-width: 220px;
    max-width: 320px;
    text-align: center;
    transition: transform 0.15s, box-shadow 0.15s;
    text-decoration: none;
    font-size: 1.1em;
}
.quick-link-card:hover {
    transform: translateY(-6px) scale(1.03);
    box-shadow: 0 6px 24px rgba(0,123,255,0.13);
    color: #007bff;
}
.quick-link-card i {
    font-size: 2.2em;
    margin-bottom: 10px;
    color: #007bff;
}
@media (max-width: 700px) {
    .quick-links { flex-direction: column; gap: 18px; }
    .hero-section { padding: 32px 0 18px 0; }
}
</style>
<div class="hero-section">
    <h1>Welcome to the Sports Complex Management System</h1>
    <p>Effortlessly manage facility bookings, equipment, and user accounts for your sports complex.</p>
    <div class="quick-links">
        <a class="quick-link-card" href="/sports-complex/booking/index.php">
            <i class="bi bi-calendar2-check"></i><br>
            Book a Facility
        </a>
        <a class="quick-link-card" href="/sports-complex/equipment/index.php">
            <i class="bi bi-basket"></i><br>
            Equipment Inventory
        </a>
        <a class="quick-link-card" href="/sports-complex/user/index.php">
            <i class="bi bi-person-plus"></i><br>
            Register as a User
        </a>
        <a class="quick-link-card" href="/sports-complex/user/login.php">
            <i class="bi bi-box-arrow-in-right"></i><br>
            Login
        </a>
    </div>
</div>
<div class="container mt-5 mb-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-lg border-0" style="border-radius: 1.25rem;">
                <div class="card-body p-4">
                    <h3 class="card-title mb-3 text-primary"><i class="bi bi-question-circle"></i> Why use this system?</h3>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item bg-light"><i class="bi bi-calendar2-check text-success me-2"></i>Book sports facilities and reserve equipment online</li>
                        <li class="list-group-item bg-light"><i class="bi bi-basket text-warning me-2"></i>Track equipment status and maintenance</li>
                        <li class="list-group-item bg-light"><i class="bi bi-bar-chart text-info me-2"></i>View reports and analytics (admin only)</li>
                        <li class="list-group-item bg-light"><i class="bi bi-people text-secondary me-2"></i>Role-based dashboards for admin, staff, and members</li>
                    </ul>
                    <p class="mb-0">Use the navigation bar above or the quick links to get started.</p>
                </div>
            </div>
        </div>
    </div>
</div>