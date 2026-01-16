<?php
// worker_info_api.php - Returns worker information by ID, primarily for the face attendance modal.

// Set the content type header for JSON response
header('Content-Type: application/json');

// Include the database connection
include "lib_common.php";

// Define the default fallback image path (relative to where the JavaScript/client will access it)
$DEFAULT_PHOTO_PATH = '../img/001.webp';

// --- 1. Validate Input ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['status' => 'fail', 'msg' => 'Invalid worker ID provided.']);
    exit;
}

// --- 2. Fetch Data Securely using Prepared Statements ---
$stmt = $conn->prepare("SELECT id, name, email, phone, photo, aadhaar, address, salary, joining_date, status FROM workers WHERE id=?");

if (!$stmt) {
    // Handle prepare error
    $conn->close();
    echo json_encode(['status' => 'fail', 'msg' => 'Database error during preparation.']);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    
    // --- 3. Process Photo Path ---
    $photoPath = $row['photo'];
    $resolvedPhotoPath = $DEFAULT_PHOTO_PATH;

    if (!empty($photoPath)) {
        // Assuming the script runs from '../admin' context, we check potential locations.
        // We prioritize the path stored in the DB if it exists.
        
        // Scenario 1: Path is already relative to the expected client path (e.g., starts with 'uploads/')
        if (strpos($photoPath, 'uploads/') === 0) {
            // Check if file exists relative to the current script's directory
            if (file_exists(__DIR__ . '/' . $photoPath)) {
                $resolvedPhotoPath = $photoPath;
            } else {
                // Assuming client accesses it relative to root, which is usually "../" from the modal context
                $resolvedPhotoPath = '../' . $photoPath; 
            }
        } 
        
        // Scenario 2: Assume path is just the filename or a simple relative path
        elseif (file_exists('../admin/' . $photoPath)) {
             $resolvedPhotoPath = '../admin/' . $photoPath;
        } 
        
        // Scenario 3: If stored path is just the filename, check 'uploads/'
        elseif (file_exists('../admin/uploads/' . $photoPath)) {
            $resolvedPhotoPath = '../admin/uploads/' . $photoPath;
        }
        
        // NOTE: The original logic was complex. We rely on the stored path being correct 
        // or falling back to the default image if the file doesn't exist at common places.
    }
    
    // Final assignment to the output array
    $row['photo'] = $resolvedPhotoPath;

    // --- 4. Clean Null Values ---
    // Ensure all required fields are present and not null for frontend safety
    foreach (["name", "email", "phone", "aadhaar", "address", "salary", "joining_date", "status"] as $f) {
        if (!isset($row[$f]) || $row[$f] === null) {
            $row[$f] = "";
        }
    }

    // --- 5. Return Success Response ---
    $stmt->close();
    $conn->close();
    echo json_encode(['status' => 'success', 'worker' => $row]);

} else {
    // --- 6. Return Not Found Response ---
    $stmt->close();
    $conn->close();
    echo json_encode(['status' => 'fail', 'msg' => 'Worker not found.']);
}