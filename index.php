<?php
// index.php - app entry point
session_start();

// If already logged in, go straight to dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

// Otherwise, go to login
header('Location: login.php');
exit();
