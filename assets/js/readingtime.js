function updatereadingtime() {
    fetch('./api/reading_time.php') // Replace with the correct path
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const tableBody = document.getElementById("reading_time");
                tableBody.innerHTML = ""; // Clear previous data

                data.data.forEach(entry => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${entry.date}</td>
                        <td>${entry.time}</td>
                    `;
                    tableBody.appendChild(row);
                });
            } else {
                console.error("Failed to fetch data:", data.message);
            }
        })
        .catch(error => {
            console.error("Error fetching reading_time.php:", error);
        });
        }
    
    // Initial call
    updatereadingtime();
    
    // Call every 5 seconds (5000 ms)
    setInterval(updatereadingtime, 3000);
