<?php
session_start();
require_once '../includes/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $sql = "SELECT player_id, name, password, role FROM players WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['player_id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'];
            // Redirect by role
            switch ($row['role']) {
                case 'admin':
                    header('Location: /sports-complex/admin/index.php'); exit;
                case 'staff':
                    header('Location: /sports-complex/user/staff_dashboard.php'); exit;
                case 'member':
                    header('Location: /sports-complex/user/member_dashboard.php'); exit;
                default:
                    header('Location: /sports-complex/user/guest_dashboard.php'); exit;
            }
        } else {
            $message = 'Invalid password.';
        }
    } else {
        $message = 'User not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="registration-container">
    <h2>Login</h2>
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>