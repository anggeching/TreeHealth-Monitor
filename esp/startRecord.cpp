#include "FS.h"
#include "SD.h"
#include "SPI.h"

#define SD_CS 5
#define SENSOR_PIN 32  // KY-038 Analog Output (ADC1 Channel 4)

#define SAMPLE_RATE 4000  // Sample at 4kHz
#define RECORD_TIME 20000  // 20 seconds recording time
#define BUFFER_SIZE 256   // Buffer to store samples

// ESP32 GPIOs for LEDs
#define LED_1 13
#define LED_2 14
#define LED_3 27
#define LED_4 26
#define LED_5 25

const char* filename = "/audiov20.wav";  
File audioFile;

int16_t buffer[BUFFER_SIZE];  // Audio buffer
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
    
    if (!SD.begin(SD_CS)) {
        Serial.println("SD card initialization failed!");
        return;
    }
    Serial.println("SD card initialized.");
    
    // Delete existing file to avoid corruption
    if (SD.exists(filename)) {
        SD.remove(filename);
    }
    
    // Initialize LED pins
    pinMode(LED_1, OUTPUT);
    pinMode(LED_2, OUTPUT);
    pinMode(LED_3, OUTPUT);
    pinMode(LED_4, OUTPUT);
    pinMode(LED_5, OUTPUT);
    
    startRecording();
}

void loop() {
    // Empty because everything happens in setup
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