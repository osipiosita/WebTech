<?php
require_once('../db/config.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../html/login.html');
    exit();
}

// Fetch all valid verse IDs from the database
$sql = "SELECT id FROM verses";
$result = $conn->query($sql);

$verse_ids = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $verse_ids[] = $row['id'];
    }
} else {
    die("No verses found!");
}

// Get the day of the year (1-based)
$day_of_year = date('z') + 1;

// Calculate the index of the verse for today
$index = ($day_of_year - 1) % count($verse_ids);

// Fetch the corresponding verse ID
$verse_id = $verse_ids[$index];

// Fetch the verse text and reference from the database
$query = $conn->prepare("SELECT verse_text, reference FROM verses WHERE id = ?");
if (!$query) {
    die("Failed to prepare query: " . $conn->error);
}

$query->bind_param('i', $verse_id);
$query->execute();
$query->bind_result($verse, $reference);
$query->fetch();
$query->close();

// Ensure the database connection is closed
$conn->close();

// Output the verse and reference as JSON
echo json_encode([
    'verse' => $verse,
    'reference' => $reference,
    'verse_id' => $verse_id
]);
?>
