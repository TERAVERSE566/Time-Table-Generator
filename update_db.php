<?php
require_once 'db.php';

try {
    $sqls = [
        "ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) NULL;",
        "ALTER TABLE users ADD COLUMN specialization VARCHAR(100) NULL;",
        "ALTER TABLE users ADD COLUMN availability VARCHAR(50) DEFAULT 'available';"
    ];

    foreach ($sqls as $sql) {
        if ($conn->query($sql)) {
            echo "Successfully ran: $sql\n";
        } else {
            echo "Error: " . $conn->error . "\n";
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
