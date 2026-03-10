<?php
// RegisterController.php
// This class is responsible for handling user registration requests that
// arrive via the API.  It performs validation, checks for duplicates, hashes
// passwords, and inserts the new user record into the database.

// We need access to the database connection, which is created in db_connect.php.
// `dirname(__DIR__)` moves up one directory (from controllers to api) to locate
// the config folder regardless of the current working directory.
require_once dirname(__DIR__) . '/config/db_connect.php';

class UsersController {
    // store the PDO database connection here so other methods can use it
    private $con;

    public function __construct() {
        // the global $con variable is defined in db_connect.php; capture it
        global $con;
        $this->con = $con;
    }

    /**
     * Create a new user.
     *
     * This method expects an associative array `$data` containing the form
     * fields sent by the client.  It returns an array describing the result
     * (status and message), which the router will encode as JSON.
     *
     * @param array $data Associative array containing registration fields.
     * @return array Response array with status and message (and optionally data).
     */
    public function register(array $data) {
        // ------------------------------------------------------------------
        // 1. Extract and sanitise input values.  `?? ''` ensures we don't get
        // undefined index errors if a field is missing.  `trim()` removes extra
        // whitespace that users sometimes accidentally include.
        $idno       = trim($data['idno'] ?? '');
        $fullname   = trim($data['fullname'] ?? '');
        $username   = trim($data['username'] ?? '');
        $password   = $data['password'] ?? '';
        $repassword = $data['repassword'] ?? '';
        $email      = trim($data['email'] ?? '');
        $contactno  = trim($data['contactno'] ?? '');
        $department = trim($data['department'] ?? '');
        $user_type  = trim($data['user_type'] ?? '');

        // ------------------------------------------------------------------
        // 2. Basic validation: make sure none of the required fields are empty.
        if (empty($idno) || empty($fullname) || empty($username) || empty($password) || empty($repassword)
            || empty($email) || empty($contactno) || empty($department) || empty($user_type)) {
            return ['status' => 'error', 'message' => 'All fields are required.'];
        }

        // passwords must match each other
        if ($password !== $repassword) {
            return ['status' => 'error', 'message' => 'Passwords do not match.'];
        }

        // ------------------------------------------------------------------
        // 3. Ensure username/email are unique in the database.
        $stmt = $this->con->prepare("SELECT COUNT(*) FROM tbl_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            // found a conflict
            return ['status' => 'error', 'message' => 'Username or email already in use.'];
        }

        // ------------------------------------------------------------------
        // 4. Hash the password before storing it.  `password_hash` uses a secure
        // algorithm and should be preferred over older functions like md5().
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 5. Insert the new user record.  We include `created_at` using SQL's
        // NOW() function so the database records the timestamp.
        $sql = "INSERT INTO tbl_users
                   (idno, fullname, username, password, email, contactno, department, user_type, created_at)
                VALUES
                   (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        try {
            $ins = $this->con->prepare($sql);
            $ins->execute([$idno, $fullname, $username, $hash, $email, $contactno, $department, $user_type]);

            return ['status' => 'success', 'message' => 'Registration successful.'];
        } catch (PDOException $e) {
            // In production you would log this error instead of exposing it.
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
public function getUsers() {
        try {
            $stmt = $this->con->prepare("SELECT idno, fullname, username, email, contactno, department, user_type FROM tbl_user");
            $stmt->execute();
            $users = $stmt->fetchAll();
            return ['status' => 'success', 'data' => $users];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }