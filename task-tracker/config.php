<?php
session_start();


define('DB_HOST', 'localhost:8889');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'task_tracker');

// connect to the databas
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}


function checkLogin() {  //Check if they're logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}


function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
                                //validation 
function validateDate($date) {   
    return (bool)strtotime($date);
}

function validateTime($time) {
    if (empty($time)) return true; 
    return preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $time);
}

function validateFutureDate($date) {
    return strtotime($date) >= strtotime('today');
}
?>