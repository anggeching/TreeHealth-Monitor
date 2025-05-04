<?php

// Recommended: Place this in a 'Models' directory, e.g., /Models/User.php
// Consider using namespaces if your project grows: namespace App\Models;

/**
 * Represents a User (member) in the application.
 *
 * This class models the data structure corresponding to the 'member' table
 * in the database.
 */
class User
{
    /**
     * The unique identifier for the user.
     * Null if the user hasn't been saved to the database yet.
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The user's full name.
     * @var string|null
     */
    public ?string $full_name = null;

    /**
     * The user's email address. Should be unique.
     * @var string|null
     */
    public ?string $email = null;

    /**
     * The user's username. Should be unique.
     * @var string|null
     */
    public ?string $username = null;

    /**
     * The user's password.
     *
     * !! SECURITY WARNING !!
     * Storing plain text passwords (as suggested by the comparison '$password === $fetch['password']')
     * is highly insecure. You should use password hashing (e.g., password_hash() and password_verify()).
     * This model property stores whatever is retrieved or set, but the underlying storage and comparison
     * logic in your API should be updated for security.
     *
     * @var string|null
     */
    public ?string $password = null;

    /**
     * The user's current status (e.g., 'active', 'not active').
     * @var string|null
     */
    public ?string $status = null;

    /**
     * Constructor for the User class.
     *
     * @param int|null    $id        The user's ID (optional, usually set after DB interaction).
     * @param string|null $full_name The user's full name (optional).
     * @param string|null $email     The user's email address (optional).
     * @param string|null $username  The user's username (optional).
     * @param string|null $password  The user's password (optional).
     * @param string|null $status    The user's status (optional).
     */
    public function __construct(
        ?int $id = null,
        ?string $full_name = null,
        ?string $email = null,
        ?string $username = null,
        ?string $password = null, // Be mindful of security when handling this
        ?string $status = null
    ) {
        $this->id = $id;
        $this->full_name = $full_name;
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->status = $status;
    }

    /**
     * Factory method to create a User instance from a database row array.
     *
     * Example Usage:
     * $dbRow = $query->fetch(PDO::FETCH_ASSOC);
     * if ($dbRow) {
     * $user = User::fromDatabaseRow($dbRow);
     * }
     *
     * @param array $row An associative array representing a row from the 'member' table.
     * @return self A new User instance populated with data from the row.
     */
    public static function fromDatabaseRow(array $row): self
    {
        // Note: Password is included here because the login fetches '*',
        // but often you might want to exclude fetching/populating the password
        // unless specifically needed for verification.
        return new self(
            $row['id'] ?? null,
            $row['full_name'] ?? null,
            $row['email'] ?? null,
            $row['username'] ?? null,
            $row['password'] ?? null, // Consider security implications
            $row['status'] ?? null
        );
    }

    // --- Optional Helper Methods ---

    /**
     * Checks if the user status is 'active'.
     *
     * @return bool True if the status is 'active', false otherwise.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Converts the User object properties to an associative array.
     * Useful for API responses or serialization.
     *
     * @param bool $includePassword Whether to include the password hash in the array (default: false).
     * @return array An associative array representation of the User.
     */
    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'username' => $this->username,
            'status' => $this->status,
        ];

        if ($includePassword) {
            $data['password'] = $this->password; // Only include if explicitly requested
        }

        return $data;
    }
}