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
    <title>Contracts</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
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
            <h1 class="section-title">Contracts</h1>
            <table class="table table-bordered">
                <thead>
                    <tr class="bg-primary">
                        <th scope="col">ID</th>
                        <th scope="col">Contract Title</th>
                        <th scope="col">Start Date</th>
                        <th scope="col">End Date</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">001</th>
                        <td>Construction Contract ABC</td>
                        <td>2025-03-01</td>
                        <td>2025-12-31</td>
                        <td>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#viewContractModal">View</button>
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <button class="btn btn-success btn-add" data-bs-toggle="modal" data-bs-target="#addContractModal">Add New Contract</button>
        </div>
    </div>

    <div class="modal fade" id="addContractModal" tabindex="-1" aria-labelledby="addContractModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addContractModalLabel">Add New Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="contractTitle" class="form-label">Contract Title</label>
                            <input type="text" class="form-control" id="contractTitle" placeholder="Enter contract title">
                        </div>
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="mb-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Contract</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewContractModal" tabindex="-1" aria-labelledby="viewContractModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewContractModalLabel">View Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Contract ID:</strong> 001</p>
                    <p><strong>Contract Title:</strong> Construction Contract ABC</p>
                    <p><strong>Start Date:</strong> 2025-03-01</p>
                    <p><strong>End Date:</strong> 2025-12-31</p>
                    <p><strong>Status:</strong> Active</p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
