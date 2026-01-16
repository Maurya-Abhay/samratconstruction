
<?php

// worker_info_api.php - returns worker info by id (for face attendance modal)

header('Content-Type: application/json');

include "../admin/database.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {

    echo json_encode(['status'=>'fail','msg'=>'Invalid worker id']);

    exit;

}

$stmt = $conn->prepare("SELECT id, name, email, phone, photo, aadhaar, address, salary FROM workers WHERE id=?");


$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // If photo is missing, use a default image
    $photoPath = $row['photo'];
    $photoFullPath = '';
    if ($photoPath) {
        if (strpos($photoPath, 'uploads/') === 0) {
            $photoFullPath = '../admin/' . $photoPath;
        } elseif (file_exists('../admin/uploads/' . $photoPath)) {
            $photoFullPath = '../admin/uploads/' . $photoPath;
        } elseif (file_exists('../admin/' . $photoPath)) {
            $photoFullPath = '../admin/' . $photoPath;
        } else {
            $photoFullPath = '../img/001.webp';
        }
    } else {
        $photoFullPath = '../img/001.webp';
    }
    $row['photo'] = $photoFullPath;
    // Ensure all fields are non-null strings for frontend
    foreach (["name","email","phone","aadhaar","address","salary"] as $f) {
        if (!isset($row[$f]) || $row[$f] === null) $row[$f] = "";
    }
    echo json_encode(['status'=>'success','worker'=>$row]);
} else {
    echo json_encode(['status'=>'fail','msg'=>'Worker not found']);
}

?>