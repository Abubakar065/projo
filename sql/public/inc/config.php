<?php
$host = "127.0.0.1";
$user = "root"; // Laragon default
$pass = "";     // Laragon default (empty password)
$dbname = "project_tracker";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}