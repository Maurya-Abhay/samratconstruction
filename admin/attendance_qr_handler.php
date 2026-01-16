<?php
// attendance_qr_handler.php - Handle QR code attendance marking
session_start();
include 'lib_common.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

$response = ['success' => false, 'message' => ''];

// Ensure worker_attendance has check_in/check_out columns for QR flow
$hasCheckIn = false; $hasCheckOut = false;
if ($res = $conn->query("SHOW COLUMNS FROM worker_attendance LIKE 'check_in'")) { $hasCheckIn = $res->num_rows > 0; $res->free(); }
if ($res = $conn->query("SHOW COLUMNS FROM worker_attendance LIKE 'check_out'")) { $hasCheckOut = $res->num_rows > 0; $res->free(); }
if (!$hasCheckIn) { $conn->query("ALTER TABLE worker_attendance ADD COLUMN check_in TIME NULL AFTER status"); }
if (!$hasCheckOut) { $conn->query("ALTER TABLE worker_attendance ADD COLUMN check_out TIME NULL AFTER check_in"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'qr_mark') {
    $worker_id = (int)$_POST['worker_id'];
    $date = $_POST['date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'Present';
    $check_in = $_POST['check_in'] ?? date('H:i:s');
    if ($check_in && strlen($check_in) === 5) { $check_in .= ':00'; }

    if ($worker_id <= 0) {
        $response['message'] = 'Invalid worker ID.';
        echo json_encode($response);
        exit;
    }

    // Fetch worker details
    $stmt = $conn->prepare("SELECT name FROM workers WHERE id = ?");
    $stmt->bind_param("i", $worker_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = 'Worker not found.';
        echo json_encode($response);
        exit;
    }

    $worker = $result->fetch_assoc();
    $worker_name = $worker['name'];

    // Check if attendance already exists
    $stmt = $conn->prepare("SELECT id FROM worker_attendance WHERE worker_id = ? AND date = ?");
    $stmt->bind_param("is", $worker_id, $date);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Update check-out time if already marked
        $stmt2 = $conn->prepare("UPDATE worker_attendance SET check_out = ? WHERE worker_id = ? AND date = ?");
        $check_out = date('H:i:s');
        $stmt2->bind_param("sis", $check_out, $worker_id, $date);
        
        if ($stmt2->execute()) {
            $response['success'] = true;
            $response['message'] = 'Check-out time updated.';
            $response['worker_name'] = $worker_name;
        } else {
            $response['message'] = 'Failed to update attendance.';
        }
    } else {
        // Insert new attendance record
        $stmt2 = $conn->prepare("INSERT INTO worker_attendance (worker_id, date, status, check_in) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isss", $worker_id, $date, $status, $check_in);
        
        if ($stmt2->execute()) {
            $response['success'] = true;
            $response['message'] = 'Attendance marked successfully!';
            $response['worker_name'] = $worker_name;
        } else {
            $response['message'] = 'Failed to mark attendance.';
        }
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
