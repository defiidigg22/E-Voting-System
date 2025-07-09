<?php
session_start(); // Assume session is already started
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pin'])) {
    $entered_pin = trim($_POST['pin']);
    // Compare entered PIN with session PIN
    if (isset($_SESSION['pin']) && $entered_pin === $_SESSION['pin']) {
        // PIN Correct: Set verification flag and redirect
        $_SESSION['pin_verified'] = true;
        header("Location: select_election.php"); // Redirect to next step
        exit();
    } else {
        // PIN Incorrect: Set error message
        $error = "Incorrect PIN. Please try again.";
        unset($_SESSION['pin_verified']); // Ensure flag is unset
    }
}
?>
<?php
$has_voted = false;
$error_check = '';
try {
    // Prepare SQL to count existing votes for this voter in this election
    $sql_check_voted = "SELECT COUNT(*) as vote_count FROM votes WHERE voter_id = ? AND election_id = ?";
    $stmt_check_voted = $conn->prepare($sql_check_voted);
    if ($stmt_check_voted) {
        // Bind parameters (voter_id, election_id)
        $stmt_check_voted->bind_param("ii", $voter_id, $election_id);
        $stmt_check_voted->execute();
        // Get the result
        $result_voted = $stmt_check_voted->get_result();
        $voted_data = $result_voted->fetch_assoc();
        $stmt_check_voted->close();
        // If count > 0, the user has already voted
        if ($voted_data['vote_count'] > 0) {
            $has_voted = true;
        }
    } else {
        throw new Exception("Database prepare error: " . $conn->error);
    }
} catch (Exception $e) {
    $error_check = "Error checking vote status: " . $e->getMessage();
    // In a real app, log this error: error_log($error_check);
}
if ($has_voted) {
    // echo "You have already voted in this election.";
}
?>
<?php
$error_record = '';
$message_record = '';
$conn->begin_transaction();
try {
    $sql_insert_vote = "INSERT INTO votes (election_id, voter_id, candidate_id, voted_at, ip_address) VALUES (?, ?, ?, NOW(), ?)";
    $stmt_insert = $conn->prepare($sql_insert_vote);
    if ($stmt_insert) {
        $ip_address = $_SERVER['REMOTE_ADDR']; // Get voter's IP address
        $stmt_insert->bind_param("iiis", $election_id, $voter_id, $selected_candidate_id, $ip_address);
        if(!$stmt_insert->execute()) {
             if ($conn->errno == 1062) {
                 throw new Exception("Duplicate vote detected.", 1062);
             } else {
                 throw new Exception("Vote Insert Execute Error: ".$stmt_insert->error);
             }
        }
        $stmt_insert->close();
        $conn->commit();
        $message_record = "Vote recorded successfully!";   
    } else {
         throw new Exception("Database prepare error: " . $conn->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    $error_record = "Failed to record vote: " . $e->getMessage();
}
?>
<?php
try {
    // Prepare the SQL query with a placeholder (?) for the admin ID
    $sql = "SELECT election_id, title, status, start_datetime, end_datetime
            FROM elections
            WHERE created_by = ?
            ORDER BY start_datetime DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id]);
    $admin_elections = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error fetching elections: " . $e->getMessage();
    $admin_elections = []; // Ensure it's an empty array on error
}

?>
