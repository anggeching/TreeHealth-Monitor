from wav_inspector import WavInspector

# Specify the folder containing WAV files
folder_path = r"C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 2 - APR1\test1 - Accelerometer only small probe"

from wav_inspector import WavInspector

# Path to merged file
merged_file = "merged_weevil_data.wav"

# Create an instance of WavInspector
inspector = WavInspector()

# Load the merged WAV file
inspector.load_wav(merged_file)

# Plot the waveform
inspector.plot_waveform()

