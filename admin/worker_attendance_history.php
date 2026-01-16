<?php
// attendence_history.php

require_once 'database.php';
include 'topheader.php';
include 'sidenavbar.php';

// Fetch Attendance
$sql = "SELECT a.id, w.name, w.email, a.date, a.status, a.notes 
        FROM worker_attendance a 
        LEFT JOIN workers w ON a.worker_id = w.id 
        ORDER BY a.date DESC, a.id DESC";
$result = $conn->query($sql);
$records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Modern Card */
        .card-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: #fff;
            margin-bottom: 20px;
        }

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow-x: auto !important;
            overflow-y: auto !important;
            max-height: 700px;
        }
        .table {
            min-width: 800px;
        }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }

        /* Status Badges */
        .badge-status { font-size: 0.75rem; padding: 5px 10px; border-radius: 20px; font-weight: 600; }
        .status-Present { background-color: #d1e7dd; color: #0f5132; }
        .status-Absent { background-color: #f8d7da; color: #842029; }
        .status-Leave { background-color: #fff3cd; color: #856404; }
        .status-HalfDay { background-color: #cff4fc; color: #055160; }

        /* Search Input */
        .search-box {
            position: relative;
            max-width: 400px;
        }
        .search-box .form-control {
            padding-left: 40px;
            border-radius: 20px;
            border: 1px solid #e3e6f0;
        }
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-clock-history text-primary me-2"></i>Attendance History</h3>
        
        <div class="search-box flex-grow-1 flex-md-grow-0">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchInput" class="form-control" placeholder="Search records...">
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 700px;">
                <table class="table table-hover align-middle mb-0" id="attendanceTable">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">ID</th>
                            <th width="20%">Worker</th>
                            <th width="20%">Date</th>
                            <th width="15%">Status</th>
                            <th width="40%" class="pe-4">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($records) > 0): ?>
                            <?php foreach ($records as $row): 
                                $statusClass = 'status-' . str_replace(' ', '', $row['status']);
                                $dateObj = new DateTime($row['date']);
                            ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $row['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($row['email']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= $dateObj->format('d M, Y') ?></div>
                                    <div class="small text-muted"><?= $dateObj->format('l') ?></div>
                                </td>
                                <td>
                                    <span class="badge-status <?= $statusClass ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-muted small">
                                    <?= htmlspecialchars($row['notes'] ?? '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No attendance history found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script>
    // Client-side Search
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#attendanceTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>