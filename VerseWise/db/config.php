<?php
    $host = 'localhost';
    $user = 'joel.ackam';
    $password = 'Kh@ris123';
    $db = 'webtech_fall2024_joel_ackam';

    // conncecting to database
    $conn = new mysqli($host, $user, $password, $db);
    
    // checking connection
    if($conn->connect_error){
        die ("Database connection failed. Please try again later");
    }
?>