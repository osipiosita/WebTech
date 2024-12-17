<?php
require_once('../db/config.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$verse_id = $data['verse_id'];
$verse_text = $data['verse_text'];
$reference = $data['reference'];

// Check if the verse is already favorited
$check_query = $conn->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND verse_id = ?");
$check_query->bind_param('ii', $user_id, $verse_id);
$check_query->execute();
$check_result = $check_query->get_result();

if ($check_result->num_rows > 0) {
    // Already favorited, so remove from favorites
    $delete_query = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND verse_id = ?");
    $delete_query->bind_param('ii', $user_id, $verse_id);
    $delete_query->execute();
    
    echo json_encode(['success' => true, 'action' => 'removed']);
    exit();
}

// Add to favorites
$insert_query = $conn->prepare("INSERT INTO user_favorites (user_id, verse_id, verse_text, reference) VALUES (?, ?, ?, ?)");
$insert_query->bind_param('iiss', $user_id, $verse_id, $verse_text, $reference);

if ($insert_query->execute()) {
    echo json_encode(['success' => true, 'action' => 'added']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add favorite']);
}

$conn->close();
?>