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
        .form-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 2px 16px #6366f122;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            margin-bottom: 2rem;
        }
        .form-header {
            margin-bottom: 1.2rem;
        }
        .form-header h2 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #6366f1;
            margin: 0;
        }
        .btn {
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
        .btn:hover {
            background: linear-gradient(90deg, #6366f1, #a78bfa);
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            font-weight: 500;
            color: #6366f1;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.6rem 1rem;
            border-radius: 0.7rem;
            border: 1px solid #c7d2fe;
            font-size: 1rem;
            margin-top: 0.3rem;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
        }
        .alert {
            margin-bottom: 1.5rem;
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
