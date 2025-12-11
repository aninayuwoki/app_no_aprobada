<?php
session_start();
// Use a robust path to include the config file
include __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['loggedin'] = true;
        // Redirect to the dashboard in the same directory
        header('Location: dashboard.php');
        exit;
    } else {
        // Redirect back to the login page with an error
        header('Location: index.php?error=1');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>