<?php
class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUserByUsernameOrEmail($usernameOrEmail) {
        try {
            $query = "SELECT id, username, email, password FROM users WHERE username = :usernameOrEmail OR email = :usernameOrEmail";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usernameOrEmail', $usernameOrEmail);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC); // Returns an associative array of the user data or false
        } catch (PDOException $e) {
            // Log the error (recommended)
            error_log("Database Error: " . $e->getMessage());
            return false; // Return false to indicate an error
        }
    }

    public function createUser($username, $email, $password) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->execute();
            return true; // Return true on success
        } catch (PDOException $e) {
            // Log the error
            error_log("Database Error: " . $e->getMessage());
            return false; // Return false on error
        }
    }

    public function checkUserExists($username, $email) {
        try{
            $query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
             error_log("Database Error: " . $e->getMessage());
             return false;
        }

    }
}
?>