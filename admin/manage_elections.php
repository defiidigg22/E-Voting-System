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
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
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
            animation: fadeInUp 1.2s;
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
        .badge-warning { background: #fbbf24; color: #fff; }
        .badge-success { background: #22c55e; color: #fff; }
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
            .sidebar { position: static; width: 100%; min-height: auto; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="sidebar animate__animated animate__fadeInLeft">
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
    <div class="main-content animate__animated animate__fadeInUp">
        <div class="header">
            <div class="welcome-message">Manage Elections</div>
            <button class="logout-btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success animate__animated animate__fadeInDown"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
             <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        <div class="section-card animate__animated animate__fadeInUp animate__delay-1s">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-list"></i><?php echo $is_superadmin ? 'All Elections' : 'Your Elections'; ?></h3>
                <a href="create_election.php" class="btn btn-sm"><i class="fas fa-plus"></i> Create New Election</a>
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
            </div>
            <?php else: ?>
                 <div class="empty-state">
                     <p>No elections found.</p>
                     <a href="create_election.php" class="btn btn-sm"><i class="fas fa-plus"></i> Create Your First Election</a>
                 </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
