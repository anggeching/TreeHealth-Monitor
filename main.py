import numpy as np
from scipy.io import wavfile
from scipy.fft import fft
import matplotlib.pyplot as plt
import scipy.signal as signal


# Load the WAV file
file_path = r'C:\Users\john\Desktop\WEEVIL DATA\Actual\test3-15sec.wav'  # Replace withyour file path
#file_path = r'C:\Users\john\Desktop\WEEVIL DATA\treevibes-potamitis\lab\lab\infested\infested_1.wav'  # Replace with your file path

sample_rate, data = wavfile.read(file_path)

# Convert to mono if stereo
if len(data.shape) > 1:
    data = np.mean(data, axis=1)

# Normalize the amplitude
data = data / np.max(np.abs(data))

# Time axis
time = np.linspace(0, len(data) / sample_rate, num=len(data))

# Figure 1: Waveform (Time vs Amplitude)
plt.figure(figsize=(12, 4))
plt.plot(time, data, color='black')
plt.xlabel("Time (s)")
plt.ylabel("Amplitude")
plt.title("Figure 1: Waveform (Time vs Amplitude)")
plt.grid()
plt.show()
