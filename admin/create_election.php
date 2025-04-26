<?php
session_start();
require_once 'db_config.php'; // PDO

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

// Optional: Check if admin data was actually fetched
if (!$admin) {
    session_destroy(); header("Location: admin_login.php?error=admin_details_missing"); exit();
}
$is_superadmin = $admin['is_superadmin']; // Needed for sidebar

// Initialize variables for form values and errors
$title = '';
$description = '';
$start_datetime = '';
$end_datetime = '';
$is_public = 1; // Default to public
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    // Validate inputs
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($start_datetime) || empty($end_datetime)) {
        $errors[] = "Both start and end dates are required";
    } elseif (strtotime($start_datetime) >= strtotime($end_datetime)) {
        $errors[] = "End date must be after start date";
    }
    // Note: Allowing past start date for creation, maybe add validation if needed:
    // elseif (strtotime($start_datetime) < time()) { $errors[] = "Start date cannot be in the past"; }


    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO elections (title, description, start_datetime, end_datetime, created_by, is_public, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')"); // Default status to draft
            $stmt->execute([
                $title,
                $description,
                $start_datetime,
                $end_datetime,
                $admin_id,
                $is_public
            ]);

            $election_id = $pdo->lastInsertId();

            // Log the action (optional)
            /*
            $logStmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$admin_id, 'create', 'Created new election: ' . $title, $_SERVER['REMOTE_ADDR']]);
            */

            $_SESSION['success_message'] = "Election created successfully! You can now add candidates and manage voters.";
            header("Location: edit_election.php?id=$election_id"); // Redirect to edit page
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Election - E-Voting System</title>
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
                <div class="welcome-message">Create New Election</div>
                <button class="logout-btn" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>

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

            <div class="form-card">
                 <div class="form-header">
                     <h2><i class="fas fa-plus-circle"></i>Election Details</h2>
                 </div>

                <form method="POST" action="create_election.php">
                    <div class="form-group">
                        <label for="title">Election Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="start_datetime">Start Date & Time</label>
                        <input type="datetime-local" id="start_datetime" name="start_datetime" value="<?php echo htmlspecialchars($start_datetime); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_datetime">End Date & Time</label>
                        <input type="datetime-local" id="end_datetime" name="end_datetime" value="<?php echo htmlspecialchars($end_datetime); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_public" value="1" <?php echo ($is_public == 1) ? 'checked' : ''; ?>> Make this election public (visible to all logged-in users)
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn"><i class="fas fa-save"></i> Create Election</button>
                        <a href="manage_elections.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
