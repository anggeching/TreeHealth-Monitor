<?php
// Only start session if needed (e.g., for logout_status case)
// session_start(); // Uncomment if session data is strictly needed BEFORE requires
require_once '../db/pdo_conn.php';
require_once '../Models/User.php'; // Include the User model

/**
 * Updates the status of a user in the database.
 * (Keeping this function focused on ID and status for simplicity)
 *
 * @param int $userId The ID of the user to update.
 * @param string $status The new status ('active' or 'not active').
 * @return bool True on success, false on failure.
 */
function setUserStatus(int $userId, string $status): bool
{
    try {
        $conn = getPDOConnection('weevibes');
        // Ensure status is one of the expected values (basic validation)
        if ($status !== 'active' && $status !== 'not active') {
            error_log("Invalid status value passed to setUserStatus: " . $status);
            return false;
        }
        $sql = "UPDATE member SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("setUserStatus PDOException for UserID $userId: " . $e->getMessage());
        return false;
    }
}

// --- Logic based on POST data ---

$conn = null; // Initialize connection variable

// Case 1: Update status to 'active' based on username (e.g., after successful login confirmation)
if (isset($_POST['username']) && !isset($_POST['logout_on_close']) && !isset($_POST['logout_status'])) {
    $username = $_POST['username'];
    try {
        $conn = getPDOConnection('weevibes');
        $sql = "SELECT * FROM member WHERE username = :username"; // Fetch needed data
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetch) {
            // Use the User model factory method
            $user = User::fromDatabaseRow($fetch);
            // Now call setUserStatus with the ID from the model object
            if (setUserStatus($user->id, 'active')) {
                // Optional: Send a success response
                // header('Content-Type: application/json'); // Example for API
                // echo json_encode(['message' => 'Status updated successfully']);
                echo "Status updated"; // Original response
            } else {
                http_response_code(500); // Internal Server Error
                echo "Failed to update status";
            }
        } else {
            http_response_code(404); // Not Found
            echo "User not found";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Status update (active) PDOException for Username $username: " . $e->getMessage());
        echo "Database error during status update";
    }
}
// Case 2: Update status to 'not active' based on session ID (called via specific request, maybe AJAX)
// Note: Requires session_start() at the top if not already started
elseif (isset($_POST['logout_status']) && $_POST['logout_status'] === 'true') {
     session_start(); // Ensure session is started for this case
     if (isset($_SESSION['user'])) {
        $userId = $_SESSION['user'];
         if (setUserStatus($userId, 'not active')) {
              echo "Status set to not active via session";
         } else {
              http_response_code(500);
              echo "Failed to set status to not active via session";
         }
     } else {
         http_response_code(401); // Unauthorized
         echo "User session not found for status update";
     }
}
// Case 3: Update status to 'not active' based on username (e.g., window close event)
elseif (isset($_POST['username']) && isset($_POST['logout_on_close']) && $_POST['logout_on_close'] === 'true') {
    $username = $_POST['username'];
     try {
        $conn = getPDOConnection('weevibes');
        $sql = "SELECT id FROM member WHERE username = :username"; // Only need ID here
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        // No need for full User model here, just need the ID
        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetch && isset($fetch['id'])) {
            $userId = $fetch['id'];
            if (setUserStatus($userId, 'not active')) {
                 // No output usually needed for background tasks like this
                 http_response_code(200); // OK
            } else {
                 http_response_code(500); // Internal Server Error
            }
        } else {
            // User not found, maybe already deleted or username changed?
            http_response_code(404); // Not Found
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Status update (not active/close) PDOException for Username $username: " . $e->getMessage());
        // No output needed usually
    }
}
// Optional: Handle cases where none of the conditions match
// else {
//     http_response_code(400); // Bad Request
//     echo "Invalid request parameters for status update.";
// }

// Close connection if it was opened
$conn = null;
?>