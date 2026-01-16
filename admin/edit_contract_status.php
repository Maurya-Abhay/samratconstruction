<?php
// edit_contract_status.php

require_once 'lib_common.php'; // Ensure DB connection
include 'topheader.php';
include 'sidenavbar.php';

// 1. Validate ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Invalid Contract ID. <a href='contract_status.php' class='alert-link'>Go Back</a></div></div>";
    include 'downfooter.php';
    exit();
}

// 2. Handle Form Submission (Update)
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $progress = (int)$_POST['progress_percent'];
    $current_stage = trim($_POST['current_stage']);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $est_completion = !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null;
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE contacts SET progress_percent=?, current_stage=?, start_date=?, estimated_completion=?, status=? WHERE id=?");
    $stmt->bind_param("issssi", $progress, $current_stage, $start_date, $est_completion, $status, $id);

    if ($stmt->execute()) {
        $msg = "Contract details updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error updating contract: " . $stmt->error;
        $msg_type = "danger";
    }
    $stmt->close();
}

// 3. Fetch Current Data
$stmt = $conn->prepare("SELECT * FROM contacts WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$contract = $result->fetch_assoc();
$stmt->close();

if (!$contract) {
    echo "<div class='container py-5'><div class='alert alert-warning'>Contract not found.</div></div>";
    include 'downfooter.php';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Contract | Admin</title>
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

        .card-modern {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: #fff;
        }

        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }

        /* Custom Range Slider */
        .form-range::-webkit-slider-thumb {
            background: var(--primary-color);
        }
        
        .progress-container {
            background: #eaecf4;
            border-radius: 8px;
            height: 12px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-bar-custom {
            height: 100%;
            background: linear-gradient(90deg, #4e73df, #224abe);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>

<div class="container px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-dark fw-bold m-0"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Contract</h3>
            <p class="text-muted small mb-0">Project: <strong><?= htmlspecialchars($contract['name']) ?></strong></p>
        </div>
        <a href="contract_status.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-<?= $msg_type == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-modern p-4 p-md-5">
                
                <form method="POST">
                    
                    <div class="mb-4 p-3 bg-light rounded border">
                        <label class="form-label fw-bold d-flex justify-content-between">
                            <span>Project Progress</span>
                            <span class="text-primary" id="progressVal"><?= (int)$contract['progress_percent'] ?>%</span>
                        </label>
                        <input type="range" class="form-range" name="progress_percent" min="0" max="100" value="<?= (int)$contract['progress_percent'] ?>" id="progressInput">
                        
                        <div class="progress-container">
                            <div class="progress-bar-custom" id="progressBar" style="width: <?= (int)$contract['progress_percent'] ?>%;"></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" name="status" id="statusSelect">
                                    <option value="Pending" <?= $contract['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Active" <?= $contract['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="In Progress" <?= $contract['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="On Hold" <?= $contract['status'] == 'On Hold' ? 'selected' : '' ?>>On Hold</option>
                                    <option value="Completed" <?= $contract['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <label for="statusSelect">Current Status</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="current_stage" placeholder="Stage" value="<?= htmlspecialchars($contract['current_stage'] ?? '') ?>">
                                <label>Current Stage (e.g. Foundation)</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($contract['start_date'] ?? '') ?>">
                                <label>Start Date</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="estimated_completion" value="<?= htmlspecialchars($contract['estimated_completion'] ?? '') ?>">
                                <label>Est. Completion Date</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="contract_status.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Update Contract
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script>
    // Live Update Progress Bar
    const slider = document.getElementById('progressInput');
    const bar = document.getElementById('progressBar');
    const valDisplay = document.getElementById('progressVal');

    slider.addEventListener('input', function() {
        const val = this.value;
        bar.style.width = val + '%';
        valDisplay.textContent = val + '%';
        
        // Change color based on completion
        if(val == 100) {
            bar.style.background = '#1cc88a'; // Green
        } else {
            bar.style.background = 'linear-gradient(90deg, #4e73df, #224abe)'; // Blue
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>