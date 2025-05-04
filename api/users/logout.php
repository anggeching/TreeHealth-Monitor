<?php
session_start();
require_once '../../db/pdo_conn.php';
require_once 'status.php';

// Check if user session exists before trying to update status
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user'];
    // Call the existing function to update status based on ID
    setUserStatus($userId, 'not active');
    // Unset specific session variable (optional, as session_destroy() handles it)
    // unset($_SESSION['user']);
    // unset($_SESSION['username']);
}

// Destroy all session data
session_destroy();

// Set HTTP response code (optional but good practice for APIs)
// http_response_code(200); // 200 is default on success anyway

// Provide user feedback and redirect
echo "<script>
    // sessionStorage.removeItem('username'); // Also clear client-side session storage if used
    alert('You have successfully logged out.');
    window.location.href = '../../index.html';
    </script>";

exit(); // Ensure script termination
?>