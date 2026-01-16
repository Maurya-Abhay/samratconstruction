<?php
// admin/db_cleaner.php
require_once 'database.php';
session_start();

// =========================================================
// 1. AJAX API HANDLER (MUST BE AT THE VERY TOP)
// =========================================================
if (isset($_POST['action']) && $_POST['action'] === 'fetch_table_details') {
    header('Content-Type: application/json');
    
    // Auth Check
    if (!isset($_SESSION['email'])) {
        echo json_encode(['error' => 'Unauthorized Access']); exit;
    }

    $target_table = $_POST['table_name'] ?? '';
    
    // Security: Whitelist table verification to prevent SQL Injection
    $tables_check = [];
    $res = $conn->query("SHOW TABLES");
    while ($r = $res->fetch_array()) $tables_check[] = $r[0];

    if (!in_array($target_table, $tables_check)) {
        echo json_encode(['error' => 'Invalid Table Name']); exit;
    }

    // Fetch Structure
    $structure = [];
    $desc = $conn->query("DESCRIBE `$target_table`");
    while($row = $desc->fetch_assoc()) {
        $structure[] = $row;
    }

    // Fetch Data (Limit 50 rows for performance)
    $data = [];
    $rows = $conn->query("SELECT * FROM `$target_table` LIMIT 50");
    while($row = $rows->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['structure' => $structure, 'data' => $data]);
    exit;
}

// =========================================================
// 2. PAGE LOGIC & AUTH
// =========================================================
include 'topheader.php';
include 'sidenavbar.php';

if (!isset($_SESSION['email'])) {
    echo "<script>window.location.href='login.php';</script>"; exit;
}

$admin_email = $_SESSION['email'];
$admin_id = 0;

// Fetch Admin ID for password verification
$stmt = $conn->prepare("SELECT id, password FROM admin WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $admin_id = (int)$row['id'];
}
$stmt->close();

// Fetch All Tables
$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) { $tables[] = $row[0]; }

// Calculate Stats
$table_stats = [];
$total_size = 0;
$total_rows = 0;

foreach ($tables as $table) {
    // Row Count
    $row_count = $conn->query("SELECT COUNT(*) as c FROM `$table`")?->fetch_assoc()['c'] ?? 0;
    // Table Metadata (Size, Updates)
    $size_res = $conn->query("SELECT DATA_LENGTH + INDEX_LENGTH as size, UPDATE_TIME, ENGINE FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='$table'");
    $meta = $size_res ? $size_res->fetch_assoc() : [];
    $size = $meta['size'] ?? 0;
    $updated = $meta['UPDATE_TIME'] ?? 'N/A';
    $engine = $meta['ENGINE'] ?? '-';
    $total_size += $size;
    $total_rows += $row_count;
    // Health/Usage Score Calculation
    $usage_score = ($row_count * 1) + ($size / 1024); 
    $health_status = 'good';
    if($usage_score > 50000) $health_status = 'heavy';
    elseif($usage_score > 10000) $health_status = 'medium';
    $table_stats[] = [
        'name' => $table,
        'count' => $row_count,
        'size' => $size,
        'updated' => $updated,
        'engine' => $engine,
        'status' => $health_status
    ];
}

// DB Overview Vars
$db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0] ?? 'Unknown';
$mysql_version = $conn->query("SELECT VERSION()")->fetch_row()[0] ?? '';

// Handle Form Submissions (Delete/Truncate)
$response_msg = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $password = $_POST['admin_password'] ?? '';
    
    // Check Password
    $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $db_pass = $stmt->get_result()->fetch_assoc()['password'] ?? '';
    
    if (!password_verify($password, $db_pass)) {
        $response_msg = ['type' => 'error', 'text' => 'Incorrect Admin Password!'];
    } else {
        if (isset($_POST['clean_db'])) {
            foreach ($tables as $t) $conn->query("TRUNCATE TABLE `$t`");
            $response_msg = ['type' => 'success', 'text' => 'Database completely purged.'];
        } elseif (isset($_POST['delete_table'])) {
            $t = $_POST['table_name'];
            if (in_array($t, $tables)) {
                $conn->query("DROP TABLE IF EXISTS `$t`");
                echo "<script>window.location.href='db_cleaner.php';</script>"; exit;
            }
        } elseif (isset($_POST['truncate_table'])) {
            $t = $_POST['table_name'];
            if (in_array($t, $tables)) {
                $conn->query("TRUNCATE TABLE `$t`");
                $response_msg = ['type' => 'success', 'text' => "Table $t emptied."];
                // Refresh data
                $table_stats = []; // Force reload on next refresh usually, but here we just show success
            }
        }
    }
}

// Get current MySQL processlist (active connections)
$active_users = [];
$res = $conn->query("SHOW PROCESSLIST");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['User'])) {
            $active_users[$row['User']] = ($active_users[$row['User']] ?? 0) + 1;
        }
    }
}
$total_active = array_sum($active_users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Console Pro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f3f4f6;
            --card-bg: #ffffff;
            --primary: #4f46e5;
            --primary-soft: #eef2ff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            padding-bottom: 50px;
        }

        /* Modern Card */
        .modern-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        /* Header Gradient Card */
        .hero-card {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: white;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        .hero-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 60%);
        }

        /* Stats Grid */
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-green { background: #ecfdf5; color: #10b981; }
        .icon-purple { background: #f5f3ff; color: #8b5cf6; }
        .icon-red { background: #fef2f2; color: #ef4444; }

        /* Modern Table Styling */
        .tech-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .tech-table thead th {
            background: #f9fafb;
            padding: 12px 24px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }
        .tech-table tbody tr {
            transition: background 0.2s;
        }
        .tech-table tbody tr:hover {
            background: #f9fafb;
        }
        .tech-table tbody td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
            vertical-align: middle;
        }
        .tech-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status-pill {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-good { background: #ecfdf5; color: #059669; }
        .status-medium { background: #fffbeb; color: #d97706; }
        .status-heavy { background: #fef2f2; color: #dc2626; }

        /* Action Buttons */
        .action-btn {
            width: 32px; height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: white;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        .action-btn:hover { background: var(--bg-body); color: var(--text-main); border-color: #d1d5db; }
        .action-btn.danger:hover { background: #fef2f2; color: #ef4444; border-color: #fca5a5; }
        
        /* Modal & Code */
        .modal-content { border: none; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .modal-header { background: #f9fafb; border-bottom: 1px solid var(--border-color); border-radius: 16px 16px 0 0; }
        .code-view {
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="container py-4">
    
    <div class="modern-card hero-card mb-4 shadow-lg">
        <div class="d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
            <div>
                <h1 class="fw-bold mb-1"><i class="bi bi-database-fill-gear me-3"></i>Database Manager</h1>
                <p class="mb-0 opacity-75">Admin Console &bull; <?= htmlspecialchars($db_name) ?> &bull; PHP <?= phpversion() ?></p>
            </div>
            <div>
                <button class="btn btn-light text-danger fw-bold shadow-sm" onclick="toggleMainCleaner()">
                    <i class="bi bi-radioactive me-2"></i>Nuke Database
                </button>
            </div>
        </div>
    </div>

    <div id="mainCleanerCard" class="modern-card p-4 mb-4 border-danger" style="display:none; border-left: 5px solid #dc3545;">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <h5 class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Emergency Zone</h5>
                <p class="text-muted mb-0">This action will <strong>TRUNCATE ALL TABLES</strong>. All data will be lost forever. Structure remains.</p>
            </div>
            <form method="POST" class="d-flex gap-2">
                <input type="password" name="admin_password" class="form-control" placeholder="Admin Password" required style="width: 200px;">
                <button type="submit" name="clean_db" class="btn btn-danger text-nowrap">Confirm Wipe</button>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div class="stat-icon icon-blue"><i class="bi bi-table"></i></div>
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Total Tables</div>
                    <div class="h3 mb-0 fw-bold"><?= count($tables) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div class="stat-icon icon-green"><i class="bi bi-list-columns-reverse"></i></div>
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Total Rows</div>
                    <div class="h3 mb-0 fw-bold"><?= number_format($total_rows) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div class="stat-icon icon-purple"><i class="bi bi-hdd-network"></i></div>
                <div>
                    <div class="small text-muted fw-bold text-uppercase">Disk Usage</div>
                    <div class="h3 mb-0 fw-bold"><?= number_format($total_size/1024/1024, 2) ?><span class="fs-6 text-muted"> MB</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div class="stat-icon icon-red"><i class="bi bi-activity"></i></div>
                <div>
                    <div class="small text-muted fw-bold text-uppercase">SQL Version</div>
                    <div class="h3 mb-0 fw-bold fs-5 text-truncate"><?= htmlspecialchars($mysql_version) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modern-card shadow-sm">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white rounded-top">
            <h5 class="fw-bold mb-0 text-dark">System Tables</h5>
            <div class="input-group" style="width: 300px;">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearch" class="form-control border-start-0 ps-0" placeholder="Filter tables...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="tech-table">
                <thead>
                    <tr>
                        <th>Table Details</th>
                        <th>Engine</th>
                        <th>Records</th>
                        <th>Size / Health</th>
                        <th>Last Updated</th>
                        <th class="text-end">Controls</th>
                    </tr>
                </thead>
                <tbody id="tableListBody">
                    <?php foreach ($table_stats as $stat): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-3 text-primary"><i class="bi bi-database"></i></div>
                                <div>
                                    <div class="fw-bold text-dark"><?= $stat['name'] ?></div>
                                    <div class="small text-muted">ID: <?= md5($stat['name']) // Fake ID for look ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= $stat['engine'] ?></span></td>
                        <td>
                            <span class="fw-bold text-dark"><?= number_format($stat['count']) ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="small fw-bold"><?= number_format($stat['size']/1024, 1) ?> KB</span>
                                <?php if($stat['status'] == 'good'): ?>
                                    <span class="status-pill status-good">Healthy</span>
                                <?php elseif($stat['status'] == 'medium'): ?>
                                    <span class="status-pill status-medium">Medium</span>
                                <?php else: ?>
                                    <span class="status-pill status-heavy">Heavy</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-muted small">
                            <?= ($stat['updated'] && $stat['updated'] != 'N/A') ? date('M d, H:i', strtotime($stat['updated'])) : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td class="text-end">
                            <button class="action-btn me-1" onclick="inspectTable('<?= $stat['name'] ?>')" title="Inspect Data">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="action-btn me-1 text-warning" onclick="confirmAction('truncate', '<?= $stat['name'] ?>')" title="Empty Table">
                                <i class="bi bi-eraser"></i>
                            </button>
                            <button class="action-btn danger" onclick="confirmAction('delete', '<?= $stat['name'] ?>')" title="Drop Table">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="inspectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="inspectTitle">Table Inspector</h5>
                    <p class="mb-0 small text-muted">Viewing live data and schema configuration.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom-0">
                        <ul class="nav nav-pills card-header-pills" id="myTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="data-tab" data-bs-toggle="tab" data-bs-target="#tab-data" type="button"><i class="bi bi-table me-2"></i>Live Data (50)</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="struct-tab" data-bs-toggle="tab" data-bs-target="#tab-struct" type="button"><i class="bi bi-code-slash me-2"></i>Structure</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tab-data">
                                <div class="table-responsive" id="previewDataContainer" style="min-height: 200px;">
                                    <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-struct">
                                <div class="table-responsive p-3" id="structureContainer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" id="actionForm">
    <input type="hidden" name="table_name" id="actionTableName">
    <input type="hidden" name="truncate_table" id="actionTruncateInput" disabled>
    <input type="hidden" name="delete_table" id="actionDeleteInput" disabled>
    <input type="hidden" name="admin_password" id="actionPassword">
</form>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 1. Client Side Search
document.getElementById('tableSearch').addEventListener('keyup', function() {
    let searchText = this.value.toLowerCase();
    let rows = document.querySelectorAll('#tableListBody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
    });
});

// 2. Toggle Nuke Section
function toggleMainCleaner() {
    let el = document.getElementById('mainCleanerCard');
    if (getComputedStyle(el).display === 'none') {
        el.style.display = 'block';
        el.scrollIntoView({ behavior: 'smooth' });
    } else {
        el.style.display = 'none';
    }
}

// 3. AJAX Inspector
function inspectTable(tableName) {
    const modal = new bootstrap.Modal(document.getElementById('inspectModal'));
    document.getElementById('inspectTitle').innerHTML = `Inspecting <span class="text-primary font-monospace">${tableName}</span>`;
    document.getElementById('previewDataContainer').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Fetching rows...</p></div>';
    
    modal.show();

    const formData = new FormData();
    formData.append('action', 'fetch_table_details');
    formData.append('table_name', tableName);

    fetch('db_cleaner.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.error) {
            document.getElementById('previewDataContainer').innerHTML = `<div class="p-4 text-center text-danger"><i class="bi bi-exclamation-circle h1"></i><p>${data.error}</p></div>`;
            return;
        }
        renderStructure(data.structure);
        renderData(data.data);
    })
    .catch(err => {
        console.error(err);
        document.getElementById('previewDataContainer').innerHTML = '<div class="p-4 text-center text-danger">Connection Failed</div>';
    });
}

function renderStructure(struct) {
    let html = '<table class="table table-striped table-hover mb-0"><thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead><tbody>';
    struct.forEach(c => {
        html += `<tr>
            <td class="fw-bold font-monospace text-primary">${c.Field}</td>
            <td class="small">${c.Type}</td>
            <td>${c.Null}</td>
            <td><span class="badge bg-secondary">${c.Key}</span></td>
            <td class="font-monospace small">${c.Default || 'NULL'}</td>
        </tr>`;
    });
    html += '</tbody></table>';
    document.getElementById('structureContainer').innerHTML = html;
}

function renderData(rows) {
    if (rows.length === 0) {
        document.getElementById('previewDataContainer').innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox h1 opacity-25"></i><p>Table is empty</p></div>';
        return;
    }
    
    let headers = Object.keys(rows[0]);
    let html = '<table class="table table-sm table-hover mb-0 code-view"><thead><tr class="table-light">';
    headers.forEach(h => html += `<th class="text-nowrap">${h}</th>`);
    html += '</tr></thead><tbody>';
    
    rows.forEach(row => {
        html += '<tr>';
        headers.forEach(h => {
            let val = row[h];
            if(val && val.length > 60) val = val.substring(0, 60) + '<span class="text-muted">...</span>';
            html += `<td class="text-nowrap">${val === null ? '<span class="text-muted fst-italic">NULL</span>' : val}</td>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('previewDataContainer').innerHTML = html;
}

// 4. Secure Actions
function confirmAction(type, tableName) {
    const isDelete = type === 'delete';
    
    Swal.fire({
        title: isDelete ? 'Drop Table?' : 'Truncate Data?',
        html: `You are about to <b>${isDelete ? 'DELETE' : 'EMPTY'}</b> the table: <br/><span class="text-primary fw-bold fs-4">${tableName}</span>`,
        icon: 'warning',
        input: 'password',
        inputPlaceholder: 'Confirm with Admin Password',
        inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: isDelete ? 'Yes, Drop it' : 'Yes, Empty it',
        focusConfirm: false,
        preConfirm: (password) => {
            if (!password) Swal.showValidationMessage('Password is required');
            return password;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('actionTableName').value = tableName;
            document.getElementById('actionPassword').value = result.value;
            document.getElementById('actionDeleteInput').disabled = !isDelete;
            document.getElementById('actionTruncateInput').disabled = isDelete;
            document.getElementById('actionForm').submit();
        }
    });
}

// PHP Notifications
<?php if ($response_msg['type'] == 'success'): ?>
Swal.fire({ icon: 'success', title: 'Success', text: '<?= addslashes($response_msg['text']) ?>', timer: 2000, showConfirmButton: false });
<?php elseif ($response_msg['type'] == 'error'): ?>
Swal.fire({ icon: 'error', title: 'Error', text: '<?= addslashes($response_msg['text']) ?>' });
<?php endif; ?>
</script>
</body>
</html>