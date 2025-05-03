function updateStatusBox() {
    fetch('./api/statusbox.php') // Replace with the correct path
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.length > 0) {
                // Get the latest classification (you can change logic if needed)
                const latest = data.data[data.data.length - 1];
                const classification = latest.classification.toLowerCase();
    
                const statusBox = document.getElementById('statusBox');
    
                if (classification === 'infested') {
                    statusBox.style.border = '10px solid red';
                } else {
                    statusBox.style.border = '10px solid green';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching status data:', error);
        });
    }
    
    // Initial call
    updateStatusBox();
    
    // Call every 5 seconds (5000 ms)
    setInterval(updateStatusBox, 3000);
    