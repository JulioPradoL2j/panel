<?php
session_start();

if (!file_exists('includes/config.php')) 
{
    header("Location: install/step1.php");
    exit;
}

$config = require 'includes/config.php';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    include 'dashboard.php';
} 
else 
{
    include 'templates/' . $config['theme'] . '/index.php';
}

?>
