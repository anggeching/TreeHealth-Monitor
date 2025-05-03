<?php

class DataFetcher {
    private $pdo;
    private $logDirectory = '../logs/';
    private $logFile = 'data_fetcher.log'; 
    private $classificationTableName = 'tree_status';
    private $wavDataTableName = 'wav_data';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureDirectoryExists($this->logDirectory);
        $this->log_execution("DataFetcher instance created.");
    }

    private function ensureDirectoryExists($directory) {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true)) {
                error_log("Failed to create directory: " . $directory);
            }
        }
    }

    private function log_execution($message) {
        date_default_timezone_set('Asia/Manila');
        $timestamp = date("Y-m-d H:i:s");
        $logPath = $this->logDirectory . $this->logFile;
        $logMessage = "[{$timestamp}] [DataFetcher] {$message}\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    }

    public function fetchAllTimeAndClassifications() {
        $this->log_execution("Attempting to fetch all timestamps and classifications.");
        $sql = "SELECT timestamp, classification
                    FROM " . $this->classificationTableName . "
                    ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $this->log_execution("Prepared SQL statement: " . $sql);

        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log_execution("Successfully fetched all timestamps and classifications. Count: " . count($results));
            return $results;
        } catch (PDOException $e) {
            $errorMessage = "Error fetching all timestamps and classifications: " . $e->getMessage();
            error_log($errorMessage);
            $this->log_execution($errorMessage);
            $this->log_execution("SQLSTATE: " . $stmt->errorInfo()[0]);
            $this->log_execution("Driver-specific error code: " . $stmt->errorInfo()[1]);
            $this->log_execution("Driver-specific error message: " . $stmt->errorInfo()[2]);
            return [];
        }
    }

    public function fetchLatestClassification() {
        $this->log_execution("Attempting to fetch all timestamps and classifications.");
        $sql = "SELECT timestamp, classification
                    FROM " . $this->classificationTableName . "
                    ORDER BY id DESC
                    LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $this->log_execution("Prepared SQL statement: " . $sql);

        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log_execution("Successfully fetched all timestamps and classifications. Count: " . count($results));
            return $results;
        } catch (PDOException $e) {
            $errorMessage = "Error fetching all timestamps and classifications: " . $e->getMessage();
            error_log($errorMessage);
            $this->log_execution($errorMessage);
            $this->log_execution("SQLSTATE: " . $stmt->errorInfo()[0]);
            $this->log_execution("Driver-specific error code: " . $stmt->errorInfo()[1]);
            $this->log_execution("Driver-specific error message: " . $stmt->errorInfo()[2]);
            return [];
        }
       
    } 

    public function fetchAllWavDatesAndTime() {
        $this->log_execution("Attempting to fetch all dates and times from wav_data ordered by index descending.");
        $sql = "SELECT date, time
                    FROM " . $this->wavDataTableName . "
                    ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $this->log_execution("Prepared SQL statement: " . $sql);

        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log_execution("Successfully fetched all dates and times from wav_data ordered by index descending. Count: " . count($results));
            return $results;
        } catch (PDOException $e) {
            $errorMessage = "Error fetching dates and times from wav_data: " . $e->getMessage();
            error_log($errorMessage);
            $this->log_execution($errorMessage);
            $this->log_execution("SQLSTATE: " . $stmt->errorInfo()[0]);
            $this->log_execution("Driver-specific error code: " . $stmt->errorInfo()[1]);
            $this->log_execution("Driver-specific error message: " . $stmt->errorInfo()[2]);
            return [];
        }
    }


}

?>