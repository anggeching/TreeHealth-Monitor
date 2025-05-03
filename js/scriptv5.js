document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content'); 
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebarLogout = document.querySelector('.sidebar .logout-btn'); // Sidebar logout
    const mobileLogout = document.querySelector('.logout-mobile'); // Right-side logout

    let isSidebarOpen = false; // Tracks sidebar state on small screens

    function toggleSidebar() {
        if (window.innerWidth > 768) return; // Prevent toggling on large screens

        isSidebarOpen = !isSidebarOpen; // Toggle state
        sidebar.classList.toggle('active', isSidebarOpen);

        // Adjust content margin when sidebar is open or closed
        content.style.marginLeft = isSidebarOpen ? "180px" : "0";

        // Change button icon
        menuToggle.innerHTML = isSidebarOpen 
            ? '<i class="fas fa-times"></i>'  // Close icon when open
            : '<i class="fas fa-bars"></i>'; // Hamburger icon when closed
    }

    // Ensure sidebar and menu button default correctly
    function initializeSidebar() {
        if (window.innerWidth > 768) {
            // Always open on large screens
            sidebar.classList.add("active");
            content.style.marginLeft = "220px";
            menuToggle.innerHTML = '<i class="fas fa-times"></i>'; // Close icon
        } else if (!isSidebarOpen) {
            // Default closed on small screens (if not manually opened)
            sidebar.classList.remove("active");
            content.style.marginLeft = "0";
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>'; // Hamburger icon
        }
    }

    // Ensure the menu toggle button exists before adding event listener
    if (menuToggle) {
        menuToggle.addEventListener("click", function (event) {
            event.stopPropagation(); // Prevents event bubbling issues
            toggleSidebar();
        });
    } else {
        console.error("Menu toggle button not found!");
    }

    // Adjust Logout Buttons Based on Screen Size
    function adjustLogoutButtons() {
        if (window.innerWidth > 768) {
            if (sidebarLogout) sidebarLogout.style.display = "block"; // Show sidebar logout
            if (mobileLogout) mobileLogout.style.display = "none"; // Hide mobile logout
        } else {
            if (sidebarLogout) sidebarLogout.style.display = "none"; // Hide sidebar logout
            if (mobileLogout) mobileLogout.style.display = "flex"; // Show mobile logout
        }
    }

    // Run adjustments on page load and on resize
    initializeSidebar();
    adjustLogoutButtons();

    window.addEventListener('resize', function () {
        adjustLogoutButtons();
        initializeSidebar(); // Apply sidebar rules but keep manual state
    });

    // Logout Button Functionality
    if (mobileLogout) {
        mobileLogout.addEventListener("click", function() {
            window.location.href = "logout.php"; // Change this to your actual logout script
        });
    }
});
