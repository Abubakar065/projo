<?php
/**
 * Image Upload Handler
 * Project Tracking and Reporting Application
 */

define('PTRA_ACCESS', true);
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/auth.php';

startSecureSession();
requireRole('pm'); // PM or Admin can upload images

$db = getDB();

// Check if request is POST and has required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['project_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    header('Location: admin/dashboard.php?error=invalid_token');
    exit();
}

$project_id = (int)$_POST['project_id'];
$caption = sanitizeInput($_POST['caption'] ?? '');

// Verify project exists and user has access
$stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin/dashboard.php?error=project_not_found');
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'No file uploaded or upload error occurred.';
    header('Location: project.php?id=' . $project_id . '&error=' . urlencode($error_message));
    exit();
}

$file = $_FILES['image'];

// Validate file size
if ($file['size'] > MAX_FILE_SIZE) {
    $error_message = 'File size exceeds maximum allowed size of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.';
    header('Location: project.php?id=' . $project_id . '&error=' . urlencode($error_message));
    exit();
}

// Validate file type
$file_info = pathinfo($file['name']);
$file_extension = strtolower($file_info['extension']);

if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
    $error_message = 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS);
    header('Location: project.php?id=' . $project_id . '&error=' . urlencode($error_message));
    exit();
}

// Validate file is actually an image
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    $error_message = 'Uploaded file is not a valid image.';
    header('Location: project.php?id=' . $project_id . '&error=' . urlencode($error_message));
    exit();
}

// Generate unique filename
$unique_filename = uniqid('img_' . $project_id . '_', true) . '.' . $file_extension;
$upload_path = UPLOAD_DIR . $unique_filename;

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        $error_message = 'Failed to create upload directory.';
        header('Location: project.php?id=' . $project_id . '&error=' . urlencode($error_message));
        exit();
    }
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    $error_message = 'Failed to save uploaded file.';
    header('Location: project.php?id=' . $project_id . '&error=' . urlencode($error_message));
    exit();
}

// Save image record to database
$stmt = $db->prepare("
    INSERT INTO project_images (project_id, image_filename, image_caption, uploaded_by) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("issi", $project_id, $unique_filename, $caption, $_SESSION['user_id']);

if ($stmt->execute()) {
    // Resize image if it's too large (optional optimization)
    resizeImageIfNeeded($upload_path, 1200, 800);
    
    header('Location: project.php?id=' . $project_id . '&success=image_uploaded');
} else {
    // Delete the uploaded file if database insert failed
    unlink($upload_path);
    $error_message = 'Failed to save image information.';
    header('Location: project.php?id=' . $project_id . '&error=' . urlencode($error_message));
}

exit();

/**
 * Resize image if it exceeds maximum dimensions
 */
function resizeImageIfNeeded($file_path, $max_width, $max_height) {
    $image_info = getimagesize($file_path);
    if (!$image_info) return false;
    
    list($width, $height, $type) = $image_info;
    
    // Check if resize is needed
    if ($width <= $max_width && $height <= $max_height) {
        return true;
    }
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = (int)($width * $ratio);
    $new_height = (int)($height * $ratio);
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($file_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($file_path);
            break;
        default:
            return false;
    }
    
    if (!$source) return false;
    
    // Create new image
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($destination, $file_path, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($destination, $file_path, 6);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($destination, $file_path);
            break;
        default:
            $result = false;
    }
    
    // Clean up memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return $result;
}
?>
