<?php
// LoginController.php
// This file is responsible for handling user login requests for our API.

// We need to connect to the database, so we include our connection script.
// dirname(__DIR__) gets the parent directory of the current directory (which is 'api'),
// so it correctly finds the 'config' folder.
require_once dirname(__DIR__) . '/config/db_connect.php';

/**
 * Class LoginController
 * This class organizes all the functions related to user login.
 */
class LoginController {
    // A private property to hold our database connection object.
    private $con;

    /**
     * The constructor method. This is automatically called when a new LoginController object is created.
     * Its job is to set up the database connection.
     */
    public function __construct() {
        // The `$con` variable comes from our included 'db_connect.php' file.
        // We assign it to our private `$con` property so we can use it in other methods in this class.
        global $con;
        $this->con = $con;
    }

    /**
     * The login method. This function handles the actual process of authenticating a user.
     * @param string $username The username provided by the user.
     * @param string $password The plain-text password provided by the user.
     * @return array An array containing the status of the login attempt.
     */
    public function login($username, $password) {
        
        // --- Step 1: Find the user by their username ---

        // We prepare our SQL query. Using a question mark (?) as a placeholder
        // helps prevent a common web vulnerability called SQL injection.
        $stmt = $this->con->prepare("SELECT * FROM tbl_users WHERE username = ?");
        
        // We execute the prepared statement, passing the user-provided username
        // into the placeholder.
        $stmt->execute([$username]);
        
        // We fetch (get) the user's data from the database.
        // If no user is found with that username, $user will be false.
        $user = $stmt->fetch();

        // --- Step 2: Verify the password ---

        // We check if a user was found AND if the provided password matches the hashed password in the database.
        // `password_verify()` is a secure PHP function specifically for this purpose.
        if ($user && password_verify($password, $user['password'])) {
            
            // --- Step 3: Successful Login ---

            // Security Best Practice: Remove the hashed password from the user data
            // before sending it back. We don't want to expose it.
            unset($user['password']);

            // Return a success message along with the user's data.
            return [
                'status' => 'success',
                'user' => $user
            ];
        } else {
            
            // --- Step 4: Failed Login ---

            // If the user wasn't found or the password was incorrect,
            // return an error message. We use a generic message for security.
            return [
                'status' => 'error',
                'message' => 'Invalid credentials'
            ];
        }
    }
}
