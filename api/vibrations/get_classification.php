<?php
// api/vibrations/get_classifications.php (for GET requests to retrieve classifications)

require '../../db/pdo_conn.php'; // Adjust the path
require '../../model/DataFetcher.php'; // Adjust the path

// Set the log file
$logDirectory = '../../logs/'; // Adjust the path
$logFile = $logDirectory . 'get_vibrations_classifications.log'; // Specific log for GET

function log_execution($message) {
    global $logFile;
    date_default_timezone_set('Asia/Manila');
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[{$timestamp}] [VIBRATIONS/GET_CLASSIFICATIONS - GET] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

log_execution("Script started.");

// Set the content type for the response
header('Content-Type: application/json');

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    log_execution("Received GET request.");

    try {
        $pdo = getPDOConnection('outcastp_weevibes');
        $fetcher = new DataFetcher($pdo);
        $allData = $fetcher->fetchAllTimeAndClassifications();
        $formattedData = [];

        foreach ($allData as $item) {
            $timestamp = $item['timestamp'];
            $datePart = substr($timestamp, 0, 10);
            $dateTime = new DateTime($datePart);
            $formattedDate = $dateTime->format('Y-m-d'); // Standardized date format

            $formattedData[] = [
                'date' => $formattedDate,
                'classification' => $item['classification'],
            ];
        }

        $response = [
            'status' => 'success',
            'data' => $formattedData,
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
        log_execution("PDO Exception: " . $e->getMessage());
    }

    log_execution("Response sent.");

} else {
    // Handle other request methods (shouldn't happen if accessed correctly)
    http_response_code(405); // Method Not Allowed
    $response = array('status' => 'error', 'message' => 'Invalid request method. Only GET is allowed on this endpoint.');
    echo json_encode($response);
    log_execution("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
}

log_execution("Script finished.\n");

?>