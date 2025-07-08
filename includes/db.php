<?php
$host = 'localhost';
$username = 'root';
$password = 'root';
$dbname = 'crud_blog';

// creat connection
$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} else {
    // echo "<script>console.log('Database connection successful');</script>";
}
