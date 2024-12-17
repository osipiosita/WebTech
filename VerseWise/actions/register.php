<?php

require_once('../db/config.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    $confirm_password = $conn->real_escape_string($_POST['confirm-password']);

    $errors = [];
    if (empty($firstname)) $errors[] = "First name required";
    if (empty($lastname)) $errors[] = "Last name required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM people WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) $errors[] = "Email already registered";
    $stmt->close();

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $statement = $conn->prepare("INSERT INTO people (fname, lname, email, password, created_at) VALUES (?, ?, ?, ?,NOW())");
        $statement->bind_param("ssss", $firstname, $lastname, $email, $hashed_password);

        if ($statement->execute()) {
            echo json_encode(["success" => true, "message" => "Registration successful!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Registration failed: " . $statement->error]);
        }
    } else {
        echo json_encode(["success" => false, "errors" => $errors]);
    }

    $conn->close();
    exit(); // End script execution after handling POST request
}
?> 



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VerseWise - Register</title>
    <link rel="icon" href="https://img.icons8.com/?size=100&id=37401&format=png&color=000000" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/forms.css">
    <script src="../js/register.js" defer></script>  
    
</head>
<body>
    <div class="sign-up">
        <h1>VerseWise</h1>
        <h2>Register</h2>
        <form  method="POST" id="registration-form">
            <div class="input-group">
                <label for="firstname">First Name</label>
                <input type="text" name="firstname" id="firstname"  required>
            </div>
            <div class="input-group">
                <label for="lastname">Last Name</label>
                <input type="text" name="lastname" id="lastname"  required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="input-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" name="confirm-password" id="confirm-password" required>
            </div>
            <button type="submit" id="register-button">Register</button>
        </form>
        <p>Already have an account? <a href="../html/login.html">Log in</a></p>
        <div id="success" class="message success">Registration Successful</div>
        <div id="error-messages" class="message error"></div>
    </div>
</body>
</html>