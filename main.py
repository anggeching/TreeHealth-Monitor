from wav_inspector import WavInspector

# Define the folder containing WAV files (Update this path)
folder_path = r"C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 4 - APR2\Test 4 - Kusina Angel at loob box -infested"

# Define output merged file
merged_output = "kusina ouside box - qiet.wav"

# Create an instance of WavInspector
inspector = WavInspector()

# Step 1: Merge all WAV files in the folder
merged_file = inspector.merge_wav_folder(folder_path, output_file=merged_output)
print(f"Merged file created: {merged_file}")

# Step 2: Load the merged WAV file
inspector.load_wav(merged_file)
print("Merged WAV file loaded successfully.")

# Step 3: Plot the waveform
inspector.plot_waveform()
