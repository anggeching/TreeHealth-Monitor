<?php

class DataHandler {

    private $pdo;
    private $logDirectory = '../logs/';
    private $logFile = 'data_handler.log'; // Consistent log file name
    private $wavDataTableName = 'wav_data';
    private $classificationTableName = 'tree_status';
    private $uploadDirectory = '../uploads/'; // Define upload directory

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureDirectoryExists($this->logDirectory);
        $this->ensureDirectoryExists($this->uploadDirectory);
        $this->log_execution("DataHandler instance created.");
    }

    private function ensureDirectoryExists($directory) {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true)) {
                error_log("Failed to create directory: " . $directory);
                // Consider throwing an exception or handling this error more robustly
            }
        }
    }

    private function log_execution($message) {
        date_default_timezone_set('Asia/Manila');
        $timestamp = date("Y-m-d H:i:s");
        $logPath = $this->logDirectory . $this->logFile;
        $logMessage = "[{$timestamp}] [DataHandler] {$message}\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    }

    public function insertWavData($date, $time, $wavFile) {
        $this->log_execution("Attempting to insert WAV data: Date={$date}, Time={$time}, WAV File Length=" . strlen($wavFile));
        $sql = "INSERT INTO " . $this->wavDataTableName . " (date, time, wav_file) VALUES (:date, :time, :wav_file)";
        $stmt = $this->pdo->prepare($sql);
        $this->log_execution("Prepared SQL statement: " . $sql);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':wav_file', $wavFile, PDO::PARAM_LOB); // Use PDO::PARAM_LOB for BLOB data
        $this->log_execution("Bound parameters: :date={$date}, :time={$time}, :wav_file (BLOB)");

        try {
            $stmt->execute();
            $this->log_execution("WAV data insertion successful.");
            return true;
        } catch (PDOException $e) {
            $errorMessage = "WAV data insertion error: " . $e->getMessage();
            error_log($errorMessage); // Keep the standard error log as well
            $this->log_execution($errorMessage);
            $this->log_execution("SQLSTATE: " . $stmt->errorInfo()[0]);
            $this->log_execution("Driver-specific error code: " . $stmt->errorInfo()[1]);
            $this->log_execution("Driver-specific error message: " . $stmt->errorInfo()[2]);
            return false;
        }
    }

    public function insertClassificationResult($classificationData) {
        $this->log_execution("Attempting to insert classification result: " . json_encode($classificationData));
        $sql = "INSERT INTO " . $this->classificationTableName . " (classification, std_dev, max_delta, std_threshold, delta_threshold, timestamp)
                  VALUES (:classification, :std_dev, :max_delta, :std_threshold, :delta_threshold, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $this->log_execution("Prepared SQL statement: " . $sql);

        // Bind parameters from the classification data array
        $stmt->bindParam(':classification', $classificationData['classification']);
        $stmt->bindParam(':std_dev', $classificationData['std_dev']);
        $stmt->bindParam(':max_delta', $classificationData['max_delta']);
        $stmt->bindParam(':std_threshold', $classificationData['std_threshold']);
        $stmt->bindParam(':delta_threshold', $classificationData['delta_threshold']);

        $this->log_execution("Bound parameters: " . json_encode($classificationData));

        try {
            $stmt->execute();
            $this->log_execution("Classification result insertion successful.");
            return true;
        } catch (PDOException $e) {
            $errorMessage = "Classification result insertion error: " . $e->getMessage();
            error_log($errorMessage); // Keep the standard error log as well
            $this->log_execution($errorMessage);
            $this->log_execution("SQLSTATE: " . $stmt->errorInfo()[0]);
            $this->log_execution("Driver-specific error code: " . $stmt->errorInfo()[1]);
            $this->log_execution("Driver-specific error message: " . $stmt->errorInfo()[2]);
            return false;
        }
    }

    public function saveWavFile($tempFilePath, $newFilename) {
        $destinationPath = $this->uploadDirectory . $newFilename;
        if (move_uploaded_file($tempFilePath, $destinationPath)) {
            $this->log_execution("WAV file saved successfully to: " . $destinationPath);
            return $destinationPath;
        } else {
            $this->log_execution("Failed to save WAV file to: " . $destinationPath);
            return false;
        }
    }
}

?>