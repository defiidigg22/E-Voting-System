<?php
session_start(); // Start session to potentially use user info later if needed
require_once 'config.php'; // Use mysqli connection $conn

// Initialize variables
$elections_list = [];
$selected_election = null;
$results = [];
$total_votes = 0;
$election_id = 0;
$db_error = null;

// Check DB connection
if ($conn && !$conn->connect_error) {
    try {
        // 1. Fetch COMPLETED or ARCHIVED elections for the dropdown
        // Users should only see results for finished elections
        $sql_elections = "SELECT election_id, title, end_datetime FROM elections
                          WHERE status = 'completed' OR status = 'archived'
                          ORDER BY end_datetime DESC";
        $result_elections = $conn->query($sql_elections);
        if ($result_elections === false) { throw new Exception("Error fetching elections list: " . $conn->error); }
        while ($row = $result_elections->fetch_assoc()) {
            $elections_list[] = $row;
        }

        // 2. Check if an election has been selected via GET parameter
        if (isset($_GET['election_id']) && is_numeric($_GET['election_id'])) {
            $election_id = intval($_GET['election_id']);

            // 3. Fetch details of the selected election (and verify it's completed/archived)
            $stmt_election = $conn->prepare("SELECT * FROM elections WHERE election_id = ? AND (status = 'completed' OR status = 'archived')");
             if ($stmt_election === false) { throw new Exception("Prepare statement failed: " . $conn->error); }
            $stmt_election->bind_param("i", $election_id);
            $stmt_election->execute();
            $result_election = $stmt_election->get_result();
            $selected_election = $result_election->fetch_assoc();
            $stmt_election->close();

            // 4. If a valid completed/archived election is selected, fetch its results
            if ($selected_election) {
                // Fetch vote counts per candidate
                $stmt_results = $conn->prepare("
                    SELECT c.name, c.party, COUNT(v.vote_id) as vote_count
                    FROM candidates c
                    LEFT JOIN votes v ON c.candidate_id = v.candidate_id AND v.election_id = ?
                    WHERE c.election_id = ?
                    GROUP BY c.candidate_id
                    ORDER BY vote_count DESC, c.name ASC
                ");
                 if ($stmt_results === false) { throw new Exception("Prepare statement failed: " . $conn->error); }
                $stmt_results->bind_param("ii", $election_id, $election_id);
                $stmt_results->execute();
                $result_data = $stmt_results->get_result();
                while ($row = $result_data->fetch_assoc()) {
                    $results[] = $row;
                }
                $stmt_results->close();

                // Calculate total votes cast in this election
                $total_votes = array_sum(array_column($results, 'vote_count'));
            } else {
                 $election_id = 0; // Reset ID if election not found or not completed/archived
                 $db_error = "Selected election not found or results are not yet available.";
            }
        }

    } catch (Exception $e) {
        $db_error = "Database Error: " . $e->getMessage();
        // error_log($db_error); // Log error for debugging
    }
} else {
     $db_error = "Database connection error.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - E-Voting</title>
    <link rel="stylesheet" href="styles.css"> <style>
        /* Add styles similar to dashboard or select_election */
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f4f7fc; color: #333; }
        header { background: #0056b3; color: white; padding: 15px 0; text-align: center; font-size: 1.5em; }
        nav { display: flex; justify-content: center; background: #003d80; padding: 10px 0; flex-wrap: wrap; }
        nav a { color: white; text-decoration: none; margin: 5px 15px; font-size: 1.1em; }
        nav a:hover { color: #ffcc00; }
        .container { width: 90%; max-width: 900px; margin: 30px auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #0056b3; margin-bottom: 25px; }
        .election-selector { margin-bottom: 2rem; text-align: center; }
        .election-selector label { font-weight: bold; margin-right: 0.5rem; }
        .election-selector select { padding: 0.6rem; font-size: 1rem; min-width: 300px; max-width: 80%; border: 1px solid #ccc; border-radius: 4px; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .results-table th, .results-table td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #eee; }
        .results-table th { background-color: #f8f9fa; font-weight: bold; }
        .results-table tr:hover { background-color: #f1f1f1; }
        .total-row td { font-weight: bold; border-top: 2px solid #ddd; background-color: #f8f9fa; }
        .message-box { text-align: center; padding: 20px; color: #555; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px;}
        .error-box { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;}
        footer { background: #003d80; color: white; text-align: center; padding: 15px 0; margin-top: 40px; }
    </style>
</head>
<body>
    <header>E-Voting System</header>
     <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="select_election.php">Vote</a>
        <a href="results.php">Results</a>
        <a href="logout.php">Logout</a>
        </nav>

    <div class="container">
        <h2>Election Results</h2>

        <?php if ($db_error): ?>
            <div class="error-box">
                <p><?php echo htmlspecialchars($db_error); ?></p>
            </div>
        <?php endif; ?>

        <div class="election-selector">
            <form method="GET" action="results.php" id="electionSelectFormRes">
                <label for="election_id">Select Election:</label>
                <select id="election_id" name="election_id" onchange="document.getElementById('electionSelectFormRes').submit();">
                    <option value="">-- Select Completed Election --</option>
                    <?php foreach ($elections_list as $election_item): ?>
                        <option value="<?php echo $election_item['election_id']; ?>"
                            <?php echo ($election_id == $election_item['election_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($election_item['title']); ?> (Ended: <?php echo date('M j, Y', strtotime($election_item['end_datetime'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" style="margin-left: 10px; padding: 0.6rem 1rem; font-size: 1rem;">View Results</button></noscript>
            </form>
        </div>

        <?php if ($selected_election): ?>
            <h3>Results for: <?php echo htmlspecialchars($selected_election['title']); ?></h3>
            <p>Total Votes Cast: <?php echo $total_votes; ?></p>

            <?php if (!empty($results)): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Party</th>
                            <th>Votes Received</th>
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
                         <tr class="total-row">
                             <td colspan="2">Total</td>
                             <td><?php echo $total_votes; ?></td>
                             <td><?php echo ($total_votes > 0) ? '100.00%' : '0.00%'; ?></td>
                         </tr>
                    </tbody>
                </table>
            <?php else: ?>
                 <div class="message-box">
                    <p>No votes were recorded for this election, or no candidates were available.</p>
                 </div>
            <?php endif; ?>

        <?php elseif ($election_id == 0 && !$db_error): ?>
             <div class="message-box">
                <p>Please select an election from the dropdown above to view its results.</p>
             </div>
        <?php endif; ?>

    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> E-Voting System. All Rights Reserved.
    </footer>

</body>
</html>