<?php
session_start();

$host = 'localhost';
$dbname = 'education_portal';
$username = 'root'; // Replace with your MySQL username
$password = '';     // Replace with your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isFaculty() {
    return isLoggedIn() && $_SESSION['role'] === 'faculty';
}

function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function sendNotification($pdo, $message, $recipient_role, $created_by) {
    $stmt = $pdo->prepare("INSERT INTO notifications (message, recipient_role, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$message, $recipient_role, $created_by]);
}
?>