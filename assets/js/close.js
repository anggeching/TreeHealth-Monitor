
function logoutUser() {
    fetch('backend/logout.php')
    .then(response => {
      if (response.ok) {
        window.location.href = "../index.html";
      } else {
        console.error('Logout failed:', response.status);
        window.location.href = "../index.html";
      }
    })
    .catch(error => {
      console.error('Error during logout:', error);
      window.location.href = "../index.html";
    });
}

function setInactiveStatus() {
    const username = sessionStorage.getItem('username');
    if (username) {
        fetch('backend/status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `username=${encodeURIComponent(username)}&logout_on_close=true`
        }).then(response => {
            if (!response.ok) {
                console.error('Failed to set inactive status:', response.status);
            }
            // We don't need to do anything on success as the tab is closing
        }).catch(error => {
            console.error('Error setting inactive status:', error);
        });
    }
}

// closing tab and welcome greet
document.addEventListener("DOMContentLoaded", function() {
    const logoutButtons = document.querySelectorAll('.logout-btn, .logout-mobile');
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            logoutUser();
        });
    });

    const username = sessionStorage.getItem('username');
    if (!username) {
        window.location.href = "../index.html";
    } else {
        document.getElementById("welcome-text").textContent =
            `WELCOME, ${username.toUpperCase()}!`;
    }

    // Listen for the beforeunload event
    window.addEventListener('beforeunload', setInactiveStatus);
});
