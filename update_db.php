<?php
require_once 'db.php';

// Safely add columns - each ALTER wrapped individually so duplicates are skipped
$alterStatements = [
    "ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) NULL",
    "ALTER TABLE users ADD COLUMN specialization VARCHAR(100) NULL",
    "ALTER TABLE users ADD COLUMN availability VARCHAR(50) DEFAULT 'available'",
    "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL",
    "ALTER TABLE users ADD COLUMN roll_number VARCHAR(50) NULL",
    "ALTER TABLE users ADD COLUMN batch_year VARCHAR(20) NULL",
    "ALTER TABLE users ADD COLUMN section VARCHAR(10) DEFAULT 'A'",
    "ALTER TABLE users ADD COLUMN student_status VARCHAR(30) DEFAULT 'Active'",
    "ALTER TABLE users ADD COLUMN current_year INT DEFAULT 1",
    "ALTER TABLE users ADD COLUMN cgpa DECIMAL(4,2) DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN attendance_percent INT DEFAULT 100",
];

// Temporarily disable strict error reporting so we can handle errors manually
mysqli_report(MYSQLI_REPORT_OFF);

echo "<h2>Database Update Script</h2><pre>\n";
foreach ($alterStatements as $sql) {
    $result = @$conn->query($sql);
    if ($result) {
        echo "✅ Added: $sql\n";
    } else {
        $err = $conn->error;
        if (stripos($err, 'Duplicate') !== false || stripos($err, 'already exists') !== false) {
            echo "⏭️ Skipped (already exists): $sql\n";
        } else {
            echo "❌ Error: $err — $sql\n";
        }
    }
}
echo "\n✅ All updates complete!</pre>";

// Restore strict error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
