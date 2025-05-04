<?php
// api/classifications.php (for POST requests to submit new classifications)

require '../../db/pdo_conn.php';
require '../../Models/DataHandler.php';

// Set the log file
$logDirectory = '../logs/';
$logFile = $logDirectory . 'classification_submission.log';

function log_execution($message) {
    global $logFile;
    date_default_timezone_set('Asia/Manila');
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[{$timestamp}] [CLASSIFICATIONS - POST] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

log_execution("Script started.");

// Set the content type for the response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_execution("Received POST request.");

    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    log_execution("Raw POST data: " . $json_data);

    // Try to decode the JSON data
    $received_data = json_decode($json_data, true);
    log_execution("Decoded data: " . json_encode($received_data));

    if ($received_data !== null && isset($received_data['classification']) && isset($received_data['timestamp'])) {
        $classification = $received_data['classification'];
        $receivedTimestamp = $received_data['timestamp'];
        log_execution("Received classification: " . $classification . ", Timestamp: " . $receivedTimestamp);

        // Establish PDO connection
        try {
            $pdo = getPDOConnection('outcastp_weevibes');
            $dataHandler = new DataHandler($pdo);

            // Insert the classification result and timestamp into the database
            if ($dataHandler->insertClassificationResultWithTimestamp($classification, $receivedTimestamp)) {
                log_execution("INSERTED CLASSIFICATION TO DATABASE: Classification=" . $classification . ", Timestamp=" . $receivedTimestamp);
                $response = array('status' => 'success', 'message' => 'Classification data received and saved successfully.');
                http_response_code(201); // Created
            } else {
                $response = array('status' => 'error', 'message' => 'Failed to save classification data to the database.');
                http_response_code(500); // Internal Server Error
            }

        } catch (PDOException $e) {
            $response = array('status' => 'error', 'message' => 'Database connection error: ' . $e->getMessage());
            http_response_code(500); // Internal Server Error
            log_execution("PDO Exception: " . $e->getMessage());
        }

    } else {
        $response = array('status' => 'error', 'message' => 'Invalid or missing classification or timestamp in the JSON request.');
        http_response_code(400); // Bad Request
        log_execution("Invalid or missing 'classification' or 'timestamp' data in JSON request.");
    }

} else {
    $response = array('status' => 'error', 'message' => 'Invalid request method. Only POST is allowed.');
    http_response_code(405); // Method Not Allowed
    log_execution("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
}

// Send the JSON response
echo json_encode($response);
log_execution("Response sent: " . json_encode($response));

log_execution("Script finished.\n \n");

?>