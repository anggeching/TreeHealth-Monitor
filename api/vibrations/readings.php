<?php
// api/vibrations/readings.php (for GET requests to fetch vibration reading timestamps)

require '../db/pdo_conn.php';
require '../model/DataFetcher.php';

header('Content-Type: application/json');

try {
    $pdo = getPDOConnection('weevibes');
    $fetcher = new DataFetcher($pdo);

    $wavData = $fetcher->fetchAllWavDatesAndTime();

    $response = [
        'status' => 'success',
        'data' => $wavData,
    ];

    http_response_code(200); // OK
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    $errorResponse = [
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
    ];
    echo json_encode($errorResponse);
}
?>