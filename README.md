# Weevibes: IoT-Based Early Detection System for Asiatic Palm Weevil Larvae (Research Prototype)

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
    * `/api/vibrations`: Receives raw vibration data as WAV files.
    * `/api/vibrations/readings`: Retrieves vibration reading times.
    * `/api/vibrations/classifications`: Submits a new classification result.
    * `/api/vibrations/get_classifications`: Retrieves classification data.
* **User Authentication:** Handles user registration, login, and logout for the web application.
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
    * **Real-time Data Forwarding:** Immediately sends raw WAV files to the web server's `/api/vibrations` endpoint.
    * **Daily Analysis & Classification:** Performs analysis on the collected vibration data (analysis logic resides here).
    * **Classification Reporting:** Sends daily "infected" or "not infected" classifications (in JSON format) to the web server's `/api/vibrations/classifications` endpoint.

3.  **Web Application Server:**
    * **Backend Language:** PHP
    * **Web Server:** Apache
    * **Database:** MySQL (`outcastp_weevibes`)
    * **API Endpoints:**
        * Vibration Data:
            * `POST /api/vibrations`: Processes and stores raw vibration data from WAV files.
            * `GET /api/vibrations/readings`: Retrieves vibration reading times.
            * `POST /api/vibrations/classifications`: Receives and stores daily infestation classifications.
            * `GET /api/vibrations/get_classifications`: Retrieves classification data.
        * User Authentication:
            * `POST /api/users/register`:  Registers a new user.
            * `POST /api/users/login`:   Logs in an existing user.
            * `POST /api/users/logout`:  Logs out the current user.

## File Structure

The Scripts are organized as follows:

```
your_project_root/
├── public/                 # <-- WEB SERVER DOCUMENT ROOT points here
│   ├── index.html          # Login form page (or main entry point)
│   ├── signup.html         # Signup form page
│   ├── dashboard.html      # Example page users see after login
│   ├── css/                # Directory for CSS files
│   │   └── style.css       # Example stylesheet
│   └── js/                 # Directory for JavaScript files
│       ├── auth.js         # Example JS for handling login/signup forms (AJAX)
│       └── main.js         # General JS for the dashboard, etc.
│
├── src/                    # <-- CORE PHP APPLICATION CODE (NOT directly web accessible)
│   ├── Api/                # Directory for API endpoint scripts
│   │   ├── login.php       # Handles login requests
│   │   ├── signup.php      # Handles registration requests
│   │   ├── logout.php      # Handles logout requests
│   │   └── status.php      # Handles user status updates
│   │
│   ├── Database/           # Directory for database related code
│   │   └── pdo_conn.php    # Contains the getPDOConnection() function
│   │
│   ├── Models/             # Directory for data model classes
│   │   └── User.php        # The User class model
│   │
│   ├── Lib/                # (Optional) For shared libraries or helper functions
│   │   └── helpers.php     # Example for utility functions
│   │
│   └── config/             # (Optional but Recommended) Configuration files
│       └── database.php    # Example: Store DB credentials here (load in pdo_conn.php)
│                           # !! IMPORTANT: Keep sensitive config outside the web root if possible !!
│
├── vendor/                 # (Optional) For Composer dependencies (if you use Composer)
│
├── .htaccess               # (Optional) Apache configuration (e.g., URL rewriting, blocking access to src/)
│
└── composer.json           # (Optional) Composer configuration file
```



**Explanation of Key Directories and Files:**

* `api/`: Contains all API endpoint files.
* `api/vibrations/`: Groups all vibration-related API endpoints.
    * `classifications.php`: Handles POST requests for submitting new classifications.
    * `get_classifications.php`: Handles GET requests for retrieving classifications.
    * `readings.php`: Handles GET requests for retrieving vibration reading times.
    * `vibrations.php`: Handles POST requests for uploading raw vibration data.
* `api/users/`:  Contains all user-related API logic.
    * `users.php`:    Handles core user management actions (registration, login, logout).
    * `auth.php`:     (Optional)  Contains authentication middleware and utility functions.
* `api/db/`: Contains database-related files.
    * `pdo_conn.php`: Handles the PDO database connection.
* `api/model/`: Contains files that handle data logic (models).
    * `DataHandler.php`: Handles database interactions for data manipulation (e.g., inserting data).
    * `DataFetcher.php`: Handles database interactions for data retrieval (e.g., fetching data).
    * `User.php`: (Optional) Represents user data and related operations.
* `api/logs/`: Contains log files.
    * `vibrations_classification.log`: For POST requests to /api/vibrations/classifications
    * `get_vibrations_classifications.log`: For GET requests to /api/vibrations/get_classifications
    * `vibration_upload.log`: For POST requests to /api/vibrations
    * `vibration_readings.log`: For GET requests to /api/vibrations/readings
    * `user_auth.log`: For user authentication-related events.
* Optional Directories: The `db/`, `model/`, and `logs/` directories can be located either inside the `api/` directory or at the root level of the application.

## API Endpoints

### Vibration Data

* `POST /api/vibrations`
    * Purpose: Uploads a WAV file containing raw vibration data.
    * Request: `multipart/form-data` with the WAV file in the `file` field.
    * Response: `201 Created` (on success) with details.

* `GET /api/vibrations/readings`
    * Purpose: Retrieves a list of timestamps (dates and times) when vibration data was recorded.
    * Request: None.
    * Response: `200 OK` (on success) with an array of timestamps.

* `POST /api/vibrations/classifications`
    * Purpose: Submits a new classification result with a timestamp.
    * Request: `application/json` with `classification` and `timestamp`.
    * Response: `201 Created` (on success).

* `GET /api/vibrations/get_classifications`
    * Purpose: Retrieves a list of classification data with their dates.
    * Request: None.
    * Response: `200 OK` (on success) with an array of date-classification pairs.

### User Authentication

* `POST /api/users/register`
    * Purpose: Registers a new user.
    * Request: `application/json` with user registration details (e.g., username, password).
    * Response:  `201 Created` on success, with user details.

* `POST /api/users/login`
    * Purpose: Logs in an existing user.
    * Request: `application/json` with user login credentials (e.g., username, password).
    * Response: `200 OK` on success, with authentication token.

* `POST /api/users/logout`
    * Purpose: Logs out the current user.
    * Request:  None or possibly a token.
    * Response: `200 OK` on success.

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

    * Create a MySQL database named `weevibes`.
    * Update the database connection details in `db/pdo_conn.php` with your database credentials:

        ```php
        <?php
        $host = 'your_db_host';
        $dbname = 'outcastp_weevibes';
        $user = 'your_db_user';
        $password = 'your_db_password';
        ?>
        ```
    * You will need to create the necessary database tables. While the schema is not explicitly provided in this documentation, you will need tables to store the raw WAV data, daily classifications, and user information.

3.  **Web Server Configuration:**

    * Ensure your web server is configured to serve the PHP files in this directory.
    * Make sure the web server has write permissions to the `../uploads/` directory for storing uploaded WAV files and the `../logs/` directory for log files.

### Usage

Once the backend is set up, the Raspberry Pi units can send data to the API endpoints as described in the System Architecture section.  Users can also interact with the system through the user authentication endpoints.

## Contributing

Contributions to this research project are welcome. Please refer to the [CONTRIBUTING.md](https://github.com/your-github-username/weevibes-research/CONTRIBUTING.md) file for guidelines.

## Acknowledgements

This research is focused on establishing fundamental cause-and-effect relationships within a controlled environment and is a crucial step towards developing a practical early detection system for the Asiatic Palm Weevil.
