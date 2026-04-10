// assets/js/script.js

// Global PDF Export function
function exportTimetablePDF(elementId) {
    const element = document.getElementById(elementId);
    if(!element) {
        alert("Cannot find timetable to export.");
        return;
    }
    const opt = {
      margin:       0.5,
      filename:     'SaaS_Timetable.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2 },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}

// Fade out alerts specifically
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000); // 5 seconds
});
