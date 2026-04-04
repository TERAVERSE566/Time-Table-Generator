<?php
require_once 'db.php';

// Safely add columns - each ALTER wrapped in try/catch so it skips if column already exists
$alterStatements = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS employee_id VARCHAR(50) NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS specialization VARCHAR(100) NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS availability VARCHAR(50) DEFAULT 'available'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS roll_number VARCHAR(50) NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS batch_year VARCHAR(20) NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS section VARCHAR(10) DEFAULT 'A'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS student_status VARCHAR(30) DEFAULT 'Active'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS current_year INT DEFAULT 1",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS cgpa DECIMAL(4,2) DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS attendance_percent INT DEFAULT 100",
];

echo "<h2>Database Update Script</h2><pre>\n";
foreach ($alterStatements as $sql) {
    try {
        if ($conn->query($sql)) {
            echo "✅ OK: $sql\n";
        }
    } catch (Exception $e) {
        // Column likely already exists
        echo "⚠️ Skipped (already exists): $sql\n";
    }
}
echo "\n✅ All updates complete!</pre>";
?>
