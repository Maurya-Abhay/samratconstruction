<?php
// Face++ API: Match face for attendance (compare with registered faces)
include_once 'facepp_api.php';
include_once '../database.php';

// 1. Get all registered face_tokens from DB
$registered_faces = [];
$q = mysqli_query($conn, "SELECT user_id, name, face_token FROM workers_face");
while ($row = mysqli_fetch_assoc($q)) {
	$registered_faces[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
	$result = facepp_detect_face($_FILES['image']['tmp_name']);
	if (isset($result['faces'][0]['face_token'])) {
		$face_token = $result['faces'][0]['face_token'];
		// 2. Compare with all registered faces using Face++ search API
		$match = false;
		foreach ($registered_faces as $face) {
			$verify = facepp_compare_face($face_token, $face['face_token']);
			if (($verify['confidence'] ?? 0) > 80) { // 80+ confidence means match
				$match = $face;
				break;
			}
		}
		if ($match) {
			// Mark attendance for $match['user_id']
			echo json_encode(['status'=>'success','user'=>$match,'msg'=>'Attendance marked!']);
		} else {
			echo json_encode(['status'=>'fail','msg'=>'No match found']);
		}
	} else {
		echo json_encode(['status'=>'fail','msg'=>'Face not detected','details'=>$result]);
	}
	exit;
}
echo json_encode(['status'=>'fail','msg'=>'No image uploaded']);

// Helper: Face++ compare face
function facepp_compare_face($face_token1, $face_token2) {
	global $facepp_api_key, $facepp_api_secret;
	$url = 'https://api-us.faceplusplus.com/facepp/v3/compare';
	$postFields = [
		'api_key' => $facepp_api_key,
		'api_secret' => $facepp_api_secret,
		'face_token1' => $face_token1,
		'face_token2' => $face_token2
	];
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	return json_decode($response, true);
}
?>
