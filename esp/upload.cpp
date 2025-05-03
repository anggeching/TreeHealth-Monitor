#include <WiFi.h>
#include <HTTPClient.h>
#include <FS.h>
#include <SD.h>

#define WIFI_SSID "PLDTHOMEFIBRCRQAQ"
#define WIFI_PASSWORD "PLDTWIFIK2UbD"
#define SERVER_URL "http://192.168.1.35//weevibesv2/model/data.php"  

#define SD_CS 5
const char *filename = "/weevibez.wav";

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

    // Initialize SD Card
    if (!SD.begin(SD_CS)) {
        Serial.println("SD Card initialization failed!");
        return;
    }
    Serial.println("SD Card initialized!");

    uploadFile(filename);
}

void loop() {
    // Do nothing
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

    WiFiClient client;
    if (!client.connect("192.168.1.35", 80)) {
        Serial.println("ERROR: Server connection failed!");
        file.close();
        return;
    }

    String boundary = "----ESP32Boundary";
    String bodyStart = "--" + boundary + "\r\n"
                       "Content-Disposition: form-data; name=\"file\"; filename=\"weevibes.wav\"\r\n"
                       "Content-Type: audio/wav\r\n\r\n";
    
    String bodyEnd = "\r\n--" + boundary + "--\r\n";

    size_t fileSize = file.size();
    size_t totalSize = bodyStart.length() + fileSize + bodyEnd.length();

    Serial.print("File size: ");
    Serial.println(fileSize);

    // Send HTTP headers
    client.print(String("POST ") + "/WeeVibesv2/model/data.php" + " HTTP/1.1\r\n" +
                 "Host: 192.168.1.35\r\n" +
                 "Content-Type: multipart/form-data; boundary=" + boundary + "\r\n" +
                 "Content-Length: " + String(totalSize) + "\r\n" +
                 "Connection: close\r\n\r\n");

    // Send body start
    client.print(bodyStart);

    // Send file data in chunks
    uint8_t buffer[512];
    size_t totalBytesSent = 0;
    while (file.available()) {
        size_t bytesRead = file.read(buffer, sizeof(buffer));
        client.write(buffer, bytesRead);
        totalBytesSent += bytesRead;

        Serial.print("Sent Chunk: ");
        Serial.print(bytesRead);
        Serial.print(" bytes | Total Sent: ");
        Serial.print(totalBytesSent);
        Serial.println(" bytes");
    }

    // Send closing boundary
    client.print(bodyEnd);

    file.close();
    Serial.println("File upload complete!");

    // Read response from server
    Serial.println("Waiting for server response...");
    while (client.connected() || client.available()) {
        if (client.available()) {
            String response = client.readString();
            Serial.println("Server Response:");
            Serial.println(response);
        }
    }

    client.stop();
    Serial.println("Disconnected from server.");
}
