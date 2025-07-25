<?php
session_start();
require_once 'db_config.php'; // PDO

// Check if superadmin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    header("Location: admin_login.php");
    exit();
}

$loggedInAdminId = $_SESSION['admin_id']; // Needed for sidebar

// Get logged-in admin info for sidebar
$stmt_admin_info = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin_info->execute([$loggedInAdminId]);
$admin = $stmt_admin_info->fetch(); // Used for sidebar

if (!$admin) {
    session_destroy(); header("Location: admin_login.php?error=admin_details_missing"); exit();
}
$is_superadmin = $admin['is_superadmin']; // Should be true based on check above

// Initialize form variables
$username = '';
$full_name = '';
$email = '';
$is_superadmin_new = 0; // Default to normal admin
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $is_superadmin_new = isset($_POST['is_superadmin']) ? 1 : 0;

    // Validate inputs
    if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($email)) {
        $errors[] = "All fields are required";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    if (strlen($password) < 8) { // Enforce minimum password length
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if username or email already exists (only if no other errors yet)
    if(empty($errors)) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ? OR email = ?");
        $stmt_check->execute([$username, $email]);
        $count = $stmt_check->fetchColumn();
        if ($count > 0) {
            $errors[] = "Username or email already exists";
        }
    }

    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Use the correct column name 'password' to match your DB
            $stmt_insert = $pdo->prepare("INSERT INTO admins (username, password, full_name, email, is_superadmin) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->execute([
                $username,
                $password_hash,
                $full_name,
                $email,
                $is_superadmin_new
            ]);

            // Log the action (optional)
            /* ... logging code ... */

            $_SESSION['success_message'] = "Admin account '" . htmlspecialchars($username) . "' created successfully!";
            header("Location: manage_admins.php");
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
    <title>Add Admin - E-Voting System</title>
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
            <li><a href="manage_elections.php"><i class="fas fa-box-archive fa-fw"></i>Manage Elections</a></li>
            <li><a href="manage_candidates.php"><i class="fas fa-users fa-fw"></i>Manage Candidates</a></li>
            <li><a href="manage_voters.php"><i class="fas fa-user-check fa-fw"></i>Manage Voters</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-pie fa-fw"></i>Reports</a></li>
            <?php if ($is_superadmin): ?>
                <li><a href="manage_admins.php" class="active"><i class="fas fa-user-shield fa-fw"></i>Manage Admins</a></li>
            <?php endif; ?>
            <li><a href="settings.php"><i class="fas fa-cog fa-fw"></i>Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i>Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <div class="welcome-message">Add New Admin</div>
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
                <h2><i class="fas fa-user-plus"></i>Admin Account Details</h2>
            </div>
            <form method="POST" action="add_admin.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group password-input-wrapper">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="password-input" required>
                    <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                </div>
                <div class="form-group password-input-wrapper">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="password-input" required>
                    <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('confirm_password')"></i>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_superadmin" value="1" <?php echo ($is_superadmin_new == 1) ? 'checked' : ''; ?>> Grant Super Admin privileges
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn"><i class="fas fa-user-plus"></i> Create Admin</button>
                    <a href="manage_admins.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = passwordInput.nextElementSibling; // Assumes icon is immediately after input
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
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
