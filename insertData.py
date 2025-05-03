import os
import requests
from datetime import datetime, timedelta

# --- CONFIG ---
url = 'https://weevibes.outcastph.com/api/receive_wav_batch.php'
folder_path = r'C:\Users\john\Desktop\WEEVIL DATA\Actual\TEST 12\Clean'
start_datetime = datetime.strptime('2025-04-18 18:23:00', '%Y-%m-%d %H:%M:%S')
time_step = timedelta(minutes=30)

# --- GET ALL WAV FILES ---
wav_files = sorted([f for f in os.listdir(folder_path) if f.lower().endswith('.wav')])

if not wav_files:
    print("No .wav files found in the folder.")
    exit()

# --- LOOP THROUGH FILES ---
for i, filename in enumerate(wav_files):
    file_path = os.path.join(folder_path, filename)

    if not os.path.isfile(file_path):
        print(f"Skipped (not a file): {file_path}")
        continue

    current_datetime = start_datetime + i * time_step

    # If the new time is after 23:30, roll to the next day
    if current_datetime.time() >= datetime.strptime('23:30:00', '%H:%M:%S').time():
        current_datetime += timedelta(days=1)

    # Date format: 24-Apr-2025
    date_str = current_datetime.strftime('%d-%b-%Y')
    date_str = date_str[:3] + date_str[3:].capitalize()  # Capitalize only first letter of month
    time_str = current_datetime.strftime('%H:%M:%S')

    try:
        with open(file_path, 'rb') as f:
            files = {'wav_file': (filename, f, 'audio/wav')}
            data = {
                'date': date_str,
                'time': time_str,
                'file_name': filename
            }

            response = requests.post(url, files=files, data=data)

            try:
                json_data = response.json()
            except ValueError:
                json_data = response.text

            print(f"{filename} -> {response.status_code}: {json_data}")

    except Exception as e:
        print(f"Error with file {filename}: {str(e)}")
