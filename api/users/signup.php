<?php
session_start();
require_once '../../db/pdo_conn.php';
require_once '../../Models/User.php'; 

$conn = getPDOConnection('weevibes');

if (isset($_POST['signup'])) {
    // Basic validation: Check if required fields are not empty
    if (
        !empty($_POST['full_name']) &&
        !empty($_POST['email']) &&
        !empty($_POST['username']) &&
        !empty($_POST['password'])
    ) {
        try {
            $user = new User(
                null, // ID is null initially
                $_POST['full_name'],
                $_POST['email'],
                $_POST['username'],
                $_POST['password'] // Store the plain password temporarily
            );

            // Set initial status
            $user->status = 'not active';

            // --- Security Enhancement: Hash the Password ---
            $hashedPassword = password_hash($user->password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                // Handle hashing error
                throw new Exception("Password hashing failed.");
            }
            $user->password = $hashedPassword; // Update the user object with the HASH

            // Prepare SQL statement with named placeholders
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO member (full_name, email, username, password, status)
                    VALUES (:full_name, :email, :username, :password, :status)";
            $stmt = $conn->prepare($sql);

            // Bind parameters from the User object's properties
            $stmt->bindParam(':full_name', $user->full_name);
            $stmt->bindParam(':email', $user->email);
            $stmt->bindParam(':username', $user->username);
            $stmt->bindParam(':password', $user->password); // Bind the HASHED password
            $stmt->bindParam(':status', $user->status);

            // Execute the statement
            $stmt->execute();

            // Close connection (optional here as script ends)
            $conn = null;

            // Success feedback
            echo "
                <script>
                    alert('Registration successful! You can now log in.');
                    window.location.href = '../../index.html';
                </script>
            ";
            exit();

        } catch (PDOException $e) {
            // Check for unique constraint violation (duplicate email/username)
            if ($e->getCode() == 23000) { // 23000 is the SQLSTATE code for integrity constraint violation
                echo "
                    <script>
                        alert('Error: The email or username is already registered.');
                        window.location.href = '../pages/signup.html'; // Redirect back to signup
                    </script>
                ";
            } else {
                // Other database errors
                error_log("Signup PDOException: " . $e->getMessage()); // Log error for debugging
                echo "
                    <script>
                        alert('An error occurred during registration. Please try again later.');
                        window.location.href = '../../pages/signup.html';
                    </script>
                ";
            }
            exit();
        } catch (Exception $e) {
             // Other general errors (like password hashing failure)
             error_log("Signup Exception: " . $e->getMessage()); // Log error
             echo "
                 <script>
                     alert('An unexpected error occurred. Please try again later.');
                     window.location.href = '../../pages/signup.html';
                 </script>
             ";
             exit();
        }

    } else {
        // Handle case where required fields are empty
        echo "
            <script>
                alert('Please fill in all required fields.');
                window.location.href = '../../index.html'; // Redirect back to signup
            </script>
        ";
        exit();
    }
} else {
     // Handle case where 'signup' is not set in POST (e.g., direct access)
     // Optional: Redirect or show an error
     header('Location: ../../index.html');
     exit();
}
?>