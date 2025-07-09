<?php
session_start();
require_once 'db_config.php'; // Use PDO

// Authentication check
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
    session_destroy(); header("Location: admin_login.php?error=admin_details_missing"); exit();
}
$is_superadmin = $admin['is_superadmin'];

// Get and validate election_id from GET parameter
$election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;
$election_data = null;
if ($election_id > 0) {
    // Verify access or ownership if not superadmin
    $sql_verify = "SELECT election_id, title, status FROM elections WHERE election_id = ?";
    $params_verify = [$election_id];
    if (!$is_superadmin) {
        $sql_verify .= " AND created_by = ?";
        $params_verify[] = $admin_id;
    }
    $stmt_verify = $pdo->prepare($sql_verify);
    $stmt_verify->execute($params_verify);
    $election_data = $stmt_verify->fetch();

    if (!$election_data) {
        $_SESSION['error_message'] = "Election not found or you don't have permission to manage its candidates.";
        header("Location: manage_elections.php"); // Redirect to election list
        exit();
    }
}

// Handle candidate deletion
if (isset($_GET['delete_candidate_id']) && $election_id > 0 && $election_data) { // Ensure valid election context
    $candidate_id = intval($_GET['delete_candidate_id']);

    // Permission already checked when fetching $election_data
    try {
        // Delete candidate belonging to this specific election
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE candidate_id = ? AND election_id = ?");
        if ($stmt->execute([$candidate_id, $election_id])) {
             if ($stmt->rowCount() > 0) {
                 $_SESSION['success_message'] = "Candidate deleted successfully.";
             } else {
                  $_SESSION['error_message'] = "Candidate not found in this election.";
             }
        } else {
            $_SESSION['error_message'] = "Failed to delete candidate.";
        }
    } catch (PDOException $e) {
         // Catch foreign key issues if votes exist for this candidate
         if ($e->getCode() == '23000') {
             $_SESSION['error_message'] = "Cannot delete candidate: Votes have already been cast for them.";
         } else {
             $_SESSION['error_message'] = "Database error during deletion: " . $e->getMessage();
         }
    }
    // Redirect back to the same page to refresh the list
    header("Location: manage_candidates.php?election_id=$election_id");
    exit();
}

// Get candidates if an election is selected
$candidates = [];
if ($election_id > 0 && $election_data) {
    $stmt_candidates = $pdo->prepare("
        SELECT c.*, COUNT(v.vote_id) as votes
        FROM candidates c
        LEFT JOIN votes v ON c.candidate_id = v.candidate_id AND v.election_id = ?
        WHERE c.election_id = ?
        GROUP BY c.candidate_id
        ORDER BY votes DESC, c.name ASC
    ");
    $stmt_candidates->execute([$election_id, $election_id]);
    $candidates = $stmt_candidates->fetchAll();
}

// Get elections for the dropdown filter
if ($is_superadmin) {
    $elections_stmt = $pdo->query("SELECT election_id, title FROM elections ORDER BY start_datetime DESC");
} else {
    $elections_stmt = $pdo->prepare("SELECT election_id, title FROM elections WHERE created_by = ? ORDER BY start_datetime DESC");
    $elections_stmt->execute([$admin_id]);
}
$elections_list = $elections_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates <?php echo $election_data ? '- ' . htmlspecialchars($election_data['title']) : ''; ?></title>
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
        .badge-success { background: #22c55e; color: #fff; }
        .badge-warning { background: #fbbf24; color: #fff; }
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
            <li><a href="manage_candidates.php" class="active"><i class="fas fa-users fa-fw"></i>Manage Candidates</a></li>
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
                Manage Candidates <?php echo $election_data ? ': ' . htmlspecialchars($election_data['title']) : ''; ?>
                <?php if ($election_data && isset($election_data['status'])): ?>
                    <span class="badge <?php echo $election_data['status'] === 'active' ? 'badge-success' : 'badge-primary'; ?>">
                        <?php echo ucfirst(htmlspecialchars($election_data['status'])); ?>
                    </span>
                <?php endif; ?>
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
            <div class="section-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                <h3 class="section-title"><i class="fas fa-filter"></i>Select Election</h3>
                <?php if ($election_id > 0 && $election_data): // Show Add button only if valid election selected ?>
                    <a href="add_candidate.php?election_id=<?php echo $election_id; ?>" class="btn btn-sm"><i class="fas fa-plus"></i> Add Candidate</a>
                <?php endif; ?>
            </div>
            <form method="GET" action="manage_candidates.php" class="filter-form" id="electionFilterForm">
                <label for="election_id_filter">Election:</label>
                <select name="election_id" id="election_id_filter" onchange="document.getElementById('electionFilterForm').submit();">
                    <option value="">-- Select Election --</option>
                    <?php foreach($elections_list as $election_item): ?>
                        <option value="<?php echo $election_item['election_id']; ?>" <?php echo ($election_id == $election_item['election_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($election_item['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn btn-secondary btn-sm">Filter</button></noscript>
            </form>
        </div>
        <?php if ($election_id > 0 && $election_data): // Only show table if an election is selected and valid ?>
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title"><i class="fas fa-id-badge"></i>Candidates List</h3>
                    <span class="badge badge-primary"><?php echo count($candidates); ?> Candidate(s)</span>
                </div>
                <?php if (!empty($candidates)): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Party/Affiliation</th>
                                    <th>Votes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['party'] ?? 'Independent'); ?></td>
                                        <td><?php echo $candidate['votes'] ?? 0; ?></td>
                                        <td>
                                            <a href="edit_candidate.php?id=<?php echo $candidate['candidate_id']; ?>&election_id=<?php echo $election_id; ?>" class="btn btn-secondary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_candidates.php?election_id=<?php echo $election_id; ?>&delete_candidate_id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this candidate? This cannot be undone.');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No candidates found for this election.</p>
                        <a href="add_candidate.php?election_id=<?php echo $election_id; ?>" class="btn"><i class="fas fa-plus"></i> Add First Candidate</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($election_id == 0): // If no election is selected ?>
            <div class="section-card empty-state">
                <p>Please select an election from the dropdown above to manage candidates.</p>
            </div>
        <?php endif; ?>
    </div>
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
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
