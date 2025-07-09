
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <link href="neon.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="neon-sidebar d-none d-md-block">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="select_election.php">Vote</a>
            <a href="results.php">Results</a>
            <a href="verify_pin.php">Verify PIN</a>
            <a href="../logout.php">Logout</a>
        </nav>
        <div class="flex-grow-1">
            <header class="neon-header">Welcome to the Voter Dashboard</header>
            <main>
                <div class="neon-card text-center">
                    <h2 style="color:#00ffe7;">Your Voting Activity</h2>
                    <p>Track your participation and see your voting stats below.</p>
                </div>
                <div class="chart-container">
                    <canvas id="voterRadarChart" width="400" height="400"></canvas>
                </div>
            </main>
        </div>
    </div>
    <script>
    // Example radar chart data (replace with real data from PHP if available)
    const ctx = document.getElementById('voterRadarChart').getContext('2d');
    const radarChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Elections Participated', 'Votes Cast', 'PIN Verifications', 'Feedback Given', 'Profile Updates'],
            datasets: [{
                label: 'Your Activity',
                data: [3, 3, 2, 1, 1], // Replace with PHP variables if available
                fill: true,
                backgroundColor: 'rgba(0,255,231,0.2)',
                borderColor: '#00ffe7',
                pointBackgroundColor: '#00ffe7',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#00ffe7'
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: { color: '#00ffe7', font: { family: 'Orbitron' } }
                }
            },
            scales: {
                r: {
                    angleLines: { color: '#00ffe7' },
                    grid: { color: '#00ffe7' },
                    pointLabels: { color: '#00ffe7', font: { family: 'Orbitron' } },
                    ticks: { color: '#fff', backdropColor: 'rgba(20,30,48,0.7)' }
                }
            }
        }
    });
    </script>
</body>
</html>
