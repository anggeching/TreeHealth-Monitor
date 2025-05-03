document.addEventListener("DOMContentLoaded", function () {
    if (window.location.pathname.includes("dashboardv5.html")) {
        const updateTable = () => {
            fetch("./api/tree_status.php") // adjust the path if needed
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        const tableBody = document.getElementById("table-body");
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
        };

        // Initial fetch
        updateTable();

        // Set interval to update table every 5 seconds (5000 ms)
        setInterval(updateTable, 3000);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    if (window.location.pathname.includes("historylog.html")) {
        const updateHistory = () => {
            fetch("./api/reading_time.php")
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        const tableBody = document.getElementById("table-body");
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
        };

        // Initial fetch
        updateHistory();

        // Set interval to update history every 5 seconds (5000 ms)
        setInterval(updateHistory, 3000);
    }
});
