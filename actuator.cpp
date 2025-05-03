#include <WiFi.h>
#include <HTTPClient.h>
#include <FS.h>
#include <SD.h>
#include "time.h"

#define WIFI_SSID "PLDTHOMEFIBRCRQAQ"
#define WIFI_PASSWORD "PLDTWIFIK2UbD"
#define SERVER_URL "http://192.168.1.8:5000/upload_wav"  // Adjust the URL to match your main.py endpoint

#define SD_CS 5
#define SENSOR_PIN 34  // Accelerometer Analog Output (ADC1 Channel 4)

#define SAMPLE_RATE 4000  // Sample at 4kHz
#define RECORD_TIME 16000  // 16 seconds recording time
#define BUFFER_SIZE 256   // Buffer to store samples

char filename[32]; // Buffer to store filename

const char* ntpServer = "time.google.com";
const long gmtOffset_sec = 28800;  // GMT+8 for the Philippines
const int daylightOffset_sec = 0;

#define RECORD_INTERVAL 60000  // 1 minute in milliseconds
unsigned long lastRecordTime = 0;

File audioFile;
int16_t buffer[BUFFER_SIZE];  
int bufferIndex = 0;
unsigned long lastSampleTime = 0;
unsigned long startTime = 0;

// WAV Header structure
struct WAVHeader {
    char riff[4] = {'R', 'I', 'F', 'F'};
    uint32_t fileSize;
    char wave[4] = {'W', 'A', 'V', 'E'};
    char fmt[4] = {'f', 'm', 't', ' '};
    uint32_t fmtLength = 16;
    uint16_t audioFormat = 1;
    uint16_t numChannels = 1;
    uint32_t sampleRate = SAMPLE_RATE;
    uint32_t byteRate;
    uint16_t blockAlign;
    uint16_t bitsPerSample = 16;
    char data[4] = {'d', 'a', 't', 'a'};
    uint32_t dataSize;
} wavHeader;

void setup() {
    Serial.begin(115200);

    // Connect to WiFi
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    Serial.print("Connecting to WiFi...");
    while (WiFi.status() != WL_CONNECTED) {
        Serial.print(".");
        delay(500);
    }
    Serial.println("\nWiFi Connected!");

    configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
    struct tm timeinfo;
    int retry = 0;
    while (!getLocalTime(&timeinfo) && retry < 10) {  // Retry up to 10 times
        Serial.println("Retrying NTP time sync...");
        delay(1000);
        retry++;
    }
    if (!getLocalTime(&timeinfo)) {
        Serial.println("ERROR: Still failed to obtain time!");
        return;
    }

    // Generate a timestamp-based filename
    strftime(filename, sizeof(filename), "/%Y%m%d_%H%M%S.wav", &timeinfo);
    Serial.print("Generated filename: ");
    Serial.println(filename);

    // Initialize SD Card
    if (!SD.begin(SD_CS)) {
        Serial.println("SD Card initialization failed!");
        return;
    }
    Serial.println("SD Card initialized!");

    startRecording();
    uploadFile(filename);
}

void loop() {
    if (millis() - lastRecordTime >= RECORD_INTERVAL) {
        lastRecordTime = millis();
        
        if (WiFi.status() != WL_CONNECTED) {
            Serial.println("WiFi disconnected! Reconnecting...");
            WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
            int retry = 0;
            while (WiFi.status() != WL_CONNECTED && retry < 10) {
                Serial.print(".");
                delay(1000);
                retry++;
            }
            if (WiFi.status() == WL_CONNECTED) {
                Serial.println("\nWiFi Reconnected!");
            } else {
                Serial.println("\nFailed to reconnect!");
                return;  // Skip recording if no WiFi
            }
        }

        struct tm timeinfo;
        if (!getLocalTime(&timeinfo)) {
            Serial.println("Failed to obtain time!");
            return;
        }

        // Generate new filename with timestamp
        strftime(filename, sizeof(filename), "/%Y%m%d_%H%M%S.wav", &timeinfo);
        Serial.print("Generated filename: ");
        Serial.println(filename);

        startRecording();
        uploadFile(filename);
    }
}

void startRecording() {
    Serial.println("Recording started...");

    // Open file and write placeholder header
    audioFile = SD.open(filename, FILE_WRITE);
    if (!audioFile) {
        Serial.println("Failed to create file!");
        return;
    }
    
    audioFile.write((uint8_t*)&wavHeader, sizeof(WAVHeader));

    startTime = micros();  // Use micros() for accurate timing
    
    while ((micros() - startTime) < (RECORD_TIME * 1000)) {  // 16 sec in microseconds
        unsigned long currentTime = micros();
        
        // Sample at precise intervals (every 250μs for 4kHz)
        if (currentTime - lastSampleTime >= (1000000 / SAMPLE_RATE)) {
            lastSampleTime = currentTime;

            int rawSample = analogRead(SENSOR_PIN);  // Read ADC (0 - 4095)
            int mappedSample = map(rawSample, 190, 1339, 180, 1800); // Adjust mapping
            int16_t sample = (mappedSample - 1000) * 8; // Center around zero & scale
            
            buffer[bufferIndex++] = sample;

            // Write buffer when full
            if (bufferIndex >= BUFFER_SIZE) {
                audioFile.write((uint8_t*)buffer, BUFFER_SIZE * sizeof(int16_t));
                bufferIndex = 0;
            }
            
        }
    }
   // Write any remaining data
    if (bufferIndex > 0) {
        audioFile.write((uint8_t*)buffer, bufferIndex * sizeof(int16_t));
    }
    
    // Update WAV header to fix playback issues
    updateWAVHeader();
    
    Serial.println("Recording finished! WAV file saved.");
}

void updateWAVHeader() {
    audioFile.flush();  // Ensure all data is written before checking size
    uint32_t fileSize = audioFile.size();
    uint32_t dataSize = fileSize - sizeof(WAVHeader);

    wavHeader.fileSize = fileSize - 8;
    wavHeader.dataSize = dataSize;
    wavHeader.byteRate = SAMPLE_RATE * (wavHeader.bitsPerSample / 8);  // 4kHz * 2 bytes
    wavHeader.blockAlign = (wavHeader.bitsPerSample / 8);  // 2 bytes per sample

    audioFile.seek(0);
    audioFile.write((uint8_t*)&wavHeader, sizeof(WAVHeader));
    audioFile.flush();  // Flush again after writing header
    audioFile.close();  // Properly close file to save changes

    Serial.print("WAV header updated! File size: ");
    Serial.println(fileSize);
    Serial.print(" bytes (");
    Serial.print(fileSize / 1000.0, 2);
    Serial.println(" KB)");
}

void uploadFile(const char *path) {
    Serial.print("Uploading file: ");
    Serial.println(path);

    File file = SD.open(path);
    if (!file) {
        Serial.println("ERROR: Failed to open file for reading!");
        return;
    }

    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("ERROR: WiFi not connected!");
        return;
    }

    HTTPClient http;
    http.begin(SERVER_URL);  // Set the server URL

    http.addHeader("Content-Type", "audio/wav");  // Set the content type

    int httpResponseCode = http.sendRequest("POST", &file, file.size());  // Send file to server

    if (httpResponseCode > 0) {
        Serial.print("HTTP Response Code: ");
        Serial.println(httpResponseCode);
    } else {
        Serial.print("Error sending POST request: ");
        Serial.println(httpResponseCode);
    }

    file.close();
    http.end();
}