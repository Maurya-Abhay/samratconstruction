<?php
// dashboard_data.php
// Returns dashboard stats as JSON for AJAX polling
session_start();
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
include 'lib_common.php'; // assumes DB connection $conn

// Fetch counts
$service_count = (int)$conn->query("SELECT COUNT(*) FROM services")->fetch_row()[0];
$contact_count = (int)$conn->query("SELECT COUNT(*) FROM contacts")->fetch_row()[0];
$customer_count = $contact_count;
$worker_count = (int)$conn->query("SELECT COUNT(*) FROM workers")->fetch_row()[0];
$attendance_count = (int)$conn->query("SELECT COUNT(*) FROM worker_attendance")->fetch_row()[0];
$attendance_users_count = 0;
if ($res = $conn->query("SHOW TABLES LIKE 'attendence_users'")) {
  if ($res->num_rows > 0) {
    $row = $conn->query("SELECT COUNT(*) FROM attendence_users")->fetch_row();
    $attendance_users_count = (int)($row[0] ?? 0);
  }
}
$face_register_count = 0;
if (file_exists('workers_face_data.json')) {
  $face_data = json_decode(file_get_contents('workers_face_data.json'), true);
  $face_register_count = is_array($face_data) ? count($face_data) : 0;
}
$wp_count_res = $conn->query("SELECT COUNT(*) FROM worker_payments");
$worker_payments_count = $wp_count_res ? (int)($wp_count_res->fetch_row()[0] ?? 0) : 0;
$leave_count = 0;
if ($res = $conn->query("SHOW TABLES LIKE 'worker_leaves'")) {
  if ($res->num_rows > 0) {
    $row = $conn->query("SELECT COUNT(*) FROM worker_leaves")->fetch_row();
    $leave_count = (int)($row[0] ?? 0);
  }
}
$holiday_count = 0;
if ($res = $conn->query("SHOW TABLES LIKE 'holidays'")) {
  if ($res->num_rows > 0) {
    $row = $conn->query("SELECT COUNT(*) FROM holidays")->fetch_row();
    $holiday_count = (int)($row[0] ?? 0);
  }
}
$notice_count = 0;
if ($res = $conn->query("SHOW TABLES LIKE 'notices'")) {
  if ($res->num_rows > 0) {
    $row = $conn->query("SELECT COUNT(*) FROM notices")->fetch_row();
    $notice_count = (int)($row[0] ?? 0);
  }
}
$reports_count = 0;
if ($res = $conn->query("SHOW TABLES LIKE 'reports'")) {
  if ($res->num_rows > 0) {
    $row = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row();
    $reports_count = (int)($row[0] ?? 0);
  }
}
$contract_count = $contact_count;
$budget_count = 0;
$budgetTbl = null;
if ($res = $conn->query("SHOW TABLES LIKE 'budget'")) { if ($res->num_rows > 0) { $budgetTbl = 'budget'; } }
if (!$budgetTbl) { if ($res = $conn->query("SHOW TABLES LIKE 'budgets'")) { if ($res->num_rows > 0) { $budgetTbl = 'budgets'; } } }
if ($budgetTbl) {
  $row = $conn->query("SELECT COUNT(*) FROM $budgetTbl")->fetch_row();
  $budget_count = (int)($row[0] ?? 0);
}
$open_messages_count = 0;
if ($res = $conn->query("SHOW TABLES LIKE 'contact_messages'")) {
  if ($res->num_rows > 0) {
    $row = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE status='Open'")->fetch_row();
    $open_messages_count = (int)($row[0] ?? 0);
  }
}
$pending_upi = 0;
if ($res = $conn->query("SHOW TABLES LIKE 'upi_payments'")) {
  if ($res->num_rows > 0) {
    $row = $conn->query("SELECT COUNT(*) FROM upi_payments WHERE status='Pending'")->fetch_row();
    $pending_upi = (int)($row[0] ?? 0);
  }
}

echo json_encode([
  'service_count' => $service_count,
  'contact_count' => $contact_count,
  'customer_count' => $customer_count,
  'worker_count' => $worker_count,
  'attendance_count' => $attendance_count,
  'attendance_users_count' => $attendance_users_count,
  'face_register_count' => $face_register_count,
  'worker_payments_count' => $worker_payments_count,
  'leave_count' => $leave_count,
  'holiday_count' => $holiday_count,
  'notice_count' => $notice_count,
  'reports_count' => $reports_count,
  'contract_count' => $contract_count,
  'budget_count' => $budget_count,
  'open_messages_count' => $open_messages_count,
  'pending_upi' => $pending_upi
]);
