<?php
session_start();
require_once '../../db/pdo_conn.php';
require_once 'status.php';
require_once '../../Models/User.php'; 

$conn = getPDOConnection('weevibes');

if (isset($_POST['login'])) {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        $username = $_POST['username'];
        $passwordInput = $_POST['password']; // User's input

        $sql = "SELECT * FROM member WHERE username = :username"; // Use named placeholders
        $query = $conn->prepare($sql);
        $query->bindParam(':username', $username);
        $query->execute();
        $fetch = $query->fetch(PDO::FETCH_ASSOC);

        if ($fetch) {
            // Create a User object from the database data
            $user = User::fromDatabaseRow($fetch);

            // !! IMPORTANT SECURITY UPDATE !!
            // Replace plain text comparison with password_verify()
            // if (password_verify($passwordInput, $user->password)) { // Assuming $user->password stores the HASH
            
            // Current insecure comparison (matches your original code)
            if ($passwordInput === $user->password) {
                $_SESSION['user'] = $user->id;
                $_SESSION['username'] = $user->username;

                setUserStatus($user->id, 'active'); // Update status

                echo "<script>
                        sessionStorage.setItem('username', '" . addslashes($user->username) . "');
                        window.location.href = '../pages/dashboard.html';
                      </script>";
                exit(); // Important to exit after redirect
            } else {
                // Invalid password
                echo "<script>
                        alert('Invalid username or password');
                        window.location.href = '../index.html';
                      </script>";
                exit();
            }
        } else {
             // Invalid username
             echo "<script>
                     alert('Invalid username or password');
                     window.location.href = '../index.html';
                   </script>";
             exit();
        }
    } else {
         // Fields empty - handle appropriately
         echo "<script>
                 alert('Please enter username and password');
                 window.location.href = '../index.html';
               </script>";
         exit();
    }
}
// Handle cases where 'login' is not set or fields are empty if needed
?>