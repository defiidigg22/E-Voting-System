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
        .details-grid {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 0.5rem 1.5rem;
            margin-bottom: 2rem;
        }
        .stats-grid {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            flex: 1;
            text-align: center;
            color: #334155;
        }
        .actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            border-radius: 0.5rem;
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
            .stats-grid {
                flex-direction: column;
            }
            .details-grid {
                grid-template-columns: 1fr;
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
            <li><a href="manage_elections.php" class="active">Manage Elections</a></li>
            <li><a href="manage_candidates.php">Manage Candidates</a></li>
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
            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="welcome-message">Election Details</div>
            <button class="btn btn-outline-danger btn-sm" onclick="location.href='logout.php'">Logout</button>
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
                <a href="edit_election.php?id=<?php echo $election_id; ?>" class="btn btn-primary">Edit Election</a>
                <a href="manage_candidates.php?election_id=<?php echo $election_id; ?>" class="btn btn-secondary">Manage Candidates</a>
                <a href="reports.php?election_id=<?php echo $election_id; ?>" class="btn btn-secondary">View Reports</a>
                <a href="manage_elections.php" class="btn btn-secondary">Back to Elections List</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>