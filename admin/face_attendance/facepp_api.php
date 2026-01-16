<?php
// Face++ API: Detect faces in an image
include_once 'facepp_config.php';

function facepp_detect_face($imagePath) {
    global $facepp_api_key, $facepp_api_secret;
    $url = 'https://api-us.faceplusplus.com/facepp/v3/detect';
    $imageData = base64_encode(file_get_contents($imagePath));
    $postFields = [
        'api_key' => $facepp_api_key,
        'api_secret' => $facepp_api_secret,
        'image_base64' => $imageData,
        'return_landmark' => 0,
        'return_attributes' => ''
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return ['error' => $err];
    return json_decode($response, true);
}
?>
