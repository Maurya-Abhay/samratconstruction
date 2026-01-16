<?php
// messages.php

include "database.php";
include 'topheader.php'; 
include 'sidenavbar.php'; 

// Fetch Messages
$result = $conn->query("SELECT * FROM messages ORDER BY submitted_at DESC");
$total_messages = $result ? $result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Admin Panel</title>
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

        /* Stats Box */
        .summary-box {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
            margin-bottom: 20px;
        }

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }

        /* Search */
        .search-box {
            position: relative;
            max-width: 300px;
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-inbox-fill text-primary me-2"></i>Inbox</h3>
        <div class="search-box">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchInput" class="form-control" placeholder="Search messages...">
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="summary-box">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8;">Total Messages</h6>
                    <h2 class="mb-0 fw-bold"><?= $total_messages ?></h2>
                </div>
                <div class="fs-1" style="opacity: 0.3;"><i class="bi bi-envelope-open"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0" id="messagesTable">
                    <thead class="sticky-top">
                        <tr>
                            <th width="15%" class="ps-4">Date</th>
                            <th width="20%">Sender</th>
                            <th width="20%">Subject</th>
                            <th width="45%" class="pe-4">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_messages > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted small">
                                    <?= date('d M Y, h:i A', strtotime($row['submitted_at'])) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                    <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="small text-decoration-none text-primary">
                                        <?= htmlspecialchars($row['email']) ?>
                                    </a>
                                </td>
                                <td class="fw-semibold text-secondary">
                                    <?= htmlspecialchars($row['subject']) ?>
                                </td>
                                <td class="pe-4 text-muted small">
                                    <?= nl2br(htmlspecialchars($row['message'])) ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No messages found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php 
include 'downfooter.php'; 
$conn->close(); 
?>

<script>
    // Client-side Search
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#messagesTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // Auto-refresh every 1 minute for admin panel
    setInterval(function() {
        location.reload();
    }, 60000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>