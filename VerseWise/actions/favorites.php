<?php
require_once('../db/config.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../html/login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's favorite verses
$query = $conn->prepare("SELECT verse_id, verse_text, reference, favorited_date 
                         FROM user_favorites 
                         WHERE user_id = ? 
                         ORDER BY favorited_date DESC");
$query->bind_param('i', $user_id);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Favorite Verses</title>
    <link rel="stylesheet" href="../css/styles.css">
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
    <div class="favcontainer">
        <h1>My Favorite Verses</h1>
        <?php if ($result->num_rows > 0): ?>
            <div class="favorites-list">
                <?php while ($favorite = $result->fetch_assoc()): ?>
                    <div class="favorite-item">
                        <blockquote>
                            <p><?php echo htmlspecialchars($favorite['verse_text']); ?></p>
                            <cite><?php echo htmlspecialchars($favorite['reference']); ?></cite>
                        </blockquote>
                        <small>Favorited on: <?php echo date('F j, Y', strtotime($favorite['favorited_date'])); ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center;">You haven't favorited any verses yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>