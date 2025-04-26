<?php
session_start();
require_once 'db_config.php'; // PDO

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$stmt_admin_info = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin_info->execute([$admin_id]);
$admin = $stmt_admin_info->fetch();

// Handle case where admin session exists but admin is deleted from DB
if (!$admin) {
    session_destroy();
    header("Location: admin_login.php?error=admin_not_found");
    exit();
}
$is_superadmin = $admin['is_superadmin'];

// Get elections created by this admin
$elections_stmt = $pdo->prepare("SELECT election_id, title, start_datetime, end_datetime, status FROM elections WHERE created_by = ? ORDER BY start_datetime DESC");
$elections_stmt->execute([$admin_id]);
$elections = $elections_stmt->fetchAll();

// Get recent activities for this admin
$activities_stmt = $pdo->prepare("SELECT log_id, action, description, created_at FROM audit_log WHERE admin_id = ? ORDER BY created_at DESC LIMIT 5");
$activities_stmt->execute([$admin_id]);
$activities = $activities_stmt->fetchAll();

// --- Stats Calculation ---
$active_elections_count = 0;
$total_votes_count = 0;
$total_registered_users_count = 0;

try {
    // Count active elections for this admin
    $stmt_active = $pdo->prepare("SELECT COUNT(*) FROM elections WHERE created_by = ? AND status = 'active'");
    $stmt_active->execute([$admin_id]);
    $active_elections_count = $stmt_active->fetchColumn();

    // Count total votes cast in elections created by this admin
    $stmt_votes = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id IN (SELECT election_id FROM elections WHERE created_by = ?)");
    $stmt_votes->execute([$admin_id]);
    $total_votes_count = $stmt_votes->fetchColumn();

    // Count total registered users in the system
    $stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
    $total_registered_users_count = $stmt_users->fetchColumn();

} catch (PDOException $e) {
    // Handle potential errors fetching stats
    // error_log("Dashboard Stats Error: " . $e->getMessage());
    $active_elections_count = 'N/A';
    $total_votes_count = 'N/A';
    $total_registered_users_count = 'N/A';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Voting System</title>
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
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt fa-fw"></i>Dashboard</a></li>
                <li><a href="manage_elections.php"><i class="fas fa-box-archive fa-fw"></i>Manage Elections</a></li>
                <li><a href="manage_candidates.php"><i class="fas fa-users fa-fw"></i>Manage Candidates</a></li>
                <li><a href="manage_voters.php"><i class="fas fa-user-check fa-fw"></i>Manage Voters</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-pie fa-fw"></i>Reports</a></li>
                <?php if ($admin['is_superadmin']): ?>
                    <li><a href="manage_admins.php"><i class="fas fa-user-shield fa-fw"></i>Manage Admins</a></li>
                <?php endif; ?>
                <li><a href="settings.php"><i class="fas fa-cog fa-fw"></i>Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i>Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    Welcome, <?php echo htmlspecialchars($admin['full_name']); ?>!
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-fire"></i></div>
                    <div class="info">
                        <h3>Active Elections (Yours)</h3>
                        <p><?php echo $active_elections_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                     <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="info">
                        <h3>Total Registered Users</h3>
                        <p><?php echo $total_registered_users_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                     <div class="icon"><i class="fas fa-check-to-slot"></i></div>
                    <div class="info">
                        <h3>Total Votes (Yours)</h3>
                        <p><?php echo $total_votes_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-box-archive"></i>Your Elections</h3>
                    <a href="create_election.php" class="btn btn-sm"><i class="fas fa-plus"></i> Create New</a>
                </div>
                <?php if (!empty($elections)): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($elections as $election): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($election['title']); ?></td>
                                        <td>
                                            <?php
                                                $status_class = '';
                                                switch (strtolower($election['status'])) {
                                                    case 'draft': $status_class = 'badge-warning'; break;
                                                    case 'active': $status_class = 'badge-success'; break;
                                                    case 'completed': $status_class = 'badge-primary'; break;
                                                    case 'archived': $status_class = 'badge-secondary'; break;
                                                    default: $status_class = 'badge-secondary';
                                                }
                                                echo '<span class="badge ' . $status_class . '">' . ucfirst(htmlspecialchars($election['status'])) . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($election['start_datetime'])); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($election['end_datetime'])); ?></td>
                                        <td>
                                            <a href="view_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-secondary btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                            <a href="edit_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">You have not created any elections yet.</p>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-history"></i>Recent Activities</h3>
                    </div>
                 <?php if (!empty($activities)): ?>
                     <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(ucfirst($activity['action'])); ?></td>
                                        <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                 <?php else: ?>
                    <p class="empty-state">No recent activity recorded.</p>
                 <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>