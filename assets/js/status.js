function updateStatusOnLoad() {
    const storedUsername = sessionStorage.getItem('username');
    if (storedUsername) {
      fetch('backend/status.php', { // Updated URL to the combined file
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'username=' + encodeURIComponent(storedUsername),
      })
      .then(response => {
        if (!response.ok) {
          console.error('Failed to update status:', response.status);
          // Optionally handle errors, e.g., clear sessionStorage and redirect to login
        }
      })
      .catch(error => {
        console.error('Error updating status:', error);
        // Optionally handle errors
      });
    }
  }

  // Call the function when the page loads
  window.onload = updateStatusOnLoad;