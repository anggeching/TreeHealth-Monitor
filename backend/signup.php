<?php
session_start();
require_once '../db/pdo_conn.php';
$conn = getPDOConnection('outcastp_weevibes');

if (isset($_POST['signup'])) {
    if (
        !empty($_POST['full_name']) && 
        !empty($_POST['email']) && 
        !empty($_POST['username']) && 
        !empty($_POST['password'])
    ) {
        try {
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            $status = 'not active';  

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO member (full_name, email, username, password, status) 
                    VALUES (:full_name, :email, :username, :password, :status)";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':status', $status);

            $stmt->execute();

            $conn = null;

            echo "
                <script>
                    alert('Registration successful! You can now log in.');
                    window.location.href = '../index.html';
                </script>
            ";
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "
                    <script>
                        alert('Error: The email or username is already registered.');
                        window.location.href = '../index.html';
                    </script>
                ";
            }
        }
    } 
}
?>
