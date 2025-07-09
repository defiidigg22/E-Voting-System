<?php
session_start();
require_once 'db_config.php'; // PDO

// Check if superadmin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    header("Location: admin_login.php");
    exit();
}

$loggedInAdminId = $_SESSION['admin_id']; // ID of the currently logged-in superadmin

// Get logged-in admin info for sidebar
$stmt_admin_info = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin_info->execute([$loggedInAdminId]);
$admin = $stmt_admin_info->fetch(); // Used for sidebar

if (!$admin) {
    session_destroy(); header("Location: admin_login.php?error=admin_details_missing"); exit();
}
// $is_superadmin is already true based on the check at the top

// Handle admin deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    // Prevent superadmin from deleting their own account
    if ($delete_id == $loggedInAdminId) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
    } else {
        try {
            // Check if admin exists before deleting
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE admin_id = ?");
            $checkStmt->execute([$delete_id]);
            if ($checkStmt->fetchColumn() > 0) {
                $deleteStmt = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
                if ($deleteStmt->execute([$delete_id])) {
                     // Log the action (optional)
                     /*
                    $logStmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                    $logStmt->execute([$loggedInAdminId, 'delete', 'Deleted admin account ID: ' . $delete_id, $_SERVER['REMOTE_ADDR']]);
                    */
                    $_SESSION['success_message'] = "Admin account deleted successfully.";
                } else {
                     $_SESSION['error_message'] = "Failed to delete admin account.";
                }
            } else {
                 $_SESSION['error_message'] = "Admin account not found for deletion.";
            }
        } catch (PDOException $e) {
             // Handle potential errors (e.g., foreign key constraints if admin created elections)
             if ($e->getCode() == '23000') {
                  $_SESSION['error_message'] = "Cannot delete admin: They may have created elections or other related records.";
             } else {
                 $_SESSION['error_message'] = "Database error during deletion: " . $e->getMessage();
             }
        }
    }
    header("Location: manage_admins.php"); // Redirect back to refresh
    exit();
}

// Get all admins for display
$stmt_all_admins = $pdo->query("SELECT admin_id, username, full_name, email, is_superadmin, created_at FROM admins ORDER BY is_superadmin DESC, created_at DESC");
$admins_list = $stmt_all_admins->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - E-Voting System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 50%, #f0abfc 100%);
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #6366f1 0%, #a78bfa 100%);
            color: #fff;
            padding: 2rem 1rem 1rem 1rem;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            width: 240px;
            z-index: 10;
            box-shadow: 2px 0 16px #6366f122;
            transition: left 0.3s;
        }
        .sidebar.closed {
            left: -260px !important;
        }
        .sidebar-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 0.2rem;
            background: linear-gradient(90deg, #fff, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }
        .sidebar-header p {
            font-size: 1rem;
            color: #fbbf24;
            margin-bottom: 0;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li {
            margin-bottom: 1.2rem;
        }
        .sidebar-menu a {
            color: #fff;
            text-decoration: none;
            font-size: 1.08rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.7rem 1rem;
            border-radius: 0.7rem;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar-menu a.active, .sidebar-menu a:hover {
            background: #fff2;
            color: #fbbf24;
        }
        .main-content {
            margin-left: 260px;
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .welcome-message {
            font-size: 1.2rem;
            font-weight: 600;
            color: #6366f1;
        }
        .logout-btn {
            background: linear-gradient(90deg, #f472b6, #6366f1);
            color: #fff;
            border: none;
            border-radius: 1.5rem;
            padding: 0.5rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 2px 8px #6366f144;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: linear-gradient(90deg, #6366f1, #f472b6);
        }
        .section-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 2px 16px #6366f122;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            margin-bottom: 2rem;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #6366f1;
            margin: 0;
        }
        .btn.btn-sm {
            background: linear-gradient(90deg, #a78bfa, #6366f1);
            color: #fff;
            border: none;
            border-radius: 1.2rem;
            padding: 0.4rem 1.2rem;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 2px 8px #a78bfa33;
            transition: background 0.2s;
        }
        .btn.btn-sm:hover {
            background: linear-gradient(90deg, #6366f1, #a78bfa);
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .table {
            background: none;
            color: #22223b;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .badge {
            font-size: 0.95em;
            padding: 0.4em 0.8em;
            border-radius: 1em;
        }
        .badge-primary { background: #6366f1; color: #fff; }
        .badge-secondary { background: #a1a1aa; color: #fff; }
        .empty-state {
            color: #a1a1aa;
            font-style: italic;
            text-align: center;
            margin: 1.5rem 0;
        }
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { position: fixed; width: 80vw; min-height: 100vh; left: -90vw; top: 0; box-shadow: 2px 0 16px #6366f122; }
            .sidebar.open { left: 0 !important; }
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button id="sidebarToggle" class="btn btn-sm" style="position:fixed;top:18px;left:18px;z-index:1001;background:linear-gradient(90deg,#a78bfa,#6366f1);color:#fff;border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px #a78bfa33;">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar" id="sidebarPanel">
        <div class="sidebar-header">
            <h2>E-Voting System</h2>
            <p>Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt fa-fw"></i>Dashboard</a></li>
            <li><a href="manage_elections.php"><i class="fas fa-box-archive fa-fw"></i>Manage Elections</a></li>
            <li><a href="manage_candidates.php"><i class="fas fa-users fa-fw"></i>Manage Candidates</a></li>
            <li><a href="manage_voters.php"><i class="fas fa-user-check fa-fw"></i>Manage Voters</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-pie fa-fw"></i>Reports</a></li>
            <?php if ($admin['is_superadmin']): ?>
                <li><a href="manage_admins.php" class="active"><i class="fas fa-user-shield fa-fw"></i>Manage Admins</a></li>
            <?php endif; ?>
            <li><a href="settings.php"><i class="fas fa-cog fa-fw"></i>Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i>Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <div class="welcome-message">Manage Admin Accounts</div>
            <button class="logout-btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        <div class="section-card">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-user-cog"></i>Admin Accounts</h3>
                <a href="add_admin.php" class="btn btn-sm"><i class="fas fa-user-plus"></i> Add New Admin</a>
            </div>
            <?php if (!empty($admins_list)): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins_list as $admin_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin_item['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_item['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_item['email']); ?></td>
                                    <td>
                                        <?php if ($admin_item['is_superadmin']): ?>
                                            <span class="badge badge-primary">Super Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($admin_item['created_at'])); ?></td>
                                    <td>
                                        <?php if ($admin_item['admin_id'] != $loggedInAdminId): // Can't edit/delete self ?>
                                            <a href="edit_admin.php?id=<?php echo $admin_item['admin_id']; ?>" class="btn btn-secondary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_admins.php?delete=<?php echo $admin_item['admin_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this admin account?');"><i class="fas fa-trash"></i></a>
                                        <?php else: ?>
                                            <span style="color: #a1a1aa; font-style: italic;">(Your Account)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No other admin accounts found.</p>
                    <a href="add_admin.php" class="btn btn-sm"><i class="fas fa-user-plus"></i> Add Admin</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle logic
        const sidebar = document.getElementById('sidebarPanel');
        const toggleBtn = document.getElementById('sidebarToggle');
        let sidebarOpen = true;
        function setSidebar(open) {
            sidebarOpen = open;
            if (window.innerWidth <= 900) {
                sidebar.classList.toggle('open', open);
                sidebar.classList.toggle('closed', !open);
            } else {
                sidebar.classList.remove('open');
                sidebar.classList.remove('closed');
            }
        }
        toggleBtn.addEventListener('click', () => setSidebar(!sidebarOpen));
        window.addEventListener('resize', () => setSidebar(window.innerWidth > 900));
        setSidebar(window.innerWidth > 900);
    </script>
</body>
</html>
