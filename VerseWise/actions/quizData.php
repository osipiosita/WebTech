<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db/config.php';

$questionsPerQuiz = 10;

// Fetch quiz data from the database
$countSql = "SELECT COUNT(*) AS total_questions FROM quizzes";
$countResult = $conn->query($countSql);
$totalQuestions = $countResult->fetch_assoc()['total_questions'];
// Randomize question selection
$sql = "SELECT question, correct_answer, JSON_UNQUOTE(choices) AS choices, reference 
        FROM quizzes 
        ORDER BY RAND() 
        LIMIT $questionsPerQuiz";
        
$result = $conn->query($sql);

// Initialize an array to store quiz data
$quizData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Decode JSON choices into an array
        $row['choices'] = json_decode($row['choices']);
        $quizData[] = $row;
    }
} else {
    echo json_encode(['error' => "No quiz data found."]);
    exit;
}

// Close the connection
$conn->close();

// Return JSON data
echo json_encode([
    'questions' => $quizData,
    'totalAvailableQuestions' => $totalQuestions
]);
exit;
?>
