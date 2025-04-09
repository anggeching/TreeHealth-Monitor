from datetime import datetime
import numpy as np
from scipy.io import wavfile
import matplotlib.pyplot as plt
import wave
import os

class WavInspector:
    def __init__(self, file_path=None):
        self.file_path = file_path
        self.sample_rate = None
        self.data = None
        self.time = None

    def merge_wav_folder(self, folder_path, output_file="merged_output.wav"):
        """
        Merges WAV files with matching audio parameters from a folder.

        Skips incompatible files but prints a warning.

        Parameters:
            folder_path (str): Path to folder with WAV files.
            output_file (str): Name of the merged output file.

        Returns:
            str: Path to the merged WAV file.
        """
        wav_files = sorted([
            os.path.join(folder_path, f)
            for f in os.listdir(folder_path)
            if f.lower().endswith(".wav")
        ])

        if not wav_files:
            raise FileNotFoundError("No WAV files found in the specified folder.")

        valid_files = []
        reference_params = None

        # Identify compatible files
        for file in wav_files:
            with wave.open(file, 'rb') as wf:
                if reference_params is None:
                    reference_params = wf.getparams()
                    valid_files.append(file)
                elif wf.getparams() == reference_params:
                    valid_files.append(file)
                else:
                    print(f"Skipped incompatible file: {file}")

        if not valid_files:
            raise ValueError("No compatible WAV files to merge.")

        # Write merged file
        with wave.open(output_file, 'wb') as out_wav:
            out_wav.setparams(reference_params)
            for file in valid_files:
                with wave.open(file, 'rb') as wf:
                    out_wav.writeframes(wf.readframes(wf.getnframes()))

        print(f"Merged {len(valid_files)} files into: {output_file}")
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

    def plot_waveform(self):
        """
        Plots the waveform (Time vs. Amplitude).
        """
        if self.data is None:
            raise ValueError("No WAV file loaded. Call load_wav() first.")

        plt.figure(figsize=(12, 4))
        plt.plot(self.time, self.data, color='black')
        plt.xlabel("Time (s)")
        plt.ylabel("Amplitude")
        plt.title("Waveform (Time vs Amplitude)")
        plt.grid()

        # Set fixed x-axis limit (e.g., first 15 seconds)
        plt.xlim(0, 1)
        plt.ylim(-1.5, 1)

        # Set fixed y-axis limit (e.g., amplitude between -1 and 1)
        #plt.ylim(0, 1)
        plt.show()
    
    def export_amplitude_per_second(self, output_txt="amplitude_per_second.txt"):
        """
        Computes and saves the average absolute amplitude per second to a text file.

        Parameters:
            output_txt (str): Path to the output text file.
        """
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
            str: "INFESTED" or "NOT INFESTED"
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

        if std_dev > std_threshold or max_delta > delta_threshold:
            print("Classification: INFESTED")
            return "INFESTED"
        else:
            print("Classification: NOT INFESTED")
            return "NOT INFESTED"


if __name__ == '__main__':
    # Example Usage (assuming you have a folder named 'wav_files' with some .wav files)
    inspector = WavInspector()
    
    # Define folder containing WAV files
    folder_path = r"C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 5 - APR5\Test 1\Infested"

    # Define output directory and filename
    output_dir = r"C:\Users\john\Desktop\WEEVIL DATA\TEST"
    os.makedirs(output_dir, exist_ok=True)
    timestamp = datetime.now().strftime("%d-%b-%Y_%I-%M-%S%p")
    merged_output = f"TIBOK NG UOD_{timestamp}.wav"
    filepath = os.path.join(output_dir, merged_output)

    # Merge WAV files
    merged_file = inspector.merge_wav_folder(folder_path, output_file=filepath)
    print(f"Merged file created: {merged_file}")

    # Load and analyze WAV
    inspector.load_wav(merged_file)
    print("Merged WAV file loaded successfully.")

    # Export amplitude per second
    inspector.export_amplitude_per_second(
        r"C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 5 - APR5\Test 1\Infested_Amplitude.txt"
    )

    # Plot waveform
    inspector.plot_waveform()

    # Classify infestation
    classification = inspector.classify_infestation()
    print(f"Result: {classification}")