<?php
session_start();
// This file does not need a database connection, only config
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use password_verify with the hash from config.php
    if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['loggedin'] = true;
        // Redirect to the correct dashboard URL
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        // Redirect back to the login page with an error
        header('Location: /admin/?error=1');
        exit;
    }
} else {
    // If accessed directly, redirect to login
    header('Location: /admin/');
    exit;
}
?>