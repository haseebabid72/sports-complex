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
<div class="registration-container" style="max-width:600px;">
    <h1>Welcome to the Sports Complex Management System</h1>
    <p>This system allows you to manage facility bookings, equipment, and user accounts for your sports complex.</p>
    <ul style="margin: 24px 0;">
        <li>Book sports facilities and reserve equipment</li>
        <li>Track equipment status and maintenance</li>
        <li>View reports and analytics (admin only)</li>
        <li>Role-based dashboards for admin, staff, and members</li>
    </ul>
    <p>Use the navigation bar above to get started.</p>
</div>
<?php?>