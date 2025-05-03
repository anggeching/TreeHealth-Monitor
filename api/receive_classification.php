<?php
// api/receive_classification.php

require '../db/pdo_conn.php'; // Adjust the path as needed
require '../model/DataHandler.php';

// Set the log file
$logDirectory = '../logs/';
$logFile = $logDirectory . 'receive_classification.log';

// Store the timestamp of the last successful data reception
$lastSuccessfulReceptionTimeFile = $logDirectory . 'last_classification_time.txt';

function log_execution($message) {
    global $logFile;
    date_default_timezone_set('Asia/Manila');
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[{$timestamp}] [receive_classification] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function getLastSuccessfulReceptionTime() {
    global $lastSuccessfulReceptionTimeFile;
    if (file_exists($lastSuccessfulReceptionTimeFile)) {
        $time = trim(file_get_contents($lastSuccessfulReceptionTimeFile));
        return (int) $time;
    }
    return 0; // Return 0 if the file doesn't exist (first request)
}

function updateLastSuccessfulReceptionTime() {
    global $lastSuccessfulReceptionTimeFile;
    $currentTime = time();
    file_put_contents($lastSuccessfulReceptionTimeFile, $currentTime);
}

log_execution("Script started.");

// Set the content type for the response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_execution("Received POST request.");

    // Check the time since the last successful reception
    $lastReceptionTime = getLastSuccessfulReceptionTime();
    $currentTime = time();
    $timeDifference = $currentTime - $lastReceptionTime;
    $minimumInterval = 10; // seconds

    log_execution("Last successful reception time: " . date("Y-m-d H:i:s", $lastReceptionTime));
    log_execution("Current time: " . date("Y-m-d H:i:s", $currentTime));
    log_execution("Time difference: " . $timeDifference . " seconds.");
    log_execution("Minimum interval required: " . $minimumInterval . " seconds.");

    if ($lastReceptionTime > 0 && $timeDifference < $minimumInterval) {
        $response = array('status' => 'error', 'message' => 'Too frequent requests. Please wait ' . ($minimumInterval - $timeDifference) . ' more seconds.');
        http_response_code(429); // Too Many Requests
        echo json_encode($response);
        log_execution("Request rejected due to too frequent requests.");
        log_execution("Script finished.\n");
        exit();
    }

    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    log_execution("Raw POST data: " . $json_data);

    // Try to decode the JSON data
    $received_data = json_decode($json_data, true);
    log_execution("Decoded data: " . json_encode($received_data));

    if ($received_data !== null && isset($received_data['classification'])) {
        $classificationData = $received_data['classification'];
        log_execution("Received classification data: " . json_encode($classificationData));

        // Establish PDO connection
        try {
            $pdo = getPDOConnection('outcastp_weevibes');
            $dataHandler = new DataHandler($pdo);

            // Insert the classification result into the database
            if ($dataHandler->insertClassificationResult($classificationData)) {
                log_execution("INSERTING TO DATABASE: " . ($classificationData));
                $response = array('status' => 'success', 'message' => 'Classification data received and saved successfully.');
                log_execution("DONE INSERTING TO DATABASE: " . ($classificationData));
                http_response_code(200);
                updateLastSuccessfulReceptionTime(); // Update the last successful reception time
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
        $response = array('status' => 'error', 'message' => 'Invalid or missing classification data in the JSON request.');
        http_response_code(400); // Bad Request
        log_execution("Invalid or missing 'classification' data in JSON request.");
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