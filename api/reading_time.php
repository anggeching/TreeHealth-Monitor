<?php
// api/fetch_wav_dates_times.php

require '../db/pdo_conn.php';
require '../model/DataFetcher.php';

header('Content-Type: application/json');

try {
    $pdo = getPDOConnection('outcastp_weevibes');
    $fetcher = new DataFetcher($pdo);

    $wavData = $fetcher->fetchAllWavDatesAndTime();

    $response = [
        'status' => 'success',
        'data' => $wavData,
    ];

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