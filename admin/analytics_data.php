<?php
// Returns aggregated traffic data as JSON for the last 30 days.
header('Content-Type: application/json');
include __DIR__ . '/database.php';

$days = isset($_GET['days']) ? max(1, min(90, intval($_GET['days']))) : 30;

// Ensure table exists (in case API called before any logged visit)
$conn->query("CREATE TABLE IF NOT EXISTS analytics_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(255) NOT NULL,
    referer VARCHAR(512) NULL,
    user_agent VARCHAR(512) NULL,
    ip VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_path_created (path, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

$sql = "SELECT DATE(created_at) as d, COUNT(*) as c FROM analytics_visits WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY d";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $startDate);
$stmt->execute();
$res = $stmt->get_result();

// Build continuous date series
$map = [];
while ($row = $res->fetch_assoc()) {
    $map[$row['d']] = (int)$row['c'];
}

$labels = [];
$counts = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime('-' . $i . ' days'));
    $labels[] = $d;
    $counts[] = isset($map[$d]) ? $map[$d] : 0;
}

echo json_encode([
    'labels' => $labels,
    'counts' => $counts,
]);
