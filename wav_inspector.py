import numpy as np
from scipy.io import wavfile
import matplotlib.pyplot as plt
import scipy.signal as signal
from pydub import AudioSegment
import os

AudioSegment.converter = r"C:\ffmpeg\bin\ffmpeg.exe" 

class WavInspector:
    def __init__(self, file_path=None):
        self.file_path = file_path
        self.sample_rate = None
        self.data = None
        self.time = None

    def merge_wav_folder(self, folder_path, output_file="merged_output.wav"):
            """
            Merges all WAV files in a given folder into one continuous WAV file.

            Parameters:
                folder_path (str): Path to the folder containing WAV files.
                output_file (str): Name of the output merged file.

            Returns:
                str: Path of the merged file.
            """
            # Get all .wav files from the folder and sort them alphabetically
            wav_files = sorted([
                os.path.join(folder_path, f) 
                for f in os.listdir(folder_path) 
                if f.lower().endswith(".wav")
            ])

            if not wav_files:
                raise FileNotFoundError("No WAV files found in the specified folder.")

            combined = AudioSegment.empty()

            for file in wav_files:
                audio = AudioSegment.from_wav(file)
                combined += audio  # Append each file

            combined.export(output_file, format="wav")
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
