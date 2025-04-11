<?php
session_start();
if(!isset($_SESSION['email'])){
    header("location: index.php");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css//bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .nav-link {
            color: white !important;
        }

        .nav-link:hover {
            background-color: #575757 !important;
            color: white !important;
        }

        .nav-link.active {
            background-color: #007bff !important;
            color: white !important;
        }
    </style>
</head>

<body>

<?php
        include "header.php";
        include "navbar.php";
    ?>

            <div class="col-md-9 col-lg-10 mt-5">
                <div class="container mt-4">
                    <h1 class="section-title">Reports</h1>
                    <table class="table table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th scope="col">Report ID</th>
                                <th scope="col">Title</th>
                                <th scope="col">Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">001</th>
                                <td>Project Progress Report</td>
                                <td>2025-03-25</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewReportModal">View</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">001</th>
                                <td>Project Progress Report</td>
                                <td>2025-03-25</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewReportModal">View</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr><tr>
                                <th scope="row">001</th>
                                <td>Project Progress Report</td>
                                <td>2025-03-25</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewReportModal">View</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr><tr>
                                <th scope="row">001</th>
                                <td>Project Progress Report</td>
                                <td>2025-03-25</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewReportModal">View</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal" data-bs-target="#addReportModal">Add
                        New Report</button>
                </div>
            </div>

            <!-- Modal for Adding New Report -->
            <div class="modal fade" id="addReportModal" tabindex="-1" aria-labelledby="addReportModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addReportModalLabel">Add New Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="reportTitle" class="form-label">Report Title</label>
                                    <input type="text" class="form-control" id="reportTitle"
                                        placeholder="Enter report title">
                                </div>
                                <div class="mb-3">
                                    <label for="reportCategory" class="form-label">Category</label>
                                    <input type="text" class="form-control" id="reportCategory"
                                        placeholder="Enter report category">
                                </div>
                                <div class="mb-3">
                                    <label for="reportDate" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="reportDate">
                                </div>
                                <div class="mb-3">
                                    <label for="reportStatus" class="form-label">Status</label>
                                    <select class="form-control" id="reportStatus">
                                        <option value="approved">Approved</option>
                                        <option value="pending">Pending</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="reportDetails" class="form-label">Details</label>
                                    <textarea class="form-control" id="reportDetails" rows="4"
                                        placeholder="Enter report details"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Report</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal for Viewing Report (Example) -->
            <div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewReportModalLabel">View Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Report ID:</strong> 001</p>
                            <p><strong>Title:</strong> Project Progress Report</p>
                            <p><strong>Category:</strong> Progress</p>
                            <p><strong>Date:</strong> 2025-03-25</p>
                            <p><strong>Status:</strong> Approved</p>
                            <p><strong>Details:</strong> This is a report detailing the progress made on the
                                construction project in the month of March.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>