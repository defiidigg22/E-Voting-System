<?php
session_start();
require_once 'db_config.php'; // PDO

// Check if superadmin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    header("Location: admin_login.php");
    exit();
}

$loggedInAdminId = $_SESSION['admin_id'];
$editAdminId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get logged-in admin info for sidebar
$stmt_admin_info = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt_admin_info->execute([$loggedInAdminId]);
$admin = $stmt_admin_info->fetch(); // Used for sidebar
if (!$admin) {
    session_destroy(); header("Location: admin_login.php?error=admin_details_missing"); exit();
}
// $is_superadmin = $admin['is_superadmin']; // Already confirmed true

// Fetch the admin account to be edited
$adminToEdit = null;
if ($editAdminId > 0) {
    $stmt_edit = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt_edit->execute([$editAdminId]);
    $adminToEdit = $stmt_edit->fetch();
    if (!$adminToEdit) {
        $_SESSION['error_message'] = "Admin account not found.";
        header("Location: manage_admins.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "No admin ID specified.";
    header("Location: manage_admins.php");
    exit();
}

// Initialize form variables
$errors = [];
$username = $adminToEdit['username'];
$full_name = $adminToEdit['full_name'];
$email = $adminToEdit['email'];
$is_superadmin_edit = $adminToEdit['is_superadmin']; // Fetch current superadmin status

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get current values from form
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Optional: only update if provided
    $confirm_password = $_POST['confirm_password'];
    // Only allow changing superadmin status if editing *another* admin
    $is_superadmin_edit_new = ($editAdminId != $loggedInAdminId) ? (isset($_POST['is_superadmin']) ? 1 : 0) : $adminToEdit['is_superadmin']; // Keep original if editing self


    // --- Validation ---
    if (empty($username) || empty($full_name) || empty($email)) {
        $errors[] = "Username, Full Name, and Email are required.";
    }
    if (!empty($password) && strlen($password) < 8) {
         $errors[] = "New password must be at least 8 characters long.";
    }
    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    // Check if username or email exists (excluding the current admin being edited)
    if(empty($errors)) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE (username = ? OR email = ?) AND admin_id != ?");
        $stmt_check->execute([$username, $email, $editAdminId]);
        if ($stmt_check->fetchColumn() > 0) {
             $errors[] = "Username or email already exists for another admin.";
        }
    }


    if (empty($errors)) {
        try {
             // Build the update query dynamically
            $sql = "UPDATE admins SET username = ?, full_name = ?, email = ?, is_superadmin = ?";
            $params = [$username, $full_name, $email, $is_superadmin_edit_new];

            // Add password update if provided
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password_hash = ?";
                $params[] = $password_hash;
            }

            $sql .= " WHERE admin_id = ?";
            $params[] = $editAdminId;

            $stmt_update = $pdo->prepare($sql);
            $stmt_update->execute($params);

            // Log action (optional)
            /* ... logging code ... */

            $_SESSION['success_message'] = "Admin account '" . htmlspecialchars($username) . "' updated successfully!";
            header("Location: manage_admins.php");
            exit();

        } catch (PDOException $e) {
             $errors[] = "Database error: " . $e->getMessage();
        }
    }
    // If errors, fall through to display form with errors and retained values
    $is_superadmin_edit = $is_superadmin_edit_new; // Keep potentially changed value for checkbox state
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin - <?php echo htmlspecialchars($adminToEdit['username']); ?></title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">    
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
                <li><a href="reports.php"><i class="fas fa-chart-pie fa-fw"></i>Reports</a></li>
                <?php if ($admin['is_superadmin']): // Use logged-in admin's status ?>
                    <li><a href="manage_admins.php" class="active"><i class="fas fa-user-shield fa-fw"></i>Manage Admins</a></li>
                <?php endif; ?>
                <li><a href="settings.php"><i class="fas fa-cog fa-fw"></i>Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i>Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
             <div class="header">
                <div class="welcome-message">Edit Admin Account</div>
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
                     <h2><i class="fas fa-user-edit"></i>Admin Details for: <?php echo htmlspecialchars($adminToEdit['username']); ?></h2>
                 </div>

                 <form method="POST" action="edit_admin.php?id=<?php echo $editAdminId; ?>">
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
                        <label for="password">New Password (Optional)</label>
                        <input type="password" id="password" name="password" class="password-input">
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('password')"></i>
                         <small class="password-note">Leave blank to keep the current password.</small>
                    </div>
                    <div class="form-group password-input-wrapper">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="password-input">
                         <i class="fas fa-eye password-toggle-icon" onclick="togglePasswordVisibility('confirm_password')"></i>
                    </div>

                    <?php if ($editAdminId != $loggedInAdminId): // Prevent changing own superadmin status ?>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_superadmin" value="1" <?php echo $is_superadmin_edit ? 'checked' : ''; ?>> Grant Super Admin privileges
                            </label>
                        </div>
                    <?php else: ?>
                         <div class="form-group">
                            <label>Role</label>
                            <p><strong><?php echo $is_superadmin_edit ? 'Super Admin' : 'Admin'; ?></strong> <span class="role-note">(Cannot change your own role)</span></p>
                            </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn"><i class="fas fa-save"></i> Update Admin</button>
                        <a href="manage_admins.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
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
    </script>
</body>
</html>
