<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_photo'];

    // Check errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "Upload failed with error code " . $file['error']]);
        exit();
    }

    // Validate type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(["success" => false, "message" => "Invalid file format. Please upload JPG or PNG."]);
        exit();
    }

    // Prepare directory
    $uploadDir = 'uploads/photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Create unique name
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
        $stmt->bind_param("si", $destPath, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['profile_photo'] = $destPath; // Update session
            echo json_encode(["success" => true, "message" => "Photo uploaded successfully", "path" => $destPath]);
        } else {
            echo json_encode(["success" => false, "message" => "Database update failed."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Failed to move uploaded file."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "No file provided."]);
}
?>
