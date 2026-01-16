<?php
// workers_api.php - Returns all workers as JSON for manual attendance dropdown
require_once 'lib_common.php';
header('Content-Type: application/json');

$workers = [];
$res = $conn->query("SELECT id, name FROM workers ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $workers[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
}
echo json_encode(['status' => 'success', 'data' => $workers]);
