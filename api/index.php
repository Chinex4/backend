<?php
declare(strict_types=1);

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

// set a real writable log file (important)
ini_set('error_log', __DIR__ . '/php-error.log');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header("Access-Control-Allow-Origin: $origin"); // Reflects the origin
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require __DIR__ . "/bootstrap.php";
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$parts = explode("/", $path);
// var_dump($parts);
$resource = $parts[3];
$type = $parts[4];
$action = $parts[5];
$id = $parts[6] ?? null;
if ($resource != "task") {
    http_response_code(404);
    exit;
}
$user_conn = new Database();
$connect = $user_conn->check_Database($_ENV["DB_NAME"]);
$task_gateway = new TaskGatewayFunction($user_conn);
$controller = new TaskController();
$controller->processRequest($_SERVER['REQUEST_METHOD'], $type, $action, $id);
// var_dump($_SERVER['REQUEST_METHOD'], $type, $id);