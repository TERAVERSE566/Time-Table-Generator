document.addEventListener("DOMContentLoaded", () => {
    // Apply saved theme color
    const savedColor = localStorage.getItem('themeColor');
    if (savedColor) {
        document.documentElement.style.setProperty('--navy', savedColor);
        document.documentElement.style.setProperty('--primary', savedColor);
    }
    
    // Apply dark mode
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
});
