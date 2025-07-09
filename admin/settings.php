<?php
session_start();
require_once 'db_config.php'; //

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php"); //
    exit();
}

$admin_id = $_SESSION['admin_id'];
// Get admin info
$stmt_admin = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin->execute([$admin_id]);
$admin = $stmt_admin->fetch();

if (!$admin) {
    echo "Error: Could not fetch admin details.";
    exit();
}

// --- TODO: Define Settings ---
// What settings are needed? Site name? Timezone? Email settings?
// Fetch current settings from DB (if stored there) or config file.

// --- TODO: Handle Form Submission ---
// If settings form is submitted, validate and update settings in DB/config.


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - E-Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
        }
        .sidebar {
            min-width: 240px;
            max-width: 240px;
            background: linear-gradient(160deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.3s;
            z-index: 1030;
        }
        .sidebar.collapsed {
            margin-left: -240px;
        }
        .sidebar-header {
            padding: 2rem 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .sidebar-header p {
            font-size: 1rem;
            color: #dbeafe;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li {
            margin: 0.5rem 0;
        }
        .sidebar-menu a {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 0.75rem 2rem;
            border-left: 4px solid transparent;
            transition: background 0.2s, border-color 0.2s;
        }
        .sidebar-menu a.active, .sidebar-menu a:hover {
            background: rgba(255,255,255,0.08);
            border-left: 4px solid #fff;
        }
        .main-content {
            margin-left: 240px;
            padding: 2rem 2vw 2rem 2vw;
            transition: margin-left 0.3s;
        }
        .main-content.full {
            margin-left: 0;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .sidebar-toggle {
            background: none;
            border: none;
            color: #6366f1;
            font-size: 2rem;
            margin-right: 1rem;
            display: inline-block;
        }
        .section {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 16px 0 rgba(99,102,241,0.08);
            padding: 2rem 2rem 1.5rem 2rem;
            max-width: 700px;
            margin: 0 auto;
        }
        .section h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #6366f1;
        }
        .alert {
            max-width: 700px;
            margin: 0 auto 1.5rem auto;
        }
        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
            }
            .sidebar {
                position: fixed;
                z-index: 1040;
            }
        }
    </style>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('full');
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>E-Voting System</h2>
            <p>Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_elections.php">Manage Elections</a></li>
            <li><a href="manage_candidates.php">Manage Candidates</a></li>
            <li><a href="manage_voters.php">Manage Voters</a></li>
            <li><a href="reports.php">Reports</a></li>
            <?php if ($admin['is_superadmin']): ?>
                <li><a href="manage_admins.php">Manage Admins</a></li>
            <?php endif; ?>
            <li><a href="settings.php" class="active">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="welcome-message">System Settings</div>
            <button class="btn btn-outline-danger btn-sm" onclick="location.href='logout.php'">Logout</button>
        </div>
        <div class="section">
            <h3>General Settings</h3>
            <p>This section is currently under development. Settings might include:</p>
            <ul>
                <li>System Name / Branding</li>
                <li>Default Timezone</li>
                <li>Email Configuration (for PINs, notifications)</li>
                <li>Security Settings (e.g., session timeout)</li>
                <?php if ($admin['is_superadmin']): ?>
                    <li>Superadmin-specific settings</li>
                <?php endif; ?>
            </ul>
            <form></form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>