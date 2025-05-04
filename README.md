# Weevibes: IoT-Based Early Detection System for Asiatic Palm Weevil Larvae (Research Prototype)

[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Maintenance](https://img.shields.io/badge/Maintained-Yes-green.svg)](https://github.com/your-github-username/weevibes-research)
[![Contributions Welcome](https://img.shields.io/badge/Contributions-Welcome-blue.svg)](https://github.com/your-github-username/weevibes-research/CONTRIBUTING.md)

## Overview

Weevibes is a research prototype for an IoT-based system designed for the early detection of Asiatic Palm Weevil (APW) larvae infestation in coconut trees. This project leverages vibration sensors and a web application to establish a cause-and-effect relationship between specific vibration patterns and the presence of APW larvae under controlled environmental conditions.

**This repository contains the backend code for the web application server, responsible for receiving and storing vibration data and infestation classifications from the sensing units.**

## Key Features (within the Controlled Research Environment)

* **Quantitative Data Acquisition:** Utilizes accelerometers to continuously record vibration data from coconut tree trunks.
* **Real-time Data Logging Pipeline:** Raw vibration data is transmitted immediately upon recording from the ESP32 to the Raspberry Pi, and then forwarded to the web server for logging.
* **Daily Infestation Classification Pipeline:** The Raspberry Pi performs daily analysis (implementation details not included here) and sends an "infected" or "not infected" classification to the web server.
* **API Endpoints for Data Ingestion:**
    * `/api/upload_wav.php`: Receives raw vibration data as WAV files.
    * `/api/receive_classification.php`: Receives daily infestation classifications in JSON format.
* **Data Storage:** Utilizes a MySQL database to store raw vibration data and infestation classifications.
* **Basic Logging:** Implements logging for debugging and monitoring the data ingestion process.

## System Architecture (Controlled Environment)

![System Architecture Diagram (Optional - Consider adding a visual diagram here if you have one)](./docs/architecture.png)

1.  **Sensing Unit (Coconut Tree):**
    * **ESP32 Microcontroller:** Controls the vibration sensor and data transmission.
    * **Accelerometer:** Measures vibrations on the coconut tree trunk.
    * **Communication:** Sends raw vibration data (as WAV files) to the Raspberry Pi via HTTP POST requests.

2.  **Edge Computing & Forwarding Unit (Raspberry Pi):**
    * **Raspberry Pi:** Receives data from the ESP32.
    * **Real-time Data Forwarding:** Immediately sends raw WAV files to the web server's `/api/upload_wav.php` endpoint.
    * **Daily Analysis & Classification:** Performs analysis on the collected vibration data (analysis logic resides here).
    * **Classification Reporting:** Sends daily "infected" or "not infected" classifications (in JSON format) to the web server's `/api/receive_classification.php` endpoint.

3.  **Web Application Server:**
    * **Backend Language:** PHP
    * **Web Server:** (Likely Apache or Nginx)
    * **Database:** MySQL (`outcastp_weevibes`)
    * **API Endpoints:**
        * `/api/upload_wav.php`: Processes and stores raw vibration data from WAV files.
        * `/api/receive_classification.php`: Receives and stores daily infestation classifications.

## API Endpoints

### `/api/upload_wav.php`

* **Method:** `POST`
* **Content-Type:** `multipart/form-data` (expects a file upload with the field name `file`)
* **Request Body:** Includes a WAV file named according to the format `DD-Mon-YYYY_HH-MM-SS[AM|PM].wav`.
* **Response (JSON):**
    ```json
    {
        "status": "success" | "error",
        "message": "...",
        "filename": "...",
        "date": "DD-Mon-YYYY",
        "time": "HH:MM",
        // ... other relevant information
    }
    ```

### `/api/receive_classification.php`

* **Method:** `POST` (Assumed)
* **Content-Type:** `application/json`
* **Request Body (Example):**
    ```json
    {
        "timestamp": "YYYY-MM-DD HH:MM:SS",
        "classification": "infected"
    }
    ```
* **Response (JSON):**
    ```json
    {
        "status": "success" | "error",
        "data": [
            {
                "date": "d M Y",
                "classification": "infected" | "not infected"
            },
            // ... more classification data
        ]
    }
    ```

## Getting Started

This section provides a basic guide for setting up the backend web application.

### Prerequisites

* PHP 7.4 or higher
* MySQL database
* Web server (Apache or Nginx recommended)
* PDO PHP extension enabled

### Installation

1.  **Clone the repository:**
    ```bash
    git clone [https://github.com/your-github-username/weevibes-research.git](https://github.com/your-github-username/weevibes-research.git)
    cd weevibes-research/web_app_server
    ```

2.  **Database Configuration:**
    * Create a MySQL database named `outcastp_weevibes`.
    * Update the database connection details in `db/pdo_conn.php` with your database credentials:
        ```php
        <?php
        $host = 'your_db_host';
        $dbname = 'outcastp_weevibes';
        $user = 'your_db_user';
        $password = 'your_db_password';
        ?>
        ```
    * You will need to create the necessary database tables. While the schema is not explicitly provided in this documentation, you will need tables to store the raw WAV data and the daily classifications.

3.  **Web Server Configuration:**
    * Ensure your web server is configured to serve the PHP files in this directory.
    * Make sure the web server has write permissions to the `../uploads/` directory for storing uploaded WAV files and the `../logs/` directory for log files.

### Usage

Once the backend is set up, the Raspberry Pi units can send data to the API endpoints as described in the System Architecture section.

## Contributing

Contributions to this research project are welcome. Please refer to the [CONTRIBUTING.md](https://github.com/your-github-username/weevibes-research/CONTRIBUTING.md) file for guidelines.

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Acknowledgements

This research is focused on establishing fundamental cause-and-effect relationships within a controlled environment and is a crucial step towards developing a practical early detection system for the Asiatic Palm Weevil.

## Contact

[Your Name/Organization]
[Your Email Address]
[Link to your website/profile (optional)]