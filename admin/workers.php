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
                    <h1 class="section-title">Workers</h1>
                </div>

                <div class="container">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th scope="col">Worker ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Position</th>
                                <th scope="col">Hourly Rate</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">001</th>
                                <td>Ravi Kumar</td>
                                <td>Carpenter</td>
                                <td>₹1,500/day</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editWorkerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">002</th>
                                <td>Priya Patel</td>
                                <td>Electrician</td>
                                <td>₹1,800/day</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editWorkerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">003</th>
                                <td>Amit Sharma</td>
                                <td>Plumber</td>
                                <td>₹1,600/day</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editWorkerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">004</th>
                                <td>Sunita Rani</td>
                                <td>Foreman</td>
                                <td>₹2,000/day</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editWorkerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">005</th>
                                <td>Vikas Yadav</td>
                                <td>Mason</td>
                                <td>₹1,400/day</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editWorkerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal" data-bs-target="#addWorkerModal">Add
                        New Worker</button>
                </div>
            </div>

            <div class="modal fade" id="addWorkerModal" tabindex="-1" aria-labelledby="addWorkerModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addWorkerModalLabel">Add New Worker</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="workerName" class="form-label">Worker Name</label>
                                    <input type="text" class="form-control" id="workerName"
                                        placeholder="Enter worker name">
                                </div>
                                <div class="mb-3">
                                    <label for="workerPosition" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="workerPosition"
                                        placeholder="Enter worker position">
                                </div>
                                <div class="mb-3">
                                    <label for="workerHourlyRate" class="form-label">Hourly Rate</label>
                                    <input type="text" class="form-control" id="workerHourlyRate"
                                        placeholder="Enter worker hourly rate">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Worker</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editWorkerModal" tabindex="-1" aria-labelledby="editWorkerModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editWorkerModalLabel">Edit Worker</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="editWorkerName" class="form-label">Worker Name</label>
                                    <input type="text" class="form-control" id="editWorkerName" value="Ravi Kumar"
                                        placeholder="Enter worker name">
                                </div>
                                <div class="mb-3">
                                    <label for="editWorkerPosition" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="editWorkerPosition" value="Carpenter"
                                        placeholder="Enter worker position">
                                </div>
                                <div class="mb-3">
                                    <label for="editWorkerHourlyRate" class="form-label">Hourly Rate</label>
                                    <input type="text" class="form-control" id="editWorkerHourlyRate" value="₹1,500/day"
                                        placeholder="Enter worker hourly rate">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>