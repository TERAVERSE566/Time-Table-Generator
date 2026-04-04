# Time-Table-Generator (Literary Planner)

An intelligent timetable generation and management system for educational institutions.

## Features
- **Smart Timetable Generation:** Uses algorithms to map courses, faculty, and rooms without conflicts.
- **Role-based Dashboards:** Dedicated panels for Admins, Faculty, and Students.
- **Interactive Visualizations:** Chart.js integration for attendance and scheduling metrics.
- **Data Management:** DataTables.net functionality for easy searching and sorting.
- **Export Capabilities:** Export timetables directly to PDF and Excel.
- **Premium UI:** Glassmorphism, Dark Mode support, and fully responsive design.

## Local Setup (XAMPP/WAMP)
1. Clone the repository.
2. Move the project folder into your `htdocs` (or `www`) directory.
3. Import the `127_0_0_1.sql` database file into your local MySQL server (using phpMyAdmin or similar).
4. Update credentials in `db.php` if your local MySQL uses a different username/password.
5. In your browser, navigate to: `http://localhost/Time-Table-Generator/index.php`.

## Docker Setup
This project includes a `Dockerfile` and `.dockerignore` for easy container deployment.

To build and run the application locally using Docker:

```bash
# Build the Docker image
docker build -t timetable-gen .

# Run the container mapping port 80
docker run -d -p 8080:80 --name timetable-app timetable-gen
```
Then visit `http://localhost:8080` in your browser.