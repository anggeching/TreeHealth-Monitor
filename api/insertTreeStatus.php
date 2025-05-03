<?php
header("Content-Type: application/json");

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST method is allowed"]);
    exit;
}

// Get JSON payload
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

// Validate required fields
$required = ["classification", "std_dev", "max_delta", "std_threshold", "delta_threshold", "timestamp"];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(["error" => "$field is required"]);
        exit;
    }
}

// Connect to DB
$host = 'localhost';
$db = 'tree_monitoring_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO tree_status (classification, std_dev, max_delta, std_threshold, delta_threshold, timestamp)
            VALUES (:classification, :std_dev, :max_delta, :std_threshold, :delta_threshold, :timestamp)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':classification' => $input['classification'],
