<?php

// secure_image_upload.php - Secure image processing and upload function.

// Requires PHP GD extension.

// Block direct access to this file
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    // ... (HTML Forbidden content remains the same)
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Forbidden</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:linear-gradient(135deg,#e0e7ff,#b4c2ff);min-height:100vh;display:flex;align-items:center;justify-content:center}.card{border-radius:1rem;box-shadow:0 1rem 3rem rgba(0,0,0,0.10);max-width:400px;margin:auto}</style></head><body><div class="card p-4 text-center"><h2 class="text-danger mb-3">Access Denied</h2><p class="mb-3 text-secondary">Direct access to this file is forbidden.<br>Image upload is protected for security reasons.</p><a href="index.php" class="btn btn-primary">Go to Admin Panel</a><div class="mt-3 text-muted small">&copy; 2025 Samrat Construction Private Limited</div></div></body></html>';
    exit;
}


/**
 * Uploads, validates, resizes, and saves an image file locally using the GD library.
 *
 * @param array $file The element from $_FILES (e.g., $_FILES['photo']).
 * @param string $target_dir Target directory path (e.g., 'uploads/profiles/').
 * @param int $max_size Max allowed size in bytes (default: 300KB).
 * @param array $allowed_types Allowed extensions.
 * @param int $resize_width Target width (height is proportional).
 * @return array Returns ['error' => string] or ['success' => true, 'file' => string].
 */
function secure_image_upload($file, $target_dir = 'uploads/', $max_size = 300 * 1024, $allowed_types = ['jpg','jpeg','png','webp'], $resize_width = 400) {

    // Check for upload errors
    if (!isset($file['tmp_name']) || !$file['tmp_name'] || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'No file uploaded or an internal error occurred (Error code: ' . $file['error'] . ').'];
    }
    
    // Check and create target directory
    if (!is_dir($target_dir) && !@mkdir($target_dir, 0755, true)) {
        return ['error' => "Target directory does not exist and could not be created. Check permissions."];
    }

    // 1. Validation Checks
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['error' => 'File too large. Max allowed size is ' . ($max_size / 1024) . 'KB.'];
    }

    // Basic MIME type check (for security, rely on getimagesize for true type)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (strpos($mime_type, 'image/') !== 0) {
        return ['error' => 'Invalid file MIME type detected. Only images are allowed.'];
    }

    // Check if it's a valid image using GD functions (better than relying on user input)
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return ['error' => 'File is not a valid image.'];
    }
    
    // Check extension for GD type handling
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return ['error' => 'Invalid file format. Only ' . implode(', ', $allowed_types) . ' are allowed.'];
    }

    // 2. Setup File Path
    // Use the detected extension, not the original, in case the original filename was misleading.
    $unique_file_name = hash('sha256', uniqid('', true) . microtime() . $file['name']) . '.' . $file_extension;
    $target_file = rtrim($target_dir, '/') . '/' . $unique_file_name;
    
    // 3. Image Processing (Requires GD)
    
    $src = false;
    // Create source image resource based on file extension
    switch ($file_extension) {
        case 'jpg':
        case 'jpeg':
            $src = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'png':
            $src = @imagecreatefrompng($file['tmp_name']);
            break;
        case 'webp':
            // Check if webp support is available in GD
            if (function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($file['tmp_name']);
            } else {
                return ['error' => 'WebP support is not enabled in PHP GD extension.'];
            }
            break;
        default:
            // This should ideally not be reached due to $allowed_types check
            return ['error' => 'Unsupported image type for processing.']; 
    }

    if (!$src) {
        return ['error' => 'Failed to create image resource (possible corruption or GD issue).'];
    }

    // Calculate new dimensions
    $width = imagesx($src);
    $height = imagesy($src);

    $new_width = ($width > $resize_width) ? $resize_width : $width;
    $new_height = intval($height * ($new_width / $width));

    $dst = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency for PNG
    if ($file_extension === 'png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        // Note: Transparent color handling is not strictly needed here if alpha blending is off/save alpha is on
    }

    // Resample the image
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // 4. Save the Processed Image
    $save_success = false;
    switch ($file_extension) {
        case 'jpg':
        case 'jpeg':
            $save_success = imagejpeg($dst, $target_file, 85); // Quality 85
            break;
        case 'png':
            $save_success = imagepng($dst, $target_file, 7); // Compression 7 (0=no comp, 9=max comp)
            break;
        case 'webp':
            // Use imagewebp if available
            if (function_exists('imagewebp')) {
                $save_success = imagewebp($dst, $target_file, 85); // Quality 85
            } else {
                $save_success = false; // Should have been caught earlier, but safety check
            }
            break;
    }

    // 5. Cleanup
    imagedestroy($src);
    imagedestroy($dst);
    
    // Remove the temporary uploaded file to clean up immediately
    @unlink($file['tmp_name']); 

    if ($save_success) {
        // Return the path relative to the script execution point
        return ['success' => true, 'file' => $target_file];
    } else {
        return ['error' => 'Failed to save the resized image file to disk.'];
    }
}


// --- Secure image upload handler ---
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // lib_common.php may not contain image upload utilities, 
    // but often contains DB connection or path definitions. Keep it if needed.
    // require_once __DIR__ . '/lib_common.php'; 
    
    // Define a secure, dedicated directory for uploads relative to the execution script
    $upload_dir = __DIR__ . '/../admin/uploads/secure_photos/'; 
    
    $result = secure_image_upload($_FILES['image'], $upload_dir);
    
    if (isset($result['success'])) {
        // Correct message for local file system upload
        $success = 'File uploaded securely and resized to: ' . htmlspecialchars($result['file']);
        // Here you would typically insert the file path into the database
        // Example: $conn->query("UPDATE users SET photo = '{$result['file']}' WHERE id = 1");
        
    } else {
        $error = $result['error'];
    }
}