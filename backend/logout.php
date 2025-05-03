<?php
session_start();
require_once '../db/pdo_conn.php';
require_once 'status.php';

if (isset($_SESSION['user'])) {
    setUserStatus($_SESSION['user'], 'not active');
    unset($_SESSION['user']);
}

session_destroy();

http_response_code(200);

echo "<script>
    alert('You have successfully logged out.');
    window.location.href = '../index.html';
    </script>";

exit();

?>