<?php
// api/vibrations.php (for POST requests to upload new vibration data)

require '../../db/pdo_conn.php';
require '../../Models/DataHandler.php';

$logDirectory = '../logs/';
$logFile = $logDirectory . 'upload_wav.log';

function log_execution($message) {
    global $logFile;
    date_default_timezone_set('Asia/Manila');
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[{$timestamp}] [VIBRATIONS - POST] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function getCurrentTime24H() {
    date_default_timezone_set('Asia/Manila');
    return date('H:i');
}

log_execution("Script started.");

// Set the content type for the response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    log_execution("Received POST request with 'file'.");
    $uploadedFile = $_FILES['file'];
    log_execution("Uploaded file details: " . print_r($uploadedFile, true));

    // Basic error checking
    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $filename = $uploadedFile['name'];
        $tempFilePath = $uploadedFile['tmp_name'];
        $destinationPath = '../uploads/' . $filename;
        log_execution("Upload successful. Filename: " . $filename . ", Temp path: " . $tempFilePath . ", Destination: " . $destinationPath);

        if (move_uploaded_file($tempFilePath, $destinationPath)) {
            log_execution("File moved successfully to " . $destinationPath);

            if (preg_match('/^(\d{2}-\w{3}-\d{4})_(\d{2})-(\d{2})-(\d{2})(AM|PM)\.wav$/i', $filename, $matches)) {
                $extractedDate = $matches[1]; // e.g., 10-Apr-2025
                $hour = intval($matches[2]);
                $minute = intval($matches[3]);
                $ampm = strtoupper($matches[6]);
                log_execution("Filename matched regex. Matches: " . print_r($matches, true));

                $currentTime24H = getCurrentTime24H();
                log_execution("Current time in Philippines (24H): " . $currentTime24H);

                $wavFileContent = file_get_contents($destinationPath);
                log_execution("WAV file content length: " . strlen($wavFileContent));

                try {
                    $pdo = getPDOConnection('outcastp_weevibes');
                    log_execution("PDO connection established to database 'account'.");
                    $dataHandler = new DataHandler($pdo);
                    log_execution("DataHandler instance created.");

                    // Insert file data into the database
                    if ($dataHandler->insertWavData($extractedDate, $currentTime24H, $wavFileContent)) {
                        $response = array(
                            'status' => 'success',
                            'message' => 'Vibration data uploaded and saved successfully.',
                            'filename' => $filename,
                            'date' => $extractedDate,
                            'time' => $currentTime24H,
                        );
                        http_response_code(201); // Created
                        header('Content-Type: application/json');
                        echo json_encode($response);
                        log_execution("Database insertion successful. Response sent: " . json_encode($response));
                        exit();
                    } else {
                        $response = array(
                            'status' => 'error',
                            'message' => 'File uploaded successfully, but failed to save vibration data to the database.',
                        );
                        http_response_code(500); // Internal Server Error
                        header('Content-Type: application/json');
                        echo json_encode($response);
                        log_execution("Database insertion failed.");
                        exit();
                    }
                } catch (PDOException $e) {
                    log_execution("PDO Exception: " . $e->getMessage());
                    $response = array(
                        'status' => 'error',
                        'message' => 'Database connection error: ' . $e->getMessage(),
                    );
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit();
                }
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'Filename format is incorrect. Expected format: DD-Mon-YYYY_HH-MM-SS[AM|PM].wav',
                    'filename' => $filename,
                );
                http_response_code(400); // Bad Request
                header('Content-Type: application/json');
                echo json_encode($response);
                log_execution("Filename format incorrect.");
                exit();
            }
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Failed to move uploaded file.',
            );
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode($response);
            log_execution("Failed to move uploaded file.");
            exit();
        }
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Error during file upload: ' . $uploadedFile['error'],
        );
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode($response);
        log_execution("File upload error: " . $uploadedFile['error']);
        exit();
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
    log_execution("Invalid request (not POST or no 'file' field).");
    exit();
}

log_execution("Script finished.");

?>