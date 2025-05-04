import os
import json
import time
import wave
import shutil
from datetime import datetime
from http.server import BaseHTTPRequestHandler, HTTPServer
from io import BytesIO
import threading
import numpy as np
from scipy.io import wavfile
import requests
import logging

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# --- Global Variables ---
today_date = datetime.now().strftime("%Y-%m-%d")  # Standardized date format
output_folder = f"C:/xampp/htdocs/WeeVibesV2/raw_vibrations/{today_date}" # More descriptive folder
merge_folder_path = f"C:/xampp/htdocs/WeeVibesV2/merged_vibrations" # More descriptive folder
amp_folder_path = f"C:/xampp/htdocs/WeeVibesV2/amplitude_data" # More descriptive folder
CLASSIFICATION_THRESHOLD_SECONDS = 10 # Minimum interval for sending classifications

# --- Singleton WavInspector Instance ---
wav_inspector_instance = None
wav_inspector_lock = threading.Lock()

class WavInspector:
    def __init__(self, merge_interval=300):
        self.sample_rate = None
        self.data = None
        self.time = None
        self.merge_interval = merge_interval  # Merge interval in seconds
        self.last_sent_classification_time = 0 # Timestamp of last sent classification
        self.last_sent_classification = None  # To store the last sent classification
        self._stop_event = threading.Event()
        self._merge_thread = None
        self._start_merge_thread()

    def _start_merge_thread(self):
        if self._merge_thread is None or not self._merge_thread.is_alive():
            self._merge_thread = threading.Thread(target=self._run_merge_timer)
            self._merge_thread.daemon = True
            self._merge_thread.start()

    def stop_merge_thread(self):
        self._stop_event.set()
        if self._merge_thread and self._merge_thread.is_alive():
            self._merge_thread.join()
            self._merge_thread = None

    def _run_merge_timer(self):
        while not self._stop_event.is_set():
            logging.info(f"Running merge process...")
            timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S") # Standardized timestamp
            merge_filename = f"{timestamp}.wav"
            merge_filepath = os.path.join(merge_folder_path, merge_filename)
            amp_filename = f"{timestamp}.txt"
            amp_filepath = os.path.join(amp_folder_path, amp_filename)

            merged_file = self.merge_wav_folder(output_folder, output_file=merge_filepath)

            if merged_file:
                self.load_wav(merged_file)
                logging.info("Merged WAV file loaded successfully.")
                self.export_amplitude_per_second(amp_filepath)
                classification_result_json = self.classify_infestation()
                logging.info(f"Classification Result JSON: {classification_result_json}")

                current_time = time.time()
                if current_time - self.last_sent_classification_time >= CLASSIFICATION_THRESHOLD_SECONDS and classification_result_json != self.last_sent_classification:
                    if self._send_classification_to_server(classification_result_json):
                        self.last_sent_classification = classification_result_json
                        self.last_sent_classification_time = current_time
                else:
                    logging.info("Classification result has not changed or minimum send interval not met. Skipping send.")
            else:
                logging.info("No WAV files to merge.")

            time.sleep(self.merge_interval)

    def handle_post_request(self, request, folder_path):
        """Handles a POST request to save received WAV files."""
        if not os.path.exists(folder_path):
            os.makedirs(folder_path)

        content_length = int(request.headers['Content-Length'])
        post_data = request.rfile.read(content_length)

        if "wav" not in request.headers['Content-Type'].lower():
            response = "Invalid file format. Only WAV files are allowed."
            request.send_response(400)
            request.send_header("Content-type", "text/html")
            request.end_headers()
            request.wfile.write(response.encode())
            return

        timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S") # Standardized timestamp
        filename = f"{timestamp}.wav"
        filepath = os.path.join(folder_path, filename)

        with open(filepath, 'wb') as f:
            f.write(post_data)

        response = f"File successfully uploaded and saved as {filename}"
        request.send_response(200)
        request.send_header("Content-type", "text/html")
        request.end_headers()
        request.wfile.write(response.encode())
        self._send_vibration_data_to_server(filepath) # More RESTful name

    def _send_vibration_data_to_server(self, filepath):
        """Sends the WAV file to the /api/vibrations endpoint."""
        url = "http://localhost/WeeVibesv2/api/vibrations" # RESTful URL
        try:
            with open(filepath, 'rb') as file:
                files = {'file': (os.path.basename(filepath), file, 'audio/wav')}
                response = requests.post(url, files=files)
                response.raise_for_status()
                logging.info(f"Vibration data sent successfully: {filepath} - Status Code: {response.status_code}")
                logging.debug(f"Server response content: {response.text}")
                try:
                    response_json = response.json()
                    logging.debug(f"Server response JSON: {response_json}")
                    if response_json.get("status") == "success":
                        logging.info("PHP script reported successful processing.")
                    else:
                        logging.warning(f"PHP script reported an issue: {response_json.get('message')}")
                except json.JSONDecodeError:
                    logging.debug("Server response is not valid JSON.")
        except requests.exceptions.RequestException as e:
            logging.error(f"Network error sending vibration data {filepath}: {e}")
        except FileNotFoundError:
            logging.error(f"File not found when trying to send: {filepath}")
        except Exception as e:
            logging.error(f"An unexpected error occurred while sending {filepath}: {e}")

    def merge_wav_folder(self, folder_path, output_file):
        """Merges WAV files with matching audio parameters from a folder."""
        if not os.path.exists(merge_folder_path):
            os.makedirs(merge_folder_path)

        wav_files = sorted([os.path.join(folder_path, f) for f in os.listdir(folder_path) if f.lower().endswith(".wav")])

        if not wav_files:
            logging.info("No WAV files found for merging.")
            return None

        reference_params = None
        all_files = []

        for file in wav_files:
            try:
                with wave.open(file, 'rb') as wf:
                    if reference_params is None:
                        reference_params = wf.getparams()
                    all_files.append(file)
            except wave.Error as e:
                logging.warning(f"Skipping invalid WAV file '{file}': {e}")
                continue

        if not all_files:
            logging.info("No valid WAV files found for merging.")
            return None

        os.makedirs(os.path.dirname(output_file), exist_ok=True)

        try:
            with wave.open(output_file, 'wb') as out_wav:
                out_wav.setparams(reference_params)
                for file in all_files:
                    with wave.open(file, 'rb') as wf:
                        frames = wf.readframes(wf.getnframes())
                        out_wav.writeframes(frames)
                        logging.info(f"Merged: {file}")

            logging.info(f"✅ Merged {len(all_files)} files into: {output_file}")
            return output_file
        except wave.Error as e:
            logging.error(f"Error during merging: {e}")
            return None

    def load_wav(self, file_path):
        """Loads a WAV file, converts it to mono, and normalizes amplitude."""
        try:
            self.sample_rate, data = wavfile.read(file_path)
            if len(data.shape) > 1:
                data = np.mean(data, axis=1)
            self.data = data / np.max(np.abs(data)) if data.size > 0 else np.array([])
            self.time = np.linspace(0, len(self.data) / self.sample_rate, num=len(self.data)) if self.sample_rate else np.array([])
        except FileNotFoundError:
            logging.error(f"WAV file not found at {file_path}")
            self.data = None
            self.sample_rate = None
            self.time = None
        except Exception as e:
            logging.error(f"Error loading WAV file {file_path}: {e}")
            self.data = None
            self.sample_rate = None
            self.time = None

    def export_amplitude_per_second(self, output_txt):
        """Computes and saves the average absolute amplitude per second."""
        if not os.path.exists(amp_folder_path):
            os.makedirs(amp_folder_path)

        if self.data is None or self.sample_rate is None:
            logging.warning("No WAV file loaded for amplitude export.")
            return

        total_seconds = int(len(self.data) / self.sample_rate) if self.sample_rate else 0
        amplitudes = []

        for second in range(total_seconds):
            start = second * self.sample_rate
            end = start + self.sample_rate
            segment = self.data[start:end]
            avg_amplitude = np.mean(segment) if segment.size > 0 else 0
            amplitudes.append(avg_amplitude)

        with open(output_txt, "w") as f:
            for sec, amp in enumerate(amplitudes):
                f.write(f"{sec:04d}s: {amp:.6f}\n")

        logging.info(f"Amplitude per second saved to {output_txt}")

    def classify_infestation(self, std_threshold=0.01, delta_threshold=0.05):
        """Classifies whether the WAV is infested or not."""
        if self.data is None or self.sample_rate is None:
            logging.warning("No WAV file loaded for classification.")
            return json.dumps({"classification": "ERROR", "message": "No WAV data loaded"})

        total_seconds = int(len(self.data) / self.sample_rate) if self.sample_rate else 0
        per_second_averages = []

        for second in range(total_seconds):
            start = second * self.sample_rate
            end = start + self.sample_rate
            segment = self.data[start:end]
            avg = np.mean(segment) if segment.size > 0 else 0
            per_second_averages.append(avg)

        std_dev = np.std(per_second_averages) if per_second_averages else 0
        max_delta = np.max(np.abs(np.diff(per_second_averages))) if len(per_second_averages) > 1 else 0

        logging.info(f"STD of per-second average: {std_dev:.6f}")
        logging.info(f"Max delta between seconds: {max_delta:.6f}")

        classification = "INFESTED" if std_dev > std_threshold or max_delta > delta_threshold else "NOT INFESTED"

        result = {
            "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"), # Include timestamp
            "classification": classification,
            "std_dev": std_dev,
            "max_delta": max_delta,
            "std_threshold": std_threshold,
            "delta_threshold": delta_threshold
        }
        return json.dumps(result)

    def _send_classification_to_server(self, classification_data_json_string):
        """Sends the classification to the /api/classifications endpoint."""
        url = "http://localhost/weevibesv2/api/classifications" # RESTful URL
        headers = {'Content-Type': 'application/json'}
        try:
            classification_data = json.loads(classification_data_json_string)
            response = requests.post(url, headers=headers, json=classification_data) # Send the whole object
            response.raise_for_status()
            logging.info(f"Classification sent successfully: {classification_data_json_string} - Status Code: {response.status_code}")
            logging.info(f"Receiver response: {response.json()}")
            return True  # Indicate success
        except requests.exceptions.RequestException as e:
            logging.error(f"Error sending classification: {e}")
            return False # Indicate failure
        except json.JSONDecodeError as e:
            logging.error(f"Error decoding JSON before sending: {e}")
            return False # Indicate failure

# HTTP server to handle incoming POST requests
class MyHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        global wav_inspector_instance, wav_inspector_lock
        with wav_inspector_lock:
            if wav_inspector_instance is None:
                wav_inspector_instance = WavInspector()
        wav_inspector_instance.handle_post_request(self, output_folder)

    def do_GET(self):
        self.send_response(200)
        self.send_header('Content-type', 'text/html')
        self.end_headers()
        message = "Server is running. Send WAV files via POST to /"
        self.wfile.write(message.encode())

def run(server_class=HTTPServer, handler_class=MyHandler, port=8000):
    server_address = ('', port)
    httpd = server_class(server_address, handler_class)
    print(f'Starting server on port {port}...')
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nServer stopped.")
    finally:
        global wav_inspector_instance
        if wav_inspector_instance:
            wav_inspector_instance.stop_merge_thread()

if __name__ == '__main__':
    run(port=5000)  