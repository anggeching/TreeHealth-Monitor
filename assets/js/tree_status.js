
function updatetreestatus() {
    fetch('./api/tree_status.php') // Replace with the correct path
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const tableBody = document.getElementById("tree_status");
                tableBody.innerHTML = ""; // Clear previous data

                data.data.forEach(item => {
                    const row = document.createElement("tr");

                    const dateCell = document.createElement("td");
                    dateCell.textContent = item.date;

                    const classificationCell = document.createElement("td");
                    classificationCell.textContent = item.classification;

                    row.appendChild(dateCell);
                    row.appendChild(classificationCell);
                    tableBody.appendChild(row);
                });
            } else {
                console.error("Failed to fetch data:", data.message);
            }
        })
        .catch(error => {
            console.error("Error fetching tree health data:", error);
        });
    }
    
    // Initial call
    updatetreestatus();
    
    // Call every 5 seconds (5000 ms)
    setInterval(updatetreestatus, 3000);
    