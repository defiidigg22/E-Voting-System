<?php
session_start();
require_once 'db_config.php'; // Use PDO

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
// Get admin info
$stmt_admin = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin->execute([$admin_id]);
$admin = $stmt_admin->fetch();

if (!$admin) {
    session_destroy();
    header("Location: admin_login.php?error=admin_not_found");
    exit();
}
$is_superadmin = $admin['is_superadmin'];

// Fetch all registered users (voters) from the 'users' table
$voters = [];
try {
    // MODIFIED: Added 'is_active' to the SELECT statement
    $voters_stmt = $pdo->query("SELECT id, full_name, email, national_id, pin, is_active FROM users ORDER BY full_name ASC");
    $voters = $voters_stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error fetching voters: " . $e->getMessage();
    // error_log("Admin Manage Voters Error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registered Voters</title>
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
        .section-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 16px 0 rgba(99,102,241,0.08);
            padding: 2rem 2rem 1.5rem 2rem;
            max-width: 1100px;
            margin: 0 auto 2rem auto;
        }
        .section-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #6366f1;
        }
        .alert {
            max-width: 1100px;
            margin: 0 auto 1.5rem auto;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .table {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 4px #6366f11a;
        }
        .badge-primary {
            background: #6366f1;
        }
        .badge-success {
            background: #22c55e;
        }
        .badge-warning {
            background: #facc15;
            color: #1e293b;
        }
        .empty-state {
            text-align: center;
            color: #64748b;
            padding: 2rem 0;
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
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
             <div class="sidebar-header"><h2>E-Voting System</h2><p>Admin Panel</p></div>
             <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt fa-fw"></i>Dashboard</a></li>
                <li><a href="manage_elections.php"><i class="fas fa-box-archive fa-fw"></i>Manage Elections</a></li>
                <li><a href="manage_candidates.php"><i class="fas fa-users fa-fw"></i>Manage Candidates</a></li>
                <li><a href="manage_voters.php" class="active"><i class="fas fa-user-check fa-fw"></i>Manage Voters</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-pie fa-fw"></i>Reports</a></li>
                <?php if ($admin['is_superadmin']): ?>
                    <li><a href="manage_admins.php"><i class="fas fa-user-shield fa-fw"></i>Manage Admins</a></li>
                <?php endif; ?>
                <li><a href="settings.php"><i class="fas fa-cog fa-fw"></i>Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i>Logout</a></li>
            </ul>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()" style="position:fixed;top:18px;left:18px;z-index:1001;background:linear-gradient(90deg,#a78bfa,#6366f1);color:#fff;border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px #a78bfa33;">
            <i class="fas fa-bars"></i>
        </button>
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">Manage Registered Voters</div>
                <button class="btn btn-outline-danger btn-sm" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-id-card"></i>All Registered Voters</h3>
                    <span class="badge badge-primary"><?php echo count($voters); ?> User(s)</span>
                </div>
                <?php if (!empty($voters)): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>National ID</th>
                                    <th>General PIN</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($voters as $voter): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($voter['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($voter['email']); ?></td>
                                        <td><?php echo htmlspecialchars($voter['national_id']); ?></td>
                                        <td><?php echo htmlspecialchars($voter['pin']); ?></td>
                                        <td>
                                            <?php if ($voter['is_active'] == 1): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($voter['is_active'] == 1): ?>
                                                <a href="handle_voter_status.php?user_id=<?php echo $voter['id']; ?>&action=deactivate" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to deactivate this voter?');">Deactivate</a>
                                            <?php else: ?>
                                                <a href="handle_voter_status.php?user_id=<?php echo $voter['id']; ?>&action=activate" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to activate this voter?');">Activate</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No voters found in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function toggleSidebar() {
                document.querySelector('.sidebar').classList.toggle('collapsed');
                document.querySelector('.main-content').classList.toggle('full');
            }
        </script>
    </body>
</html>