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
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header"><h2>E-Voting System</h2><p>Admin Panel</p></div>
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
                    <div class="table-wrapper"> <table class="table">
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
                                                <span style="color: var(--text-light); font-style: italic;">(Your Account)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div> <?php else: ?>
                    <div class="empty-state">
                        <p>No other admin accounts found.</p>
                         <a href="add_admin.php" class="btn"><i class="fas fa-user-plus"></i> Add Admin</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
