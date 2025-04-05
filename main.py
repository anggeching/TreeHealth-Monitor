from wav_inspector import WavInspector

# Define the folder containing WAV files
folder_path = r"C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 5 - APR5\Test 1\Clean"

# Define output merged file name
merged_output = "kusina ouside box - qiet.wav"

# Create instance
inspector = WavInspector()

# Merge WAV files
merged_file = inspector.merge_wav_folder(folder_path, output_file=merged_output)
print(f"Merged file created: {merged_file}")

# Load merged WAV file
inspector.load_wav(merged_file)
print("Merged WAV file loaded successfully.")

# Export amplitude per second
#inspector.export_amplitude_per_second(r"C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 5 - APR5\Test 1\Infested_Amplitude.txt")

# Plot waveform
#inspector.plot_waveform()

# Apply the threshold-based rule
classification = inspector.classify_infestation()
print(f"Result: {classification}")

