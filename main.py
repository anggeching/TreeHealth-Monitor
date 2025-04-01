import numpy as np
from scipy.io import wavfile
from scipy.fft import fft
import matplotlib.pyplot as plt

# Load the WAV file
file_path = 'rpw.wav'  # Replace with your file path
sample_rate, data = wavfile.read(file_path)

# Convert stereo to mono if needed
if len(data.shape) > 1:
    data = np.mean(data, axis=1)

# Apply FFT
n = len(data)
yf = fft(data)
xf = np.fft.fftfreq(n, 1 / sample_rate)

# Get the magnitude
magnitude = np.abs(yf)

# Ignore negative frequencies
positive_freqs = xf[:n // 2]
positive_magnitude = magnitude[:n // 2]

# Identify the steady frequency
# Bin the frequency spectrum to group close frequencies
num_bins = 100
hist, bin_edges = np.histogram(positive_freqs, bins=num_bins, weights=positive_magnitude)

# Find the bin with the highest sum of magnitudes (steady frequency)
steady_bin_index = np.argmax(hist)
steady_frequency = (bin_edges[steady_bin_index] + bin_edges[steady_bin_index + 1]) / 2

# Find the peak value in the steady frequency range
bin_mask = (positive_freqs >= bin_edges[steady_bin_index]) & (positive_freqs < bin_edges[steady_bin_index + 1])
peak_value_steady = np.max(positive_magnitude[bin_mask])

# Plot the frequency spectrum
plt.figure(figsize=(12, 6))
plt.plot(positive_freqs, positive_magnitude)
plt.title(f'Steady Frequency: {steady_frequency:.2f} Hz, Peak Value: {peak_value_steady:.2f}')
plt.xlabel('Frequency (Hz)')
plt.ylabel('Amplitude')
plt.grid()
plt.show()

# Print the results
print(f'Steady Frequency: {steady_frequency:.2f} Hz ({steady_frequency / 1000:.2f} kHz)')
print(f'Peak Value (Steady Frequency): {peak_value_steady:.2f}')