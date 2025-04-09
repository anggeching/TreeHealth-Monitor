from wav_inspector import WavInspector
import os
from datetime import datetime

# Create instance
inspector = WavInspector()

# Define folder containing WAV files
folder_path = r"C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 5 - APR5\Test 1\Infested"

# Define output directory and filename
output_dir = r"C:\Users\john\Desktop\WEEVIL DATA\TEST 6 - APR9"
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
    f"C:/Users/john/Desktop/WEEVIL DATA/Actual/TEST 6 - APR9/Amplitude_{timestamp}.txt"
)

# Plot waveform
inspector.plot_waveform()

# Classify infestation
classification = inspector.classify_infestation()
print(f"Result: {classification}")
