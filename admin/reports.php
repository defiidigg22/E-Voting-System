<?php
session_start();
require_once 'db_config.php'; // PDO

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// --- Fetch full admin details for sidebar ---
$stmt_admin_info = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin_info->execute([$admin_id]);
$admin = $stmt_admin_info->fetch(); // This defines the $admin variable

// Check if admin details were fetched successfully
if (!$admin) {
    session_destroy();
    header("Location: admin_login.php?error=admin_details_missing");
    exit();
}
$is_superadmin = $admin['is_superadmin'];
// --- End of admin fetch block ---


// Get elections for reports dropdown
if ($is_superadmin) {
    $elections_stmt = $pdo->query("SELECT election_id, title, start_datetime FROM elections ORDER BY start_datetime DESC");
} else {
    $elections_stmt = $pdo->prepare("SELECT election_id, title, start_datetime FROM elections WHERE created_by = ? ORDER BY start_datetime DESC");
    $elections_stmt->execute([$admin_id]);
}
$elections = $elections_stmt->fetchAll();


// --- Process selected election ---
$selected_election = null;
$vote_stats = [];
$participation_stats = ['total_voters' => 0, 'voted_count' => 0, 'not_voted_count' => 0]; // Initialize
$candidate_count = 0; // Initialize
$election_id = 0; // Initialize election_id

if (isset($_GET['election_id']) && is_numeric($_GET['election_id'])) {
    $election_id = intval($_GET['election_id']);

    // Verify election access again (good practice)
    $sql_verify_election = "SELECT * FROM elections WHERE election_id = ?";
    $params_verify = [$election_id];
    if (!$is_superadmin) {
        $sql_verify_election .= " AND created_by = ?";
        $params_verify[] = $admin_id;
    }

    $stmt_verify = $pdo->prepare($sql_verify_election);
    $stmt_verify->execute($params_verify);
    $selected_election = $stmt_verify->fetch();

    if ($selected_election) {
        // Get vote statistics per candidate
        $vote_stats_stmt = $pdo->prepare("
            SELECT c.name, c.party, COUNT(v.vote_id) as vote_count
            FROM candidates c
            LEFT JOIN votes v ON c.candidate_id = v.candidate_id AND v.election_id = ?
            WHERE c.election_id = ?
            GROUP BY c.candidate_id
            ORDER BY vote_count DESC, c.name ASC
        ");
        $vote_stats_stmt->execute([$election_id, $election_id]);
        $vote_stats = $vote_stats_stmt->fetchAll();

        // Count votes cast in this specific election
        $stmt_votes_cast = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ?");
        $stmt_votes_cast->execute([$election_id]);
        $votes_cast_count = $stmt_votes_cast->fetchColumn();
        $participation_stats['voted_count'] = $votes_cast_count;

        // Count candidates for this election
        $stmt_candidate_count = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE election_id = ?");
        $stmt_candidate_count->execute([$election_id]);
        $candidate_count = $stmt_candidate_count->fetchColumn();

    } else {
        // Reset election_id if selected election is not valid/accessible
        $election_id = 0;
         $_SESSION['error_message'] = "Selected election not found or not accessible."; // Optional message
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - E-Voting System</title>
    <link rel="stylesheet" href="../css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
                <li><a href="reports.php" class="active"><i class="fas fa-chart-pie fa-fw"></i>Reports</a></li>
                <?php if ($admin['is_superadmin']): ?>
                    <li><a href="manage_admins.php"><i class="fas fa-user-shield fa-fw"></i>Manage Admins</a></li>
                <?php endif; ?>
                <li><a href="settings.php"><i class="fas fa-cog fa-fw"></i>Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i>Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="welcome-message">Election Reports</div>
                <button class="logout-btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <div class="section-card">
                 <div class="section-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                     <h3 class="section-title" style="margin-bottom: 1rem;"><i class="fas fa-filter"></i>Select Election for Report</h3>
                 </div>
                <div class="election-selector">
                    <form method="GET" action="reports.php" id="electionSelectForm">
                        <label for="election_id">Select Election:</label>
                        <select id="election_id" name="election_id" onchange="document.getElementById('electionSelectForm').submit();">
                            <option value="">-- Select an election --</option>
                            <?php foreach ($elections as $election): ?>
                                <option value="<?php echo $election['election_id']; ?>"
                                    <?php echo ($election_id == $election['election_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($election['title']); ?> (<?php echo date('M j, Y', strtotime($election['start_datetime'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <noscript><button type="submit" class="btn btn-secondary btn-sm" style="margin-left: 10px;">Load Report</button></noscript>
                    </form>
                </div>
            </div>

            <?php if ($selected_election): ?>
                 <div class="section-card">
                     <div class="section-header">
                         <h3 class="section-title"><i class="fas fa-poll"></i>Report for: <?php echo htmlspecialchars($selected_election['title']); ?></h3>
                     </div>
                    <p><strong>Period:</strong> <?php echo date('M j, Y H:i A', strtotime($selected_election['start_datetime'])); ?> to <?php echo date('M j, Y H:i A', strtotime($selected_election['end_datetime'])); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($selected_election['status'])); ?></p>

                    <div class="stats-grid" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                         <div class="stat-card">
                            <h4>Candidates</h4>
                            <p><?php echo $candidate_count ?? 0; ?></p>
                        </div>
                        <div class="stat-card">
                            <h4>Votes Cast</h4>
                            <p><?php echo $participation_stats['voted_count'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <div class="chart-container">
                    <?php if (!empty($vote_stats)): ?>
                    <div class="chart">
                        <h4 class="chart-title">Vote Distribution by Candidate</h4>
                        <canvas id="votesChart"></canvas>
                    </div>
                    <?php else: ?>
                     <div class="section-card empty-state" style="grid-column: 1 / -1;"><p>No vote data available to display charts.</p></div>
                    <?php endif; ?>
                    </div>

                <?php if (!empty($vote_stats)): ?>
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-list-ol"></i>Detailed Vote Results</h3>
                    </div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Party</th>
                                    <th>Votes</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $total_votes = array_sum(array_column($vote_stats, 'vote_count'));
                                    foreach ($vote_stats as $stat):
                                        $percentage = $total_votes > 0 ? ($stat['vote_count'] / $total_votes) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['name']); ?></td>
                                        <td><?php echo htmlspecialchars($stat['party'] ?: 'N/A'); ?></td>
                                        <td><?php echo $stat['vote_count']; ?></td>
                                        <td><?php echo number_format($percentage, 2) . '%'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($total_votes > 0): ?>
                                <tr class="total-row">
                                    <td colspan="2">Total Votes Cast</td>
                                    <td><?php echo $total_votes; ?></td>
                                    <td>100.00%</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php elseif ($election_id > 0): // Only show if election selected but no votes ?>
                    <div class="section-card empty-state">
                        <p>No votes have been cast in this election yet, or no candidates exist.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($vote_stats)): ?>
                <script>
                    // Votes Chart (Bar Chart)
                    const votesCtx = document.getElementById('votesChart').getContext('2d');
                    const votesChart = new Chart(votesCtx, {
                        type: 'bar',
                        data: {
                            labels: [<?php
                                foreach ($vote_stats as $stat) {
                                    $label = $stat['name'] . ($stat['party'] ? ' (' . addslashes($stat['party']) . ')' : '');
                                    echo "'" . addslashes($label) . "',";
                                }
                            ?>],
                            datasets: [{
                                label: 'Votes Received',
                                data: [<?php
                                    foreach ($vote_stats as $stat) { echo $stat['vote_count'] . ","; }
                                ?>],
                                backgroundColor: 'rgba(102, 0, 255, 0.7)', // Primary color with opacity
                                borderColor: 'rgba(102, 0, 255, 1)',
                                borderWidth: 1,
                                borderRadius: 4, // Rounded bars
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y', // Horizontal bars are often better for candidate names
                            scales: {
                                x: { // Note: axes are swapped for horizontal bar
                                    beginAtZero: true,
                                    title: { display: true, text: 'Number of Votes', font: { size: 14 } },
                                    ticks: { font: { size: 12 } }
                                },
                                y: {
                                     ticks: { font: { size: 12 } }
                                }
                            },
                            plugins: {
                                legend: { display: false }, // Hide legend as label is clear
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleFont: { size: 14 },
                                    bodyFont: { size: 12 },
                                    padding: 10
                                }
                            }
                        }
                    });
                </script>
                <?php endif; ?>

            <?php elseif($election_id == 0): ?>
                <div class="section-card empty-state">
                    <p>Please select an election from the dropdown above to view its reports.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
