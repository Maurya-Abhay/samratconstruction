<?php

// logs_viewer.php - Show basic analytics and activity logs

session_start();

if (!isset($_SESSION['email'])) { header('Location: index.php'); exit; }

require_once __DIR__ . '/lib_common.php';

// Ensure analytics table exists
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


// --- Fetch Key Metrics ---
$total_visits_res = $conn->query("SELECT COUNT(id) as count FROM analytics_visits");
$total_visits = $total_visits_res ? $total_visits_res->fetch_assoc()['count'] : 0;

$unique_paths_res = $conn->query("SELECT COUNT(DISTINCT path) as count FROM analytics_visits");
$unique_paths = $unique_paths_res ? $unique_paths_res->fetch_assoc()['count'] : 0;

$today_visits_res = $conn->query("SELECT COUNT(id) as count FROM analytics_visits WHERE created_at >= CURDATE()");
$today_visits = $today_visits_res ? $today_visits_res->fetch_assoc()['count'] : 0;


// CSV export MUST happen before any output or includes
if (isset($_GET['export'])) {
    $q = $_GET['q'] ?? ''; $from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? '';
    $where = '1';
    if ($q !== '') { $q1 = '%'.$conn->real_escape_string($q).'%'; $where .= " AND path LIKE '$q1'"; }
    if ($from !== '') { $from1 = $conn->real_escape_string($from); $where .= " AND created_at >= '$from1 00:00:00'"; }
    if ($to   !== '') { $to1   = $conn->real_escape_string($to);   $where .= " AND created_at <= '$to1 23:59:59'"; }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=logs_'.date('Ymd_His').'.csv');
    $out = fopen('php://output','w'); 
    fputcsv($out,['created_at','path','ip','user_agent','referer']);
    
    $res = $conn->query("SELECT created_at, path, ip, user_agent, referer FROM analytics_visits WHERE $where ORDER BY id DESC LIMIT 5000");
    if ($res) {
        while($r=$res->fetch_assoc()){ fputcsv($out,$r); }
    }
    fclose($out); 
    exit;
}

include 'topheader.php';
include 'sidenavbar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    /* Custom styles for a modern dashboard look */
    .card-metric {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .card-metric:hover {
        transform: translateY(-3px);
    }
    .metric-icon {
        font-size: 2rem;
        opacity: 0.6;
    }
    .metric-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 0;
    }
    .metric-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #343a40;
    }
    .card-log {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .table-log th {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #495057;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .table-log td {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .filter-group .form-control-sm {
        border-radius: 6px;
    }
</style>

<div class="container-fluid px-4 py-4">

    <!-- Title and Export -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0"><i class="bi bi-activity text-primary me-2"></i>Activity Logs Viewer</h3>
        <a class="btn btn-sm btn-outline-success" href="?export=1<?= htmlspecialchars(isset($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : '') ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
    </div>

    <!-- Key Metrics Section -->
    <div class="row g-4 mb-4">
        
        <!-- Total Visits -->
        <div class="col-md-4">
            <div class="card card-metric p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="metric-label">Total Recorded Visits</p>
                        <div class="metric-value"><?= number_format($total_visits) ?></div>
                    </div>
                    <i class="bi bi-bar-chart-fill text-primary metric-icon"></i>
                </div>
            </div>
        </div>

        <!-- Unique Paths -->
        <div class="col-md-4">
            <div class="card card-metric p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="metric-label">Unique Pages Tracked</p>
                        <div class="metric-value"><?= number_format($unique_paths) ?></div>
                    </div>
                    <i class="bi bi-folder-fill text-info metric-icon"></i>
                </div>
            </div>
        </div>

        <!-- Visits Today -->
        <div class="col-md-4">
            <div class="card card-metric p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="metric-label">Visits Today (<?= date('M d') ?>)</p>
                        <div class="metric-value"><?= number_format($today_visits) ?></div>
                    </div>
                    <i class="bi bi-person-fill text-success metric-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form Section -->
    <div class="card card-log mb-4 p-3">
        <form class="row g-3 align-items-end filter-group" method="get">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" class="form-control form-control-sm" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" class="form-control form-control-sm" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Path/Content Filter</label>
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="e.g., /dashboard.php, error, Chrome">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a class="btn btn-outline-danger btn-sm" href="logs_viewer.php" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>

    <!-- Log Table Section -->
    <div class="card card-log">
        <div class="card-header bg-white fw-bold text-dark border-bottom-0">Recent Activity Log (Max 200 Entries)</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 table-log">
                <thead>
                    <tr>
                        <th style="width: 120px;">Timestamp</th>
                        <th style="width: 25%;">Path</th>
                        <th style="width: 100px;">IP Address</th>
                        <th>User Agent</th>
                        <th>Referer</th>
                    </tr>
                </thead>
                <tbody>
<?php
$q = $_GET['q'] ?? ''; $from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? '';
$where = '1';

// Apply filters for path, IP, or user agent if search query 'q' is present
if ($q !== '') { 
    $q1 = '%'.$conn->real_escape_string($q).'%'; 
    $where .= " AND (path LIKE '$q1' OR user_agent LIKE '$q1' OR ip LIKE '$q1')"; 
}

if ($from !== '') { $from1 = $conn->real_escape_string($from); $where .= " AND created_at >= '$from1 00:00:00'"; }
if ($to   !== '') { $to1   = $conn->real_escape_string($to);   $where .= " AND created_at <= '$to1 23:59:59'"; }

$res = $conn->query("SELECT created_at, path, ip, user_agent, referer FROM analytics_visits WHERE $where ORDER BY id DESC LIMIT 200");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $time_format = date('d M, H:i:s', strtotime($r['created_at']));
        echo '<tr>';
        echo '<td class="text-muted">' . htmlspecialchars($time_format) . '</td>';
        echo '<td><span class="badge bg-light text-dark fw-normal">' . htmlspecialchars($r['path']) . '</span></td>';
        echo '<td>' . htmlspecialchars($r['ip']) . '</td>';
        echo '<td class="text-truncate" style="max-width:300px" title="' . htmlspecialchars($r['user_agent']) . '">' . htmlspecialchars($r['user_agent']) . '</td>';
        echo '<td class="text-truncate text-secondary" style="max-width:200px" title="' . htmlspecialchars($r['referer']) . '">' . (empty($r['referer']) ? 'Direct' : htmlspecialchars($r['referer'])) . '</td>';
        echo '</tr>';
    }
}
?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'downfooter.php'; ?>