<?php
session_start();
require_once 'db_config.php'; // PDO

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$election_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get admin info
$stmt_admin = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin->execute([$admin_id]);
$admin = $stmt_admin->fetch();

if (!$admin) {
    // Handle error: maybe redirect to login or show error message
    session_destroy();
    header("Location: admin_login.php?error=admin_details_missing");
    exit();
}
$is_superadmin = $admin['is_superadmin'];

// Get election details & Verify access
$election = null;
if ($election_id > 0) {
     // Superadmin can view any election
     if ($is_superadmin) {
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE election_id = ?");
        $stmt->execute([$election_id]);
    } else {
        // Normal admin can only view elections they created
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE election_id = ? AND created_by = ?");
        $stmt->execute([$election_id, $admin_id]);
    }
    $election = $stmt->fetch();
}

// Redirect if election not found or not accessible
if (!$election) {
    $_SESSION['error_message'] = "Election not found or you don't have permission to view it.";
    header("Location: manage_elections.php"); // Redirect to election list
    exit();
}

// --- Fetch related data (counts, etc.) for display ---

// Count Candidates for this election
$stmt_cand_count = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE election_id = ?");
$stmt_cand_count->execute([$election_id]);
$candidate_count = $stmt_cand_count->fetchColumn();

// *** REMOVED: Voter Count query using election_voters ***
// $stmt_voter_count = $pdo->prepare("SELECT COUNT(*) FROM election_voters WHERE election_id = ?");
// $stmt_voter_count->execute([$election_id]);
// $voter_count = $stmt_voter_count->fetchColumn();

// Count Votes Cast for this election
$stmt_vote_count = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ?");
$stmt_vote_count->execute([$election_id]);
$votes_cast_count = $stmt_vote_count->fetchColumn();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Election: <?php echo htmlspecialchars($election['title']); ?></title>
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
                <li><a href="manage_elections.php" class="active">Manage Elections</a></li> <li><a href="manage_candidates.php">Manage Candidates</a></li>
                <li><a href="manage_voters.php">Manage Voters</a></li>
                <li><a href="reports.php">Reports</a></li>
                <?php if ($admin['is_superadmin']): ?>
                    <li><a href="manage_admins.php">Manage Admins</a></li>
                <?php endif; ?>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
             <div class="header">
                <div class="welcome-message">
                   Election Details
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
            </div>

             <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <div class="section">
                 <h3><?php echo htmlspecialchars($election['title']); ?></h3>
                 <dl class="details-grid">
                     <dt>Description:</dt>
                     <dd><?php echo nl2br(htmlspecialchars($election['description'] ?: 'N/A')); ?></dd>

                     <dt>Start Date & Time:</dt>
                     <dd><?php echo date('M j, Y H:i A', strtotime($election['start_datetime'])); ?></dd>

                     <dt>End Date & Time:</dt>
                     <dd><?php echo date('M j, Y H:i A', strtotime($election['end_datetime'])); ?></dd>

                     <dt>Status:</dt>
                     <dd><?php echo ucfirst(htmlspecialchars($election['status'])); ?></dd>

                     <dt>Visibility:</dt>
                     <dd><?php echo $election['is_public'] ? 'Public' : 'Private'; ?></dd>

                     <dt>Created On:</dt>
                     <dd><?php echo date('M j, Y H:i A', strtotime($election['created_at'])); ?></dd>
                 </dl>

                 <div class="stats-grid">
                     <div class="stat-card">
                        <h4>Candidates</h4>
                        <p><?php echo $candidate_count; ?></p>
                     </div>
                     <div class="stat-card">
                        <h4>Votes Cast</h4>
                        <p><?php echo $votes_cast_count; ?></p>
                     </div>
                     </div>

                 <div class="actions">
                     <a href="edit_election.php?id=<?php echo $election_id; ?>" class="btn">Edit Election</a>
                     <a href="manage_candidates.php?election_id=<?php echo $election_id; ?>" class="btn btn-secondary">Manage Candidates</a>
                     <a href="reports.php?election_id=<?php echo $election_id; ?>" class="btn btn-secondary">View Reports</a>
                     <a href="manage_elections.php" class="btn btn-secondary">Back to Elections List</a>
                 </div>
            </div>

        </div>
    </div>
</body>
</html>