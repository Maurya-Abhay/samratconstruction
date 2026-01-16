<?php
// worker_pdf_selector.php

// Include necessary files (DB connection, headers)
include 'topheader.php'; 
include 'sidenavbar.php';

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Fetch workers for dropdown
$stmt_workers = $conn->prepare("SELECT id, name FROM workers ORDER BY name ASC");
$stmt_workers->execute();
$workers_res = $stmt_workers->get_result();
$default_start = date('Y-m-01');
$default_end = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Worker PDF Report</title>
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

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
        .form-select { height: 3.5rem; padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Worker Report</h3>
        <a href="dash.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-modern shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Generate PDF Report</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">Select a worker and specify the date range to download a comprehensive attendance and payment report.</p>

                    <?php if ($workers_res && $workers_res->num_rows > 0): ?>
                        <?php if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                        if (!function_exists('csrf_token')) {
                            function csrf_token() {
                                if (empty($_SESSION['csrf_token'])) {
                                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                                }
                                return $_SESSION['csrf_token'];
                            }
                        } ?>
                        <form method="get" action="worker_pdf_report.php" target="_blank" onsubmit="return validatePDFForm();">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="form-floating">
                                        <select name="id" class="form-select" id="workerSelect" required>
                                            <option value="" disabled selected>Select Worker</option>
                                            <?php while($w = $workers_res->fetch_assoc()): ?>
                                                <option value="<?= (int)$w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <label for="workerSelect">Worker</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" name="start_date" class="form-control" id="startDate" value="<?= $default_start ?>" required>
                                        <label for="startDate">From Date</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" name="end_date" class="form-control" id="endDate" value="<?= $default_end ?>" required>
                                        <label for="endDate">To Date</label>
                                    </div>
                                </div>
                                <div class="col-12 text-end mt-4">
                                    <button type="submit" class="btn btn-danger btn-lg px-4">
                                        <i class="bi bi-file-pdf-fill me-2"></i> Download PDF
                                    </button>
                                </div>
                            </div>
                        </form>
                        <script>
                        function validatePDFForm() {
                            var worker = document.getElementById('workerSelect').value;
                            var start = document.getElementById('startDate').value;
                            var end = document.getElementById('endDate').value;
                            if (!worker || !start || !end) {
                                alert('Please select worker and date range.');
                                return false;
                            }
                            return true;
                        }
                        </script>
                    <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>No workers found. Please add workers first.</div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="workers.php" class="btn btn-primary">Manage Workers</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>