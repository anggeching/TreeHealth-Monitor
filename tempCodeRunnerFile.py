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


timestamp = datetime.now().strftime("%d-%b-%Y_%I-%M-%S%p")
today_date = datetime.now().strftime("%B %d")
# Set the folder where the received files will be stored
output_folder = f"/Applications/XAMPP/xamppfiles/htdocs/WeeVibesV2/{today_date}"

#declare merge wav variable and path
merge_folder_path = f"/Applications/XAMPP/xamppfiles/htdocs/WeeVibesV2/MERGED"
merge_filename = f"{timestamp}.wav"
merge_filepath = os.path.join(merge_folder_path, merge_filename)

#declare  amplitude txt file variable and path
amp_folder_path = f"/Applications/XAMPP/xamppfiles/htdocs/WeeVibesV2/AMPLITUDE"
amp_filename = f"{timestamp}.txt"
amp_filepath = os.path.join(amp_folder_path, amp_filename)

class WavInspector:
    def __init__(self, file_path=None, merge_interval=60):
        self.file_path = file_path
        self.sample_rate = None
        self.data = None
        self.time = None
        self.merge_interval = merge_interval  # Merge interval in seconds (default 30 seconds)
        
        #self.create_directories()
        self.start_merge_thread()

    def handle_post_request(self, request, folder_path):
        """
        Handles a POST request to save received WAV files in the specified folder.

        Parameters:
            request (HTTPRequest): The incoming HTTP request.
            folder_path (str): Path where WAV files will be saved.
        """
        # Make sure the folder exists
        if not os.path.exists(folder_path):
            os.makedirs(folder_path)

        # Read the incoming file data from the POST request
        content_length = int(request.headers['Content-Length'])
        post_data = request.rfile.read(content_length)

        # Check if the content type is valid for WAV file
        if "wav" not in request.headers['Content-Type'].lower():
            response = "Invalid file format. Only WAV files are allowed."
            request.send_response(400)
            request.send_header("Content-type", "text/html")
            request.end_headers()
            request.wfile.write(response.encode())
            return

        # Create a timestamped filename to avoid overwriting
        timestamp = datetime.now().strftime("%d-%b-%Y_%I-%M-%S%p")
        filename = f"{timestamp}.wav"
        filepath = os.path.join(folder_path, filename)

        # Save the incoming WAV file to the specified folder
        with open(filepath, 'wb') as f:
            f.write(post_data)

        response = f"File successfully uploaded and saved as {filename}"
        request.send_response(200)
        request.send_header("Content-type", "text/html")
        request.end_headers()
        request.wfile.write(response.encode())
        self.send_file_to_server(filepath)

        # send data via post request to data.php (outputfolder - send all wav file to )
    
    def send_file_to_server(self, filepath):
        """
        Sends the WAV file to the `data.php` endpoint via a POST request.
        Includes more robust error handling, logging, and response handling.
        """
        url = "http://localhost/WeeVibesv2/api/upload_wav.php"  # Ensure the URL is correct
        try:
            with open(filepath, 'rb') as file:
                files = {'file': (os.path.basename(filepath), file, 'audio/wav')}
                response = requests.post(url, files=files)
                response.raise_for_status()  # Raise HTTPError for bad responses (4xx or 5xx)
                logging.info(f"File sent successfully: {filepath} - Status Code: {response.status_code}")

                # Access the response content (e.g., if the PHP script returns JSON or text)
                response_content = response.text
                logging.debug(f"Server response content: {response_content}")

                # You can now process the response_content as needed
                try:
                    response_json = response.json()
                    logging.debug(f"Server response JSON: {response_json}")
                    # Do something with the JSON data
                    # For example:
                    if response_json.get("status") == "success":
                        logging.info("PHP script reported successful processing.")
                    else:
                        logging.warning(f"PHP script reported an issue: {response_json.get('message')}")
                except json.JSONDecodeError:
                    logging.debug("Server response is not valid JSON.")
                    # Handle non-JSON response if expected

        except requests.exceptions.RequestException as e:
            logging.error(f"Network error sending file {filepath}: {e}")
        except FileNotFoundError:
            logging.error(f"File not found when trying to send: {filepath}")
        except Exception as e:
            logging.error(f"An unexpected error occurred while sending {filepath}: {e}")

    
    def start_merge_thread(self):
        """
        Start a background thread that runs the merge function every `merge_interval` seconds.
        """
        merge_thread = threading.Thread(target=self.run_merge_timer)
        merge_thread.daemon = True
        merge_thread.start()

    def run_merge_timer(self):
        """
        Run the merge process every `merge_interval` seconds.
        """
        while True:
            time.sleep(self.merge_interval)
            print(f"Running merge process...")
            self.merge_wav_folder(output_folder)  # Call the merge function with the folder path
            inspector = WavInspector()

            # Load and analyze the merged WAV file
            inspector.load_wav(merge_filepath)
            print("Merged WAV file loaded successfully.")

            # Export amplitude per second
            inspector.export_amplitude_per_second(amp_filepath)

            # Classify infestation
            classification = inspector.classify_infestation()
            print(f"Result: {classification}")


    def merge_wav_folder(self, folder_path, output_file=merge_filepath):
        """
        Merges WAV files with matching audio parameters from a folder.

        Skips incompatible files but prints a warning.

        Parameters:
            folder_path (str): Path to folder with WAV files.
            output_file (str): Name of the merged output file.

        Returns:
            str: Path to the merged WAV file.
        """
        if not os.path.exists(merge_folder_path):
            os.makedirs(merge_folder_path)

        wav_files = sorted([
            os.path.join(folder_path, f)
            for f in os.listdir(folder_path)
            if f.lower().endswith(".wav")
        ])

        if not wav_files:
            raise FileNotFoundError("No WAV files found in the specified folder.")

        reference_params = None
        all_files = []


        # Collect all files and use the first file's params
        for file in wav_files:
            with wave.open(file, 'rb') as wf:
                if reference_params is None:
                    reference_params = wf.getparams()
                all_files.append(file)

        # Make sure the output directory exists
        os.makedirs(os.path.dirname(output_file), exist_ok=True)

        with wave.open(output_file, 'wb') as out_wav:
            out_wav.setparams(reference_params)
            for file in all_files:
                with wave.open(file, 'rb') as wf:
                    frames = wf.readframes(wf.getnframes())
                    out_wav.writeframes(frames)
                    print(f"Merged: {file}")

        print(f"\n✅ Merged {len(all_files)} files into: {output_file}")
        return output_file

    def load_wav(self, file_path):
        """
        Loads a WAV file, converts it to mono, and normalizes amplitude.

        Parameters:
            file_path (str): Path to the WAV file.
        """
        self.sample_rate, data = wavfile.read(file_path)

        # Convert to mono if stereo
        if len(data.shape) > 1:
            data = np.mean(data, axis=1)

        # Normalize the amplitude
        self.data = data / np.max(np.abs(data))
        
        # Time axis
        self.time = np.linspace(0, len(self.data) / self.sample_rate, num=len(self.data))

    def export_amplitude_per_second(self, output_txt=amp_filepath):
        """
        Computes and saves the average absolute amplitude per second to a text file.

        Parameters:
            output_txt (str): Path to the output text file.
        """

        if not os.path.exists(amp_folder_path):
            os.makedirs(amp_folder_path)

        if self.data is None or self.sample_rate is None:
            raise ValueError("No WAV file loaded. Call load_wav() first.")

        total_seconds = int(len(self.data) / self.sample_rate)
        amplitudes = []

        for second in range(total_seconds):
            start = second * self.sample_rate
            end = start + self.sample_rate
            segment = self.data[start:end]
            avg_amplitude = np.mean(segment)
            amplitudes.append(avg_amplitude)

        with open(output_txt, "w") as f:
            for sec, amp in enumerate(amplitudes):
                f.write(f"{sec:04d}s: {amp:.6f}\n")

        print(f"Amplitude per second saved to {output_txt}")
    
    def classify_infestation(self, std_threshold=0.01, delta_threshold=0.05):
        """
        Classifies whether the WAV is infested or not using a threshold-based rule.

        Parameters:
            std_threshold (float): Threshold for standard deviation of amplitude.
            delta_threshold (float): Threshold for maximum amplitude change per second.

        Returns:
            dict: JSON object with classification result and analysis data.
        """
        if self.data is None or self.sample_rate is None:
            raise ValueError("No WAV file loaded. Call load_wav() first.")

        total_seconds = int(len(self.data) / self.sample_rate)
        per_second_averages = []

        for second in range(total_seconds):
            start = second * self.sample_rate
            end = start + self.sample_rate
            segment = self.data[start:end]
            avg = np.mean(segment)
            per_second_averages.append(avg)

        std_dev = np.std(per_second_averages)
        max_delta = np.max(np.abs(np.diff(per_second_averages)))

        print(f"STD of per-second average: {std_dev:.6f}")
        print(f"Max delta between seconds: {max_delta:.6f}")

        classification = "INFESTED" if std_dev > std_threshold or max_delta > delta_threshold else "NOT INFESTED"

        # Create the result as a JSON object
        result = {
            "classification": classification,
            "std_dev": std_dev,
            "max_delta": max_delta,
            "std_threshold": std_threshold,
            "delta_threshold": delta_threshold
        }

        # Return the result as a JSON string
        return json.dumps(result)
    

    
# HTTP server to handle incoming POST requests
class MyHandler(BaseHTTPRequestHandler):

    def do_POST(self):
        # Create an instance of WavInspector and handle the POST request
        inspector = WavInspector()
        inspector.handle_post_request(self, output_folder)

def run(server_class=HTTPServer, handler_class=MyHandler, port=8000):
    server_address = ('', port)
    httpd = server_class(server_address, handler_class)
    print(f'Starting server on port {port}...')
    httpd.serve_forever()
    



if __name__ == '__main__':
    run(port=5000)  # Runs the server on port 5000
