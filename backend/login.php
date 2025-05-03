<?php
session_start();
require_once '../db/pdo_conn.php';
require_once 'status.php'; 

$conn = getPDOConnection('outcastp_weevibes');

if (isset($_POST['login'])) {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM member WHERE username=?";
        $query = $conn->prepare($sql);
        $query->execute([$username]);
        $fetch = $query->fetch(PDO::FETCH_ASSOC);

        if ($fetch && $password === $fetch['password']) {
            $_SESSION['user'] = $fetch['id'];
            $_SESSION['username'] = $username;

            setUserStatus($fetch['id'], 'active');

            echo "<script>
                    sessionStorage.setItem('username', '" . addslashes($username) . "');
                    window.location.href = '../dashboardv5.html';
                  </script>";
        } else {
            echo "<script>
                    alert('Invalid username or password');
                    window.location.href = '../index.html';
                  </script>";
        }
    }
}
?>