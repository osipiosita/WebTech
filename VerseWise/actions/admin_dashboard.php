<?php
require_once('../db/config.php');

session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../html/login.html'); // Redirect to login page if not logged in or not a super admin
    exit();
}
//analytics data variables
$totalUsers = 0;
$totalQuizzes = 0;
$totalVerses = 0;
$highestScore = 0;

//Query for total number of users
$result = $conn->query("SELECT COUNT(*) AS total_users FROM people");
if($result && $row=$result->fetch_assoc()){
    $totalUsers = $row['total_users'];
}

$result = $conn->query("SELECT MAX(highest_score) AS highest_score FROM people");
if($result && $row = $result->fetch_assoc()){
    $highestScore = $row['highest_score'];
}

//Query for total number of quiz questions
$result = $conn->query("SELECT COUNT(*) AS total_quizzes FROM quizzes");
if($result && $row = $result->fetch_assoc()){
    $totalQuizzes = $row['total_quizzes'];
}

//Query total verses
$result = $conn->query("SELECT COUNT(*) AS total_verses FROM verses");
if($result && $row = $result->fetch_assoc()){
    $totalVerses = $row['total_verses'];
}

$monthlyData = [];
$months = [];
for ($i = 0; $i < 6; $i++) {
    $monthNumber = date('n', strtotime("-$i month"));
    $monthName = date('M', strtotime("-$i month"));
    $months[] = $monthName;

    $stmt = $conn->prepare("SELECT COUNT(*) AS monthly_quizzes FROM quiz_results WHERE MONTH(quiz_date) = ?");
    $stmt->bind_param("i", $monthNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $monthlyData[] = (int)$row['monthly_quizzes'];
    } else {
        $monthlyData[] = 0;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="https://img.icons8.com/?size=100&id=37401&format=png&color=000000" type="image/png">

</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="users.php">User Management</a></li>
                <li><a href="verse_management.php">Verse Management</a></li>
                <li><a href="quiz_management.php">Quiz Management</a></li>
                <li><a href="logout.php">Logout</a></li>


            </ul>
        </nav>
    </header>

    <section class="analytics">
        <h2>Analytics Overview</h2>

        <div class="analytics-card">
            <h3>Total Users</h3>
            <p class="number"><?php echo $totalUsers; ?></p>
        </div>

        <div class="analytics-card">
            <h3>Total Quizzes</h3>
            <p class="number"><?php echo $totalQuizzes; ?></p>
        </div>

        <div class="analytics-card">
            <h3>Total Verses</h3>
            <p class="number"><?php echo $totalVerses; ?></p>
        </div>

        <div class="chart">
            <h3>Quizzes Taken Per Month</h3>
            <canvas id="quizChart"></canvas>
        </div>
    </section>

    <script>
        // Data for recipes created per month from PHP
        const monthlyData = <?php echo json_encode($monthlyData); ?>;

        // Chart configuration
        const config = {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Quizzes Taken',
                    data: monthlyData,
                    backgroundColor: 'rgba(34, 163, 232, 0.7)',
                    borderColor: 'rgba(34, 163, 232, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Quizzes' }
                    },
                    x: {
                        title: { display: true, text: 'Month' }
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top' }
                }
            }
        };

        // Render the chart in the canvas
        const ctx = document.getElementById('quizChart').getContext('2d');
        new Chart(ctx, config);
    </script>
</body>
</html>

