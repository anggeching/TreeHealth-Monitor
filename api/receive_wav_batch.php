<?php
require '../db/pdo_conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed']);
    exit();
}

if (!isset($_POST['date'], $_POST['time'], $_POST['file_name'], $_FILES['wav_file'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

$date = $_POST['date'];
$time = $_POST['time'];
$fileName = $_POST['file_name'];
$uploadedFile = $_FILES['wav_file']['tmp_name'];

if (!file_exists($uploadedFile)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File not received']);
    exit();
}

try {
    $pdo = getPDOConnection('outcastp_weevibes');
    $blob = file_get_contents($uploadedFile);

    $stmt = $pdo->prepare("
        INSERT INTO wav_data (date, time, wav_file)
        VALUES (:date, :time, :wav_file)
    ");
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':wav_file', $blob, PDO::PARAM_LOB);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'File uploaded successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
}