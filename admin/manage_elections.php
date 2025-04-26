<?php
session_start();
require_once 'db_config.php'; // PDO

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Get admin info - Needed for sidebar and permission checks
$stmt_admin = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin->execute([$admin_id]);
$admin = $stmt_admin->fetch();

// Optional: Check if admin data was actually fetched
if (!$admin) {
    session_destroy();
    header("Location: admin_login.php?error=admin_details_missing");
    exit();
}
$is_superadmin = $admin['is_superadmin']; // Use fetched data


// Handle election deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $election_id_to_delete = intval($_GET['delete']);

    // Verify that the election belongs to this admin OR user is superadmin
    $can_delete = false;
    if ($is_superadmin) {
        $can_delete = true; // Superadmin can delete any
    } else {
        // Normal admin can only delete their own
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM elections WHERE election_id = ? AND created_by = ?");
        $stmt_check->execute([$election_id_to_delete, $admin_id]);
        if ($stmt_check->fetchColumn() > 0) {
            $can_delete = true;
        }
    }

    if ($can_delete) {
        try {
            // Added check to ensure election exists before attempting delete
            $checkExistStmt = $pdo->prepare("SELECT COUNT(*) FROM elections WHERE election_id = ?");
            $checkExistStmt->execute([$election_id_to_delete]);
            if($checkExistStmt->fetchColumn() > 0) {
                // Proceed with deletion (consider related data - candidates, votes might need cascading delete or handling)
                // For simplicity, assuming cascade delete is set up in DB or related data isn't critical to block deletion
                $deleteStmt = $pdo->prepare("DELETE FROM elections WHERE election_id = ?");
                if ($deleteStmt->execute([$election_id_to_delete])) {
                    // Log the action (optional)
                    /*
                    $logStmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                    $logStmt->execute([$admin_id, 'delete', 'Deleted election ID: ' . $election_id_to_delete, $_SERVER['REMOTE_ADDR']]);
                    */
                    $_SESSION['success_message'] = "Election deleted successfully.";
                } else {
                     $_SESSION['error_message'] = "Failed to delete election.";
                }
            } else {
                 $_SESSION['error_message'] = "Election not found for deletion.";
            }

        } catch (PDOException $e) {
             // Catch potential foreign key constraint errors if cascade isn't set
             if ($e->getCode() == '23000') { // Integrity constraint violation
                $_SESSION['error_message'] = "Cannot delete election: It might have related candidates or votes. Please remove them first.";
             } else {
                 $_SESSION['error_message'] = "Database error during deletion: " . $e->getMessage();
             }
        }
    } else {
         $_SESSION['error_message'] = "You do not have permission to delete this election.";
    }

    header("Location: manage_elections.php"); // Redirect back to refresh the list
    exit();
}

// Get elections based on admin role
if ($is_superadmin) {
    // Superadmin gets all elections
    $stmt_elections = $pdo->query("SELECT e.*, a.username as created_by_username FROM elections e JOIN admins a ON e.created_by = a.admin_id ORDER BY e.start_datetime DESC");
} else {
    // Normal admin gets only their elections
    $stmt_elections = $pdo->prepare("SELECT * FROM elections WHERE created_by = ? ORDER BY start_datetime DESC");
    $stmt_elections->execute([$admin_id]);
}
$elections = $stmt_elections->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections - E-Voting System</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">  
</head>
<body>
<div class="dashboard">
        <div class="sidebar">
             <div class="sidebar-header"><h2>E-Voting System</h2><p>Admin Panel</p></div>
             <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt fa-fw"></i>Dashboard</a></li>
                <li><a href="manage_elections.php" class="active"><i class="fas fa-box-archive fa-fw"></i>Manage Elections</a></li>
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
                <div class="welcome-message">Manage Elections</div>
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
                    <h3 class="section-title"><i class="fas fa-list"></i><?php echo $is_superadmin ? 'All Elections' : 'Your Elections'; ?></h3>
                    <a href="create_election.php" class="btn btn-sm"><i class="fas fa-plus"></i> Create New Election</a>
                </div>
                <?php if (!empty($elections)): ?>
                <div class="table-wrapper"> <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <?php if ($is_superadmin): ?>
                                    <th>Created By</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($elections as $election): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($election['title']); ?></td>
                                    <td>
                                        <?php // Badge logic remains same ?>
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
                                     <?php if ($is_superadmin): ?>
                                        <td><?php echo htmlspecialchars($election['created_by_username'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <a href="view_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-secondary btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="edit_election.php?id=<?php echo $election['election_id']; ?>" class="btn btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                        <?php if ($is_superadmin || $election['created_by'] == $admin_id): ?>
                                        <a href="manage_elections.php?delete=<?php echo $election['election_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this election? Related votes and candidates might also be affected. This action cannot be undone.');"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> <?php else: ?>
                     <div class="empty-state">
                         <p>No elections found.</p>
                         <a href="create_election.php" class="btn"><i class="fas fa-plus"></i> Create Your First Election</a>
                     </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
