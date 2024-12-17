<?php
session_start(); // Start the session

// Destroy all session data
$_SESSION = []; // Clear session data from memory
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Redirect to the login page (or any other page)
header("Location: ../html/login.html");
exit();
