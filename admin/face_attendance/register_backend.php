<?php
// Face++ API: Register a new face (store face_token and user info in DB)
include_once 'facepp_api.php';
include_once '../database.php';

// Table create (if not exists)
$createTable = "CREATE TABLE IF NOT EXISTS workers_face (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    face_token VARCHAR(128) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $user_id = $_POST['user_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $result = facepp_detect_face($_FILES['image']['tmp_name']);
    if (isset($result['faces'][0]['face_token'])) {
        $face_token = $result['faces'][0]['face_token'];
        // Save to DB
        $stmt = mysqli_prepare($conn, "INSERT INTO workers_face (user_id, name, face_token) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iss', $user_id, $name, $face_token);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($ok) {
            echo json_encode(['status'=>'success','face_token'=>$face_token,'msg'=>'Face registered & saved!']);
        } else {
            echo json_encode(['status'=>'fail','msg'=>'DB save error','details'=>mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status'=>'fail','msg'=>'Face not detected','details'=>$result]);
    }
    exit;
}
echo json_encode(['status'=>'fail','msg'=>'No image uploaded']);
?>
