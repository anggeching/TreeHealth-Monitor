import numpy as np
from scipy.io import wavfile
import matplotlib.pyplot as plt
import wave
import os
from datetime import datetime
import json

timestamp = datetime.now().strftime("%d-%b-%Y_%I-%M-%S%p")

class WavInspector:
    def __init__(self, file_path=None):
        self.file_path = file_path
        self.sample_rate = None
        self.data = None
        self.time = None

    def merge_wav_folder(self, folder_path, output_file="merged_output.wav"):
        """
        Merges WAV files with matching audio parameters from a folder and returns JSON.

        Skips incompatible files but includes a warning in the JSON response.

        Parameters:
            folder_path (str): Path to folder with WAV files.
            output_file (str): Name of the merged output file.

        Returns:
            str: JSON string containing the status and output file path.
        """
        wav_files = sorted([
            os.path.join(folder_path, f)
            for f in os.listdir(folder_path)
            if f.lower().endswith(".wav")
        ])

        if not wav_files:
            return json.dumps({"status": "error", "message": "No WAV files found in the specified folder."})

        valid_files = []
        incompatible_files = []
        reference_params = None

        # Identify compatible files
        for file in wav_files:
            try:
                with wave.open(file, 'rb') as wf:
                    params = wf.getparams()
                    if reference_params is None:
                        reference_params = params
                        valid_files.append(file)
                    elif params == reference_params:
                        valid_files.append(file)
                    else:
                        incompatible_files.append(file)
            except wave.Error as e:
                incompatible_files.append(file)
                print(f"Error opening file {file}: {e}")
            except Exception as e:
                incompatible_files.append(file)
                print(f"An unexpected error occurred with file {file}: {e}")

        if not valid_files:
            return json.dumps({"status": "error", "message": "No compatible WAV files to merge."})

        # Write merged file
        try:
            merged_file_path = output_file
            with wave.open(merged_file_path, 'wb') as out_wav:
                out_wav.setparams(reference_params)
                for file in valid_files:
                    with wave.open(file, 'rb') as wf:
                        out_wav.writeframes(wf.readframes(wf.getnframes()))

            message = f"Merged {len(valid_files)} files into: {merged_file_path}"
            if incompatible_files:
                message += f" Skipped {len(incompatible_files)} incompatible files."
                print(f"Skipped incompatible files: {incompatible_files}")
            print(message)
            return json.dumps({"status": "success", "message": message, "output_file": merged_file_path})
        except wave.Error as e:
            return json.dumps({"status": "error", "message": f"Error writing merged file: {e}"})
        except Exception as e:
            return json.dumps({"status": "error", "message": f"An unexpected error occurred during merging: {e}"})

    def load_wav(self, file_path):
        """
        Loads a WAV file, converts it to mono, normalizes amplitude, and returns JSON.

        Parameters:
            file_path (str): Path to the WAV file.

        Returns:
            str: JSON string containing the status, sample rate, and data shape.
        """
        try:
            self.sample_rate, data = wavfile.read(file_path)

            # Convert to mono if stereo
            if len(data.shape) > 1:
                data = np.mean(data, axis=1)

            # Normalize the amplitude
            self.data = data / np.max(np.abs(data))

            # Time axis
            self.time = np.linspace(0, len(self.data) / self.sample_rate, num=len(self.data))

            return json.dumps({"status": "success", "sample_rate": self.sample_rate, "data_shape": self.data.shape})
        except FileNotFoundError:
            self.sample_rate = None
            self.data = None
            self.time = None
            return json.dumps({"status": "error", "message": f"File not found: {file_path}"})
        except ValueError:
            self.sample_rate = None
            self.data = None
            self.time = None
            return json.dumps({"status": "error", "message": f"Could not read WAV file: {file_path}. Ensure it's a valid WAV format."})
        except Exception as e:
            self.sample_rate = None
            self.data = None
            self.time = None
            return json.dumps({"status": "error", "message": f"An unexpected error occurred during loading: {e}"})

    def plot_waveform(self):
        """
        Plots the waveform (Time vs. Amplitude) and returns JSON indicating success or error.
        """
        if self.data is None:
            return json.dumps({"status": "error", "message": "No WAV file loaded. Call load_wav() first."})

        plt.figure(figsize=(12, 4))
        plt.plot(self.time, self.data, color='black')
        plt.xlabel("Time (s)")
        plt.ylabel("Amplitude")
        plt.title("Waveform (Time vs Amplitude)")
        plt.grid()

        # Set fixed x-axis limit (e.g., first 1 second)
        plt.xlim(0, 1)
        plt.ylim(-1.5, 1.5)

        try:
            plt.show()
            return json.dumps({"status": "success", "message": "Waveform plotted."})
        except Exception as e:
            return json.dumps({"status": "error", "message": f"Error during plotting: {e}"})

    def export_amplitude_per_second(self, output_txt=f"amplitude_per_second_{timestamp}.txt"):
        """
        Computes and saves the average absolute amplitude per second to a text file and returns JSON.

        Parameters:
            output_txt (str): Path to the output text file.

        Returns:
            str: JSON string containing the status and the output file path.
        """
        if self.data is None or self.sample_rate is None:
            return json.dumps({"status": "error", "message": "No WAV file loaded. Call load_wav() first."})

        total_seconds = int(len(self.data) / self.sample_rate)
        amplitudes = []

        for second in range(total_seconds):
            start = second * self.sample_rate
            end = start + self.sample_rate
            segment = self.data[start:end]
            avg_amplitude = np.mean(np.abs(segment))  # Use absolute value for average amplitude
            amplitudes.append(avg_amplitude)

        try:
            with open(output_txt, "w") as f:
                for sec, amp in enumerate(amplitudes):
                    f.write(f"{sec:04d}s: {amp:.6f}\n")

            message = f"Amplitude per second saved to {output_txt}"
            print(message)
            return json.dumps({"status": "success", "message": message, "output_file": output_txt})
        except IOError as e:
            return json.dumps({"status": "error", "message": f"Error writing to file: {e}"})
        except Exception as e:
            return json.dumps({"status": "error", "message": f"An unexpected error occurred during export: {e}"})

    def classify_infestation(self, std_threshold=0.01, delta_threshold=0.05):
        """
        Classifies whether the WAV is infested or not using a threshold-based rule and returns JSON.

        Parameters:
            std_threshold (float): Threshold for standard deviation of amplitude.
            delta_threshold (float): Threshold for maximum amplitude change per second.

        Returns:
            str: JSON string containing the classification result.
        """
        if self.data is None or self.sample_rate is None:
            return json.dumps({"status": "error", "message": "No WAV file loaded. Call load_wav() first."})

        total_seconds = int(len(self.data) / self.sample_rate)
        per_second_averages = []

        for second in range(total_seconds):
            start = second * self.sample_rate
            end = start + self.sample_rate
            segment = self.data[start:end]
            avg = np.mean(np.abs(segment)) # Use absolute value for per-second average
            per_second_averages.append(avg)

        std_dev = np.std(per_second_averages)
        if len(per_second_averages) > 1:
            max_delta = np.max(np.abs(np.diff(per_second_averages)))
        else:
            max_delta = 0  # No change if only one second of data

        print(f"STD of per-second average: {std_dev:.6f}")
        print(f"Max delta between seconds: {max_delta:.6f}")

        if std_dev > std_threshold or max_delta > delta_threshold:
            classification = "INFESTED"
            print("Classification: INFESTED")
        else:
            classification = "NOT INFESTED"
            print("Classification: NOT INFESTED")

        return json.dumps({"status": "success", "classification": classification, "std_dev": std_dev, "max_delta": max_delta, "std_threshold": std_threshold, "delta_threshold": delta_threshold})

if __name__ == '__main__':
    # Example Usage (assuming you have a folder named 'wav_files' with some .wav files)
    inspector = WavInspector()
    # Create a dummy wav_files folder and some empty .wav files for testing
    if not os.path.exists("wav_files"):
        os.makedirs("wav_files")
    for i in range(3):
        with wave.open(f"wav_files/dummy_{i}.wav", 'w') as wf:
            wf.setnchannels(1)
            wf.setsampwidth(2)
            wf.setframerate(44100)
            wf.writeframes(b'')

    # Test merge_wav_folder
    merge_result_json = inspector.merge_wav_folder("wav_files", "merged_test.wav")
    print("Merge Result:", merge_result_json)

    # Test load_wav (replace 'your_audio.wav' with an actual WAV file path)
    # Create a dummy audio file for load_wav testing
    dummy_data = np.random.rand(44100) * 0.1
    wavfile.write("dummy_audio.wav", 44100, dummy_data)
    load_result_json = inspector.load_wav("dummy_audio.wav")
    print("Load Result:", load_result_json)

    if inspector.data is not None:
        # Test plot_waveform
        plot_result_json = inspector.plot_waveform()
        print("Plot Result:", plot_result_json)

        # Test export_amplitude_per_second
        export_result_json = inspector.export_amplitude_per_second()
        print("Export Amplitude Result:", export_result_json)

        # Test classify_infestation
        classify_result_json = inspector.classify_infestation()
        print("Classification Result:", classify_result_json)

    # Clean up dummy files and folder
    if os.path.exists("wav_files"):
        for f in os.listdir("wav_files"):
            os.remove(os.path.join("wav_files", f))
        os.rmdir("wav_files")
    if os.path.exists("merged_test.wav"):
        os.remove("merged_test.wav")
    if os.path.exists("dummy_audio.wav"):
        os.remove("dummy_audio.wav")
    for f in os.listdir():
        if f.startswith("amplitude_per_second_"):
            os.remove(f)