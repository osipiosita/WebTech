<?php
require_once('../db/config.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['is_favorited' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$verse_id = isset($_GET['verse_id']) ? intval($_GET['verse_id']) : 0;

if ($verse_id <= 0) {
    echo json_encode(['is_favorited' => false]);
    exit();
}

// Check if the verse is already favorited
$check_query = $conn->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND verse_id = ?");
$check_query->bind_param('ii', $user_id, $verse_id);
$check_query->execute();
$check_result = $check_query->get_result();

echo json_encode(['is_favorited' => $check_result->num_rows > 0]);

$conn->close();
?>