<?php
require_once('../db/config.php');

session_start();

// Check if user is logged in and is a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    header('Location: ../html/login.html'); 
    exit();
}
//analytics data variables
$totalQuizzes = 0;
$highScore = 0;
$favorites = 0;
$highestScore = 0;

$user_id = $_SESSION['user_id'];

//Query for user high score
$stmt = $conn->prepare("SELECT highest_score AS high_Score FROM people WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if($result && $row=$result->fetch_assoc()){
    $highScore = $row['high_Score'];
}


// Query for quizzes taken by user
$stmt = $conn->prepare("SELECT COUNT(*) AS quizzes_taken FROM quiz_results WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if($result && $row=$result->fetch_assoc()){
    $totalQuizzes = $row['quizzes_taken'];
}


//Query for user favorites
$stmt = $conn->prepare("SELECT COUNT(*) AS favorites FROM user_favorites WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if($result && $row=$result->fetch_assoc()){
    $favorites = $row['favorites'];
}




$monthlyData = [];
$months = [];
for ($i = 0; $i < 6; $i++) {
    $monthNumber = date('n', strtotime("-$i month"));
    $monthName = date('M', strtotime("-$i month"));
    $months[] = $monthName;

    $stmt = $conn->prepare("SELECT COUNT(*) AS monthly_quizzes FROM quiz_results WHERE user_id = ? AND MONTH(quiz_date) = ?");
    $stmt->bind_param("ii", $user_id, $monthNumber);
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
            <li><a href="user_dashboard.php">Dashboard</a></li>
            <li><a href="../html/QuizApp.html">Quiz</a></li>
            <li><a href="../html/verse.html">Verse of The Day</a></li>
            <li><a href="favorites.php">Favorites</a></li>
            <li><a href="logout.php">Logout</a></li>
           </ul> 
        </nav>
    </header>

    <section class="analytics">
        <h2>Analytics Overview</h2>

        <div class="analytics-card">
            <h3>High Score</h3>
            <p class="number"><?php echo $highScore; ?></p>
        </div>

        <div class="analytics-card">
            <h3>Quizzes Taken</h3>
            <p class="number"><?php echo $totalQuizzes; ?></p>
        </div>

        <div class="analytics-card">
            <h3>Favorites</h3>
            <p class="number"><?php echo $favorites; ?></p>
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




