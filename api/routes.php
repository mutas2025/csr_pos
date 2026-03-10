<?php
// ============================================================================
// routes.php
// ============================================================================
// Unified RESTful API entry point for all application endpoints.
// This file acts as a single router that directs incoming API requests to
// the appropriate controller based on the resource path being requested
// (e.g. /register or /login). It's the gateway for the entire API.
// ============================================================================

// ============================================================================
// SECTION 1: DEVELOPMENT CONFIGURATION
// ============================================================================

// Enable displaying PHP errors to the user (useful while building).
// In production, you'd log errors to a file instead of showing them.
ini_set('display_errors', 1);

// Also display errors that occur during PHP startup (extension loading, etc.).
ini_set('display_startup_errors', 1);

// Report all kinds of errors (warnings, notices, deprecations, etc.).
// E_ALL is the highest level of error reporting.
error_reporting(E_ALL);

// ============================================================================
// SECTION 2: HTTP HEADERS
// ============================================================================

// Tell web browsers and API clients that every response from this script
// will be in JSON format. This header is important so clients know how to
// parse the response.
header('Content-Type: application/json');

// ============================================================================
// SECTION 3: LOAD DEPENDENT CLASSES
// ============================================================================

// Include the RegisterController class. This class handles all the logic
// for creating new user accounts, including validation and database insertion.
require_once __DIR__ . '/controllers/UsersController.php';

// Include the LoginController class. This class handles all the logic
// for authenticating users (verifying username and password).
require_once 'controllers/LoginController.php';
require_once 'controllers/ProductController.php';

// ============================================================================
// SECTION 4: PARSE THE INCOMING REQUEST
// ============================================================================

// Retrieve the HTTP method used by the client (GET, POST, PUT, DELETE, etc.).
// We'll use this to validate that they're sending data via POST.
$method = $_SERVER['REQUEST_METHOD'];

// Read the raw body of the request and decode it from JSON into a PHP array.
// file_get_contents('php://input') reads the entire request body.
// json_decode() converts it from JSON to a PHP array/object.
// The 'true' parameter says "return an array, not an object".
// The ?? [] at the end is a fallback: if decoding fails, use an empty array.
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// ============================================================================
// SECTION 5: EXTRACT THE RESOURCE PATH
// ============================================================================

// Parse the request URI to extract the resource path.
// We'll get the full path from the REQUEST_URI and strip off the base API directory.
// For example, if the client requests '/csr1/csr_pos_oberes/api/routes.php/register',
// we want to extract just '/register' so we know which resource to call.

// Use parse_url() to extract only the path part of the Uniform Resource Identifier or URI (ignoring any query string).
// For example, '/csr1/csr_pos_oberes/api/routes.php/register?foo=bar' 
// becomes '/csr1/csr_pos_oberes/api/routes.php/register'.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// This is the base directory where the API folder is located.
// We'll remove this prefix to isolate just the resource part.
$base = '/csr1/csr_pos_oberes/api';

// Use substr() to remove the base path from the full URI.
// substr($string, length) removes the first 'length' characters.
// For example, if $uri = '/csr1/csr_pos_oberes/api/routes.php/register'
// and $base = '/csr1/csr_pos_oberes/api' (27 characters),
// then substr() returns '/routes.php/'.
$pathInfo = substr($uri, strlen($base));

// Remove leading and trailing slashes from the path.
// This gives us a clean resource identifier.
// For example, '/register' becomes 'register' and '/login' becomes 'login'.
$pathInfo = trim($pathInfo, '/');

// Split the path into segments using the '/' delimiter.
// For example, 'routes.php/register' becomes ['routes.php', 'register'].
$segments = explode('/', $pathInfo);

// Get the first segment, which represents the resource the client wants.
// If there are no segments, use an empty string as the default.
// So 'register' is extracted from ['routes.php', 'register'].
// If the first segment contains '.php', it's the filename, so skip it and get the next segment.
$resource = '';
if (!empty($segments[0]) && strpos($segments[0], '.php') === false) {
    // First segment doesn't contain .php, so it's the resource
    $resource = $segments[0];
} elseif (!empty($segments[1])) {
    // First segment has .php (it's the filename), so get the second segment as resource
    $resource = $segments[1];
}

// ============================================================================
// SECTION 6: DISPATCH TO THE CORRECT CONTROLLER
// ============================================================================

// Use a switch statement to decide which controller to call based on
// what resource the client requested. The switch checks the value of
// $resource against each case.
switch ($resource) {
    
    // ====================================================================
    // CASE: User Registration
    // ====================================================================
    case 'register':
        if ($method === 'POST') {
            $controller = new UsersController();
            $response = $controller->register($input);
        } else {
            http_response_code(405);
            $response = ['status' => 'error', 'message' => 'Invalid request method for /register. Please use POST.'];
        }
        break;

    // ====================================================================
    // CASE: User Login
    // ====================================================================
    case 'login':
        if ($method === 'POST') {
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            $controller = new LoginController();
            $response = $controller->login($username, $password);
        } else {
            http_response_code(405);
            $response = ['status' => 'error', 'message' => 'Invalid request method for /login. Please use POST.'];
        }
        break;

    // ====================================================================
    // CASE: Get Users
    // ====================================================================
    case 'users':
        if ($method === 'GET') {
            $controller = new UsersController();
            $response = $controller->getUsers();
        } else {
            http_response_code(405);
            $response = ['status' => 'error', 'message' => 
            'Invalid request method for /users. Please use GET.'];
        }
        break;

    // ====================================================================
    // CASE: Invalid or Unknown Resource
    // ====================================================================
    default:
        http_response_code(404);
        $response = [
            'status' => 'error',
            'message' => 'Resource not found. Use /register, /login, or /users.'
        ];
        break;
}

// ============================================================================
// SECTION 8: SEND THE RESPONSE
// ============================================================================

// Convert the $response array to JSON format and send it to the client.
// json_encode() transforms the PHP array into a JSON string.
// echo outputs that string back to the client's browser or API client.
echo json_encode($response);
