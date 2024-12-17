<?php
    $host = 'localhost';
    $db = 'VerseWise';
    $user = 'root';
    $password = '';

    $conn = new mysqli($host, $user, $password, $db);
    if($conn->connect_error){
        echo "connection failed: " . $conn->connect_error;
    }
?>