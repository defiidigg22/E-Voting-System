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
    // Removed created_at from SELECT list as it caused errors before
    $voters_stmt = $pdo->query("SELECT id, full_name, email, national_id, pin FROM users ORDER BY full_name ASC");
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
                <li><a href="manage_voters.php" class="active"><i class="fas fa-user-check fa-fw"></i>Manage Voters</a></li>
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
                    Manage Registered Voters
                </div>
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
                     <h3 class="section-title"><i class="fas fa-id-card"></i>All Registered Voters</h3>
                     <span class="badge badge-primary"><?php echo count($voters); ?> User(s)</span>
                 </div>

                 <?php if (!empty($voters)): ?>
                     <div class="table-wrapper"> <table class="table">
                             <thead>
                                 <tr>
                                     <th>Full Name</th>
                                     <th>Email</th>
                                     <th>National ID</th>
                                     <th>General PIN</th>
                                     </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($voters as $voter): ?>
                                     <tr>
                                         <td><?php echo htmlspecialchars($voter['full_name']); ?></td>
                                         <td><?php echo htmlspecialchars($voter['email']); ?></td>
                                         <td><?php echo htmlspecialchars($voter['national_id']); ?></td>
                                         <td><?php echo htmlspecialchars($voter['pin']); ?></td>
                                         </tr>
                                 <?php endforeach; ?>
                             </tbody>
                         </table>
                     </div> <?php else: ?>
                      <div class="empty-state">
                          <p>No voters found in the system.</p>
                      </div>
                 <?php endif; ?>
             </div>

        </div>
    </div>
</body>
</html>
