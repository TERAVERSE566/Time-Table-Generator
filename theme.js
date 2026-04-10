document.addEventListener("DOMContentLoaded", () => {
    // Apply saved theme color
    const savedColor = localStorage.getItem('themeColor');
    if (savedColor) {
        document.body.style.setProperty('--navy', savedColor, 'important');
        document.body.style.setProperty('--primary', savedColor, 'important');
        document.body.style.setProperty('--navy-light', savedColor, 'important');
        document.body.style.setProperty('--primary-light', savedColor, 'important');
    }
    
    // Apply dark mode
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
});
