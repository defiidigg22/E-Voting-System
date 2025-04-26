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
$stmt_admin_info = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin_info->execute([$admin_id]);
$admin = $stmt_admin_info->fetch();

// Check if admin data was actually fetched
if (!$admin) {
    session_destroy();
    header("Location: admin_login.php?error=admin_details_missing");
    exit();
}
$is_superadmin = $admin['is_superadmin']; // Get superadmin status

// Get election details - Verify ownership if not superadmin
$sql_get_election = "SELECT * FROM elections WHERE election_id = ?";
$params_get = [$election_id];
if (!$is_superadmin) {
    $sql_get_election .= " AND created_by = ?";
    $params_get[] = $admin_id;
}

$stmt = $pdo->prepare($sql_get_election);
$stmt->execute($params_get);
$election = $stmt->fetch();

if (!$election) {
    $_SESSION['error_message'] = "Election not found or you don't have permission to edit it.";
    header("Location: manage_elections.php");
    exit();
}

// Initialize errors array outside the POST check
$errors = [];

// Handle form submission for updating election details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $status = $_POST['status'];
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    // Validate inputs
    if (empty($title)) { $errors[] = "Title is required"; }
    if (empty($start_datetime) || empty($end_datetime)) { $errors[] = "Both start and end dates are required"; }
    elseif (strtotime($start_datetime) >= strtotime($end_datetime)) { $errors[] = "End date must be after start date"; }
    // Validate status value
    $allowed_statuses = ['draft', 'active', 'completed', 'archived'];
    if (!in_array($status, $allowed_statuses)) { $errors[] = "Invalid status selected."; }


    if (empty($errors)) {
        try {
            // Prepare the UPDATE statement with 8 placeholders
            $update_stmt = $pdo->prepare(
                "UPDATE elections SET title = ?, description = ?, start_datetime = ?, end_datetime = ?, status = ?, is_public = ?
                 WHERE election_id = ? AND ($is_superadmin = 1 OR created_by = ?)" // 8 placeholders total
            );
            $update_params = [ $title, $description, $start_datetime, $end_datetime, $status, $is_public, $election_id, $admin_id ];

            if ($update_stmt->execute($update_params)) {
                // Log the action (optional)
                /* ... logging code ... */
                $_SESSION['success_message'] = "Election details updated successfully!";
                header("Location: edit_election.php?id=$election_id");
                exit();
            } else {
                 $errors[] = "Failed to update election. Please check your input.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error during update: " . $e->getMessage();
        }
    }
}

// Get candidates for this election (for the Candidates tab)
$candidates_stmt = $pdo->prepare("SELECT * FROM candidates WHERE election_id = ? ORDER BY name ASC");
$candidates_stmt->execute([$election_id]);
$candidates = $candidates_stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Election - <?php echo htmlspecialchars($election['title']); ?></title>
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
                <?php if ($is_superadmin): ?>
                    <li><a href="manage_admins.php"><i class="fas fa-user-shield fa-fw"></i>Manage Admins</a></li>
                <?php endif; ?>
                <li><a href="settings.php"><i class="fas fa-cog fa-fw"></i>Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i>Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="welcome-message">
                    Edit Election: <?php echo htmlspecialchars($election['title']); ?>
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>

             <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
             <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="tabs">
                <div class="tab active" onclick="showTab('details')"><i class="fas fa-info-circle"></i> Details</div>
                <div class="tab" onclick="showTab('candidates')"><i class="fas fa-users"></i> Candidates</div>
                <div class="tab" onclick="showTab('results')"><i class="fas fa-poll"></i> Results</div>
            </div>

            <div id="details" class="tab-content active">
                <div class="form-card"> <form method="POST" action="edit_election.php?id=<?php echo $election_id; ?>">
                        <div class="form-group">
                            <label for="title">Election Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($election['title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($election['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="start_datetime">Start Date & Time</label>
                            <input type="datetime-local" id="start_datetime" name="start_datetime"
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($election['start_datetime'])); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="end_datetime">End Date & Time</label>
                            <input type="datetime-local" id="end_datetime" name="end_datetime"
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($election['end_datetime'])); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="draft" <?php echo $election['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="active" <?php echo $election['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $election['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="archived" <?php echo $election['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_public" value="1" <?php echo $election['is_public'] ? 'checked' : ''; ?>> Make this election public
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_details" class="btn"><i class="fas fa-save"></i> Update Details</button>
                            <a href="manage_elections.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
                        </div>
                    </form>
                </div>
            </div>

            <div id="candidates" class="tab-content">
                <div class="section-card"> <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-id-badge"></i>Candidates (<?php echo count($candidates); ?>)</h3>
                        <a href="add_candidate.php?election_id=<?php echo $election_id; ?>" class="btn btn-sm"><i class="fas fa-plus"></i> Add Candidate</a>
                    </div>
                    <?php if (!empty($candidates)): ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Party</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['party'] ?: 'N/A'); ?></td>
                                        <td>
                                            <a href="edit_candidate.php?id=<?php echo $candidate['candidate_id']; ?>&election_id=<?php echo $election_id; ?>" class="btn btn-secondary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_candidates.php?election_id=<?php echo $election_id; ?>&delete_candidate_id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this candidate?');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                       <p class="empty-state">No candidates have been added to this election yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="results" class="tab-content">
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title"><i class="fas fa-poll"></i>Election Results</h3>
                         <a href="reports.php?election_id=<?php echo $election_id; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-chart-pie"></i> View Full Report</a>
                    </div>

                    <?php
                    // Fetch results only if election is completed or archived
                    if ($election['status'] === 'completed' || $election['status'] === 'archived'):
                        $results_stmt = $pdo->prepare("
                            SELECT c.name, c.party, COUNT(v.vote_id) as vote_count
                            FROM candidates c
                            LEFT JOIN votes v ON c.candidate_id = v.candidate_id AND v.election_id = ?
                            WHERE c.election_id = ? GROUP BY c.candidate_id ORDER BY vote_count DESC, c.name ASC
                        ");
                        $results_stmt->execute([$election_id, $election_id]);
                        $results = $results_stmt->fetchAll();
                        $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ?");
                        $total_stmt->execute([$election_id]);
                        $total_votes = $total_stmt->fetchColumn();
                    ?>
                        <div class="stat-card">
                            <h3>Total Votes Cast</h3>
                            <p><?php echo $total_votes; ?></p>
                        </div>

                        <?php if (!empty($results)): ?>
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
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['party'] ?: 'N/A'); ?></td>
                                            <td><?php echo $result['vote_count']; ?></td>
                                            <td>
                                                <?php
                                                    $percentage = $total_votes > 0 ? ($result['vote_count'] / $total_votes) * 100 : 0;
                                                    echo number_format($percentage, 2) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                     <?php if ($total_votes > 0): ?>
                                    <tr style="font-weight: bold; background-color: #f8f9fa;">
                                        <td colspan="2">Total</td>
                                        <td><?php echo $total_votes; ?></td>
                                        <td>100.00%</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                             <p class="empty-state">No votes recorded for this election, or no candidates existed.</p>
                        <?php endif; ?>

                    <?php else: ?>
                        <p class="empty-state">Results will be available here once the election status is set to 'Completed' or 'Archived'.<br>Current Status: <strong><?php echo ucfirst(htmlspecialchars($election['status'])); ?></strong></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            // Deactivate all tabs
            document.querySelectorAll('.tab').forEach(tabLink => {
                tabLink.classList.remove('active');
            });
            // Activate selected tab content and link
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
        }
        // Optional: Activate tab based on hash on load
        // document.addEventListener('DOMContentLoaded', () => { /* ... hash handling ... */ });
    </script>
</body>
</html>
