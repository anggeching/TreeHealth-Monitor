<?php
// api/tree_status.php

require '../db/pdo_conn.php';
require '../model/DataFetcher.php';

header('Content-Type: application/json');

try {
    $pdo = getPDOConnection('outcastp_weevibes');
    $fetcher = new DataFetcher($pdo);
    $allData = $fetcher->fetchAllTimeAndClassifications();
    $formattedData = [];

    foreach ($allData as $item) {
        $timestamp = $item['timestamp'];
        $datePart = substr($timestamp, 0, 10);
        $dateTime = new DateTime($datePart);
        $formattedDate = $dateTime->format('d M Y');

        $formattedData[] = [
            'date' => $formattedDate,
            'classification' => $item['classification'],
        ];
    }

    $response = [
        'status' => 'success',
        'data' => $formattedData,
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
