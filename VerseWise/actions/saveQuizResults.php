<?php 
session_start();
require_once '../db/config.php';

$data = json_decode(file_get_contents('php://input'), true);
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$userId = $_SESSION['user_id']; 
$score = intval($data['score']); // Correctly get score from JSON payload
$totalQuestions = intval($data['totalQuestions']); // Correctly get total questions from JSON payload

// Insert the quiz result into the quiz_results table
$insertQuery = "INSERT INTO quiz_results (user_id, score, total_questions) VALUES (?, ?, ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("iii", $userId, $score, $totalQuestions);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to execute insert query']);
    exit;
}

// Fetch the current highest score
$fetchHighestScoreQuery = "SELECT highest_score FROM people WHERE id = ?";
$stmt = $conn->prepare($fetchHighestScoreQuery);
$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch highest score']);
    exit;
}
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$currentHighestScore = $row['highest_score'];

// Update highest score if the new score is higher
if ($score > $currentHighestScore) {
    $updateUserQuery = "UPDATE people SET highest_score = ? WHERE id = ?";
    $stmt = $conn->prepare($updateUserQuery);
    $stmt->bind_param("ii", $score, $userId);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Failed to update highest score']);
        exit;
    }
}

// Return success response
echo json_encode(['success' => true]);
exit;
?>
