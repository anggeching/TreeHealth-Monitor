#include "FS.h"
#include "SPI.h"
#include <WiFi.h>
#include <HTTPClient.h>
#include "miniz.h"
#include <LittleFS.h>
#include "SD.h"
#include "miniz.h"

#define SD_CS 5
#define SENSOR_PIN 32  // KY-038 Analog Output (ADC1 Channel 4)

#define SAMPLE_RATE 4000  // Sample at 4kHz
#define RECORD_TIME 20000  // 20 seconds recording time
#define BUFFER_SIZE 256   // Buffer to store samples

// ESP32 WIFI 
const char* serverURL = "http://192.168.1.35//weevibesv2/model/data.php"; // Change to your server

const char* ssid = "PLDTHOMEFIBRCRQAQ";  // Change this
const char* password = "PLDTWIFIK2UbD";  // Change this

#define CHUNK_SIZE 512
#define COMPRESSED_FILE "/compressed_audio.zip"

// ESP32 GPIOs for LEDs
#define LED_1 13
#define LED_2 14
#define LED_3 27
#define LED_4 26
#define LED_5 25

const char* filename = "/audiov20.wav";  
File audioFile;

static int16_t buffer[BUFFER_SIZE];  
int bufferIndex = 0;  // Current buffer position
unsigned long lastSampleTime = 0;  // Time of last sample
unsigned long startTime = 0;  // Start time of recording

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
    
    // Initialize LittleFS once
    if (!LittleFS.begin(true)) {  // `true` will format if needed
        Serial.println("LittleFS initialization failed!");
        return;
    }
    Serial.println("LittleFS initialized.");

    // Ensure file is removed before recording (use LittleFS, not SD)
    if (LittleFS.exists(filename)) {
        LittleFS.remove(filename);
    }

    // Start recording (Make sure it saves to LittleFS)
    startRecording();

    // Compress WAV file
    compressWAV();
}

void loop() {
    // Empty because everything happens in setup
}

void startRecording() {
    Serial.println("Recording started...");

    // Open file on LittleFS instead of SD
    File audioFile = LittleFS.open(filename, FILE_WRITE);
    if (!audioFile) {
        Serial.println("❌ Failed to create WAV file on LittleFS!");
        return;
    } else {
        Serial.println("✅ WAV file created successfully!");
}

    
    // Write placeholder WAV header
    audioFile.write((uint8_t*)&wavHeader, sizeof(WAVHeader));

    startTime = micros();  // Use micros() for accurate timing
    
    while ((micros() - startTime) < (RECORD_TIME * 1000)) {  // 20 sec in microseconds
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
              Serial.print("Writing buffer to file: ");
              for (int i = 0; i < BUFFER_SIZE; i++) {
                  Serial.print(buffer[i]);
                  Serial.print(" ");
              }
              Serial.println();  // Newline for readability

              audioFile.write((uint8_t*)buffer, BUFFER_SIZE * sizeof(int16_t));
              audioFile.flush(); // Ensure data is written to storage
              bufferIndex = 0;
            }

            delay(1);
        }
    }

    // Write any remaining data
    if (bufferIndex > 0) {
        audioFile.write((uint8_t*)buffer, bufferIndex * sizeof(int16_t));
    }
    
    // Update WAV header
    updateWAVHeader();
    
    Serial.println("Recording finished! WAV file saved.");

    audioFile.close();  // Close file after writing
}

void verifyWAVHeader() {
    File audioFile = SD.open(filename);
    if (!audioFile) {
        Serial.println("Failed to open WAV file!");
        return;
    }

    WAVHeader wavHeader;
    audioFile.read((uint8_t*)&wavHeader, sizeof(WAVHeader));

    Serial.println("\n📌 WAV File Header Information:");

    // Check RIFF Header
    Serial.print("Chunk ID: "); Serial.write(wavHeader.riff, 4); Serial.println();
    if (strncmp(wavHeader.riff, "RIFF", 4) != 0) {
        Serial.println("❌ ERROR: Invalid RIFF header!");
        return;
    }

    // Print file size
    Serial.print("File Size: "); Serial.println(wavHeader.fileSize + 8);

    // Check WAVE Format
    Serial.print("Format: "); Serial.write(wavHeader.wave, 4); Serial.println();
    if (strncmp(wavHeader.wave, "WAVE", 4) != 0) {
        Serial.println("❌ ERROR: Invalid WAVE format!");
        return;
    }

    // Check "fmt " subchunk
    Serial.print("Subchunk1 ID: "); Serial.write(wavHeader.fmt, 4); Serial.println();
    Serial.print("Subchunk1 Size: "); Serial.println(wavHeader.fmtLength);
    Serial.print("Audio Format: "); Serial.println(wavHeader.audioFormat == 1 ? "PCM (Uncompressed)" : "Unknown");

    Serial.print("Channels: "); Serial.println(wavHeader.numChannels);
    Serial.print("Sample Rate: "); Serial.println(wavHeader.sampleRate);
    Serial.print("Byte Rate: "); Serial.println(wavHeader.byteRate);
    Serial.print("Block Align: "); Serial.println(wavHeader.blockAlign);
    Serial.print("Bits per Sample: "); Serial.println(wavHeader.bitsPerSample);

    // Check "data" subchunk
    Serial.print("Subchunk2 ID: "); Serial.write(wavHeader.data, 4); Serial.println();
    Serial.print("Data Size: "); Serial.println(wavHeader.dataSize);
    
    // Expected data size check
    uint32_t expectedDataSize = wavHeader.fileSize - 36;  // Data size should be file size - (Header size 44 - 8)
    if (wavHeader.dataSize != expectedDataSize) {
        Serial.println("WARNING: Data size mismatch! Possible corruption.");
    } else {
        Serial.println("WAV file structure is valid!");
    }

    audioFile.close();
}

void checkWAVHeader() {
    File file = SD.open(filename);
    if (!file) {
        Serial.println("Failed to open WAV file.");
        return;
    }

    Serial.println("First 16 bytes of the WAV file:");
    for (int i = 0; i < 16; i++) {
        Serial.print(file.read(), HEX);
        Serial.print(" ");
    }
    file.close();
}

void updateWAVHeader() {
    audioFile.close();  // Close before updating

    File file = LittleFS.open(filename, "r+");  // Open in read+write mode
    if (!file) {
        Serial.println("Failed to open WAV file for header update!");
        return;
    }

    Serial.println("Updating WAV header...");
    
    // Calculate file size
    size_t fileSize = file.size();
    Serial.print("WAV file size: ");
    Serial.print(fileSize);
    Serial.println(" bytes");

    // Ensure file isn't empty
    if (fileSize <= sizeof(WAVHeader)) {
        Serial.println("Error: File too small!");
        file.close();
        return;
    }

    // Update header
    WAVHeader header;
    file.seek(0);
    file.read((uint8_t*)&header, sizeof(WAVHeader));

    header.fileSize = fileSize - 8;
    header.dataSize = fileSize - sizeof(WAVHeader);

    file.seek(0);
    file.write((uint8_t*)&header, sizeof(WAVHeader));
    file.flush();
    file.close();

    Serial.println("WAV header updated!");
}

void readWavFile() {
    File audioFile = SD.open(filename, FILE_READ);
    if (!audioFile) {
        Serial.println("Failed to open WAV file!");
        return;
    }

    Serial.println("WAV file opened successfully!");

    // Step 1: Skip the 44-byte header
    audioFile.seek(44);  

    Serial.println("Reading PCM Data (First 20 samples):");

    // Step 2: Read PCM samples
    int16_t sampleBuffer[BUFFER_SIZE];  // 16-bit PCM samples
    int samplesRead = 0;

    while (audioFile.available() && samplesRead < 20) { // Read first 20 samples
        audioFile.read((uint8_t*)&sampleBuffer[0], sizeof(int16_t));  // Read 2 bytes per sample
        Serial.println(sampleBuffer[0]);  // Print sample value
        delay(5);
        samplesRead++;
    }

    audioFile.close();
    Serial.println("Finished reading PCM data.");
}

void sendWAVFile() {
    File audioFile = SD.open(filename, FILE_READ);
    if (!audioFile) {
        Serial.println("Failed to open WAV file!");
        return;
    }

    Serial.println("Starting file upload...");
    
    WiFiClient client;
    HTTPClient http;
    http.begin(client, serverURL);
    http.addHeader("Content-Type", "application/octet-stream");

    // Send file in chunks
    uint8_t buffer[CHUNK_SIZE];
    int bytesRead = 0;
    int totalBytesSent = 0;

    while ((bytesRead = audioFile.read(buffer, CHUNK_SIZE)) > 0) {
        Serial.print("Sending chunk: ");
        Serial.println(bytesRead);
        
        int httpResponseCode = http.sendRequest("POST", buffer, bytesRead);
        if (httpResponseCode < 0) {
            Serial.println("HTTP Request failed!");
            break;
        }
        totalBytesSent += bytesRead;
    }

    Serial.print("Total Bytes Sent: ");
    Serial.println(totalBytesSent);

    // Close connections
    audioFile.close();
    http.end();
    Serial.println("File upload completed!");
}


void compressWAV() {
    File wavFile = LittleFS.open(filename, "r");
    if (!wavFile) {
        Serial.println("Failed to open WAV file!");
        return;
    }

    File zipFile = LittleFS.open(COMPRESSED_FILE, "w");
    if (!zipFile) {
        Serial.println("Failed to create compressed file!");
        wavFile.close();
        return;
    }

    Serial.println("Compressing WAV file...");

    uint8_t inBuffer[512];   // Input buffer
    uint8_t outBuffer[512];  // Output buffer

    int bytesRead;
    while ((bytesRead = wavFile.read(inBuffer, sizeof(inBuffer))) > 0) {
        // Simple byte-wise compression (placeholder)
        for (int i = 0; i < bytesRead; i++) {
            outBuffer[i] = inBuffer[i] ^ 0xAA;  // XOR-based simple compression (for example)
        }
        zipFile.write(outBuffer, bytesRead);
    }

    zipFile.close();
    wavFile.close();

    Serial.println("Compression finished! Compressed file saved.");
}

