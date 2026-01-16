<?php
// Lightweight traffic logger. Include this early in request lifecycle.
// Safe to include multiple times; table creation is idempotent.

// Ensure DB connection
if (!isset($conn)) {
    include __DIR__ . '/lib_common.php';
}

// Create table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS analytics_visits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        path VARCHAR(255) NOT NULL,
        referer VARCHAR(512) NULL,
        user_agent VARCHAR(512) NULL,
        ip VARCHAR(64) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_path_created (path, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Collect basic request info
$path = isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 255) : '';
$referer = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 512) : null;
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 512) : null;
$ip = isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 64) : null;

// Insert log (prepared statement)
if ($stmt = $conn->prepare('INSERT INTO analytics_visits (path, referer, user_agent, ip) VALUES (?,?,?,?)')) {
    $stmt->bind_param('ssss', $path, $referer, $userAgent, $ip);
    $stmt->execute();
    $stmt->close();
}
