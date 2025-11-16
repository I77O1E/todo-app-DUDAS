<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    http_response_code(403);
    echo "Not logged in";
    exit;
}

// Get submitted values
$bio = trim($_POST['bio'] ?? '');
$tag = $_POST['tag'] ?? '';

// Fetch current profile picture
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current = $stmt->fetch();
$profile_pic = $current['profile_pic'] ?? 'default.png';

// Handle file upload
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $file = $_FILES['profile_pic'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validate extension
    $allowed_ext = ['jpg', 'jpeg', 'png'];
    if (in_array($ext, $allowed_ext)) {

        // Optional: limit file size to 10 MB
        if ($file['size'] <= 10000000) {

            // Use absolute path
            $upload_dir = __DIR__ . "uploads/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true); // create folder if missing
            }

            $new_filename = time() . "_" . basename($file['name']);
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Store relative path in DB
                $profile_pic = "uploads/" . $new_filename;
            } else {
                file_put_contents("debug_upload.txt", "Failed to move file: " . $file['tmp_name'] . " to " . $destination . "\n", FILE_APPEND);
            }

        } else {
            file_put_contents("debug_upload.txt", "File too large: " . $file['size'] . "\n", FILE_APPEND);
        }

    } else {
        file_put_contents("debug_upload.txt", "Invalid file type: " . $ext . "\n", FILE_APPEND);
    }
}

// Update user data
$update = $pdo->prepare("UPDATE users SET bio = ?, tag = ?, profile_pic = ? WHERE id = ?");
$update->execute([$bio, $tag, $profile_pic, $user_id]);

echo "success";
