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
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
     <div class="dashboard">
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
                <div class="welcome-message">
                   System Settings
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
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
                 <form>
                     </form>
            </div>

        </div>
    </div>
</body>
</html>