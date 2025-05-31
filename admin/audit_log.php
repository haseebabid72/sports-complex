<?php
require_once '../includes/db.php';
// Only allow admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /sports-complex/user/login.php');
    exit;
}
$result = $conn->query("SELECT a.*, p.name FROM audit_log a LEFT JOIN players p ON a.user_id = p.player_id ORDER BY a.timestamp DESC LIMIT 100");
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .audit-hero-header {
            background: linear-gradient(90deg, #4e54c8 0%, #8f94fb 100%);
            color: #fff;
            padding: 2.2rem 1rem 1.5rem 1rem;
            border-radius: 1.25rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 8px 32px rgba(78,84,200,0.13);
            position: relative;
            overflow: hidden;
        }
        .audit-hero-header .bi {
            font-size: 3rem;
            opacity: 0.13;
            position: absolute;
            right: 2rem;
            bottom: 1rem;
        }
        .audit-table-section {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            margin-bottom: 2.5rem;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            width: 100%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .table-responsive { overflow-x: auto; }
        table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
            margin-top: 24px;
        }
        th, td {
            border: 1px solid #e3e6f0;
            padding: 12px 10px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: #f4f7fa;
            font-size: 1.1em;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8fafd;
        }
        .table-hover tbody tr:hover {
            background: #f0f4ff;
            transition: background 0.2s;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1.2rem;
            color: #4e54c8;
            font-weight: 500;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; color: #2d2f8f; }
        @media (max-width: 1100px) {
            .audit-table-section, table { min-width: 600px; }
        }
        @media (max-width: 700px) {
            .audit-table-section, table { min-width: 350px; }
            .audit-hero-header { padding: 1.2rem 0.5rem; font-size: 1.1rem; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container py-4">
    <div class="audit-hero-header position-relative mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-lock me-3" style="font-size:2.4rem;"></i>
            <div>
                <h1 class="mb-1" style="font-weight:700; letter-spacing:1px;">Audit Log</h1>
                <p class="mb-0" style="font-size:1.1rem; opacity:0.95;">Track all system actions for transparency and security.</p>
            </div>
        </div>
        <i class="bi bi-journal-text"></i>
    </div>
    <div class="audit-table-section">
        <a href="reports.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Reports</a>
        <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-primary">
                <tr>
                    <th><i class="bi bi-person"></i> User</th>
                    <th><i class="bi bi-activity"></i> Action</th>
                    <th><i class="bi bi-info-circle"></i> Details</th>
                    <th><i class="bi bi-clock-history"></i> Timestamp</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?=htmlspecialchars($row['name'] ?? 'Unknown')?></td>
                <td><?=htmlspecialchars($row['action'])?></td>
                <td><?=htmlspecialchars($row['details'])?></td>
                <td><?=htmlspecialchars($row['timestamp'])?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
