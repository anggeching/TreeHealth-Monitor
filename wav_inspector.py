# wav_inspector.py
import numpy as np
from scipy.io import wavfile
import matplotlib.pyplot as plt
import scipy.signal as signal
from pydub import AudioSegment

class WavInspector:
    def __init__(self, file_path=None):
        self.file_path = file_path
        self.sample_rate = None
        self.data = None
        self.time = None

        if file_path:
            self.load_wav(file_path)

    def merge_wav_files(self, wav_files, output_file="merged_output.wav"):
        """
        Merges multiple WAV files into one continuous file.

        Parameters:
            wav_files (list): List of WAV file paths to merge.
            output_file (str): Name of the output merged file.

        Returns:
            str: Path of the merged file.
        """
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
        plt.show()
