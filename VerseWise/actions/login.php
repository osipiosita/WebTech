<?php
// Ensure no errors are output to the response
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Start session
session_start();

require_once '../db/config.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract email and password with safe fallbacks
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? htmlspecialchars(trim($_POST['password'])) : '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // Query the database for the user
    $stmt = $conn->prepare("SELECT id, fname, lname, password, role FROM people WHERE email = ?");
    if (!$stmt) {
        error_log('Database error: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_fname'] = $user['fname'];
            $_SESSION['user_lname'] = $user['lname'];
            $_SESSION['user_role'] = $user['role'];

            // Determine redirect based on role
            $redirect = $user['role'] === 'admin' ? '../actions/admin_dashboard.php' : '../actions/user_dashboard.php';

            echo json_encode(['success' => true, 'message' => 'Login successful.', 'redirect' => $redirect]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
    exit;
}
?>