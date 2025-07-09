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
if (!$admin) {
    session_destroy(); header("Location: admin_login.php?error=admin_details_missing"); exit();
}
$is_superadmin = $admin['is_superadmin'];

// Get IDs from URL
$candidate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;

// Verify candidate exists and belongs to an election the admin can manage
$candidate_data = null;
$election_title = '';
if ($candidate_id > 0 && $election_id > 0) {
    $query = "SELECT c.*, e.title as election_title, e.created_by
              FROM candidates c
              JOIN elections e ON c.election_id = e.election_id
              WHERE c.candidate_id = ? AND c.election_id = ?"; // Ensure candidate belongs to the specified election
    $params = [$candidate_id, $election_id];

    // Add ownership check if not superadmin
    if (!$is_superadmin) {
        $query .= " AND e.created_by = ?";
        $params[] = $admin_id;
    }

    $stmt_verify = $pdo->prepare($query);
    $stmt_verify->execute($params);
    $candidate_data = $stmt_verify->fetch();

    if (!$candidate_data) {
        $_SESSION['error_message'] = "Candidate not found or unauthorized.";
        // Redirect back to the list for that election if election_id is known, otherwise to general election list
        header("Location: " . ($election_id > 0 ? "manage_candidates.php?election_id=$election_id" : "manage_elections.php"));
        exit();
    }
    $election_title = $candidate_data['election_title'];
} else {
     $_SESSION['error_message'] = "Invalid candidate or election ID specified.";
     header("Location: " . ($election_id > 0 ? "manage_candidates.php?election_id=$election_id" : "manage_elections.php"));
     exit();
}

// Initialize form variables
$errors = [];
$name = $candidate_data['name'];
$party = $candidate_data['party'];
$bio = $candidate_data['bio'];
$current_photo = $candidate_data['photo_url'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $party = trim($_POST['party']);
    $bio = trim($_POST['bio']);
    // --- TODO: Handle photo upload/update ---
    $photo_url = $current_photo; // Placeholder - update if new photo uploaded & replace old one

    if (empty($name)) {
        $errors[] = "Candidate name is required.";
    }
    // Add more validation

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE candidates SET name = ?, party = ?, bio = ?, photo_url = ? WHERE candidate_id = ? AND election_id = ?");
            // Ensure update only happens for the correct election_id as well
            $stmt->execute([$name, $party, $bio, $photo_url, $candidate_id, $election_id]);

            // Log action (optional)
            /* ... logging code ... */

            $_SESSION['success_message'] = "Candidate '" . htmlspecialchars($name) . "' updated successfully!";
            header("Location: manage_candidates.php?election_id=$election_id");
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
    <title>Edit Candidate - <?php echo htmlspecialchars($candidate_data['name']); ?></title>
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
        .form-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 16px 0 rgba(99,102,241,0.08);
            padding: 2rem 2rem 1.5rem 2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        .form-header h2 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #6366f1;
        }
        .form-header .sub-title {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        .form-group label {
            font-weight: 500;
            color: #334155;
        }
        .form-group input, .form-group textarea, .form-group select {
            border-radius: 0.5rem;
        }
        .form-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        .btn {
            border-radius: 0.5rem;
        }
        .alert {
            max-width: 600px;
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
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt fa-fw"></i>Dashboard</a></li>
            <li><a href="manage_elections.php"><i class="fas fa-box-archive fa-fw"></i>Manage Elections</a></li>
            <li><a href="manage_candidates.php" class="active"><i class="fas fa-users fa-fw"></i>Manage Candidates</a></li>
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
            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="welcome-message">Edit Candidate</div>
            <button class="btn btn-outline-danger btn-sm" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
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
                <h2><i class="fas fa-user-edit"></i> Candidate Details</h2>
                <div class="sub-title">Election: <?php echo htmlspecialchars($election_title); ?></div>
            </div>
            <form method="POST" action="edit_candidate.php?id=<?php echo $candidate_id; ?>&election_id=<?php echo $election_id; ?>" enctype="multipart/form-data">
                <div class="form-group mb-3">
                    <label for="name">Candidate Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="party">Party / Affiliation (Optional)</label>
                    <input type="text" class="form-control" id="party" name="party" value="<?php echo htmlspecialchars($party); ?>">
                </div>
                <div class="form-group mb-3">
                    <label for="bio">Biography / Statement (Optional)</label>
                    <textarea class="form-control" id="bio" name="bio"><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
                <div class="form-group mb-3">
                    <label for="photo">Photo (Optional)</label>
                    <?php if ($current_photo): ?>
                        <p style="margin-top:0; margin-bottom: 5px;"><small>Current photo:</small></p>
                        <img src="../uploads/candidate_photos/<?php echo htmlspecialchars($current_photo); ?>" alt="Current photo" class="current-photo mb-2" style="max-width:120px; border-radius:8px; box-shadow:0 2px 8px #e0e7ff;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <p style="display:none; color: var(--text-light);"><small>Could not load current photo.</small></p>
                        <small>Upload a new photo to replace the current one.</small>
                    <?php else: ?>
                        <small>No current photo. Upload one if desired.</small>
                    <?php endif; ?>
                    <input type="file" class="form-control mt-2" id="photo" name="photo" accept="image/jpeg, image/png, image/gif">
                    <small>Max size: 2MB. JPG, PNG, GIF allowed.</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Candidate</button>
                    <a href="manage_candidates.php?election_id=<?php echo $election_id; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
