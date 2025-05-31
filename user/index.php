<?php
// User module entry point
include '../includes/db.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');
    if ($name && $email && $password && $role) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO players (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $email, $hashed, $role);
        if ($stmt->execute()) {
            $message = 'Registration successful!';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = 'All fields are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm border-0">
                    <h2 class="text-center mb-4">Register</h2>
                    <?php if ($message): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form id="registrationForm" method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name:</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role:</label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="">Select role</option>
                                <option value="member">Member</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-header position-relative mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-speedometer2 me-3" style="font-size:2.8rem;"></i>
            <div>
                <h1 class="mb-1" style="font-weight:700; letter-spacing:1px;">Dashboard</h1>
                <p class="mb-0" style="font-size:1.2rem; opacity:0.95;">Welcome to your sports complex dashboard. Quick access to bookings, equipment, and reports.</p>
            </div>
        </div>
        <i class="bi bi-graph-up"></i>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>
