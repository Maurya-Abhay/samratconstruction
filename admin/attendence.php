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
                    <h1 class="section-title">Attendance</h1>
                </div>

                <div class="container">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th scope="col">Worker ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">001</th>
                                <td>Ravi Kumar</td>
                                <td>
                                    <button class="btn btn-success btn-sm">Present</button>
                                    <button class="btn btn-danger btn-sm">Absent</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewAttendanceModal">View</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">001</th>
                                <td>Ravi Kumar</td>
                                <td>
                                    <button class="btn btn-success btn-sm">Present</button>
                                    <button class="btn btn-danger btn-sm">Absent</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewAttendanceModal">View</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">001</th>
                                <td>Ravi Kumar</td>
                                <td>
                                    <button class="btn btn-success btn-sm">Present</button>
                                    <button class="btn btn-danger btn-sm">Absent</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewAttendanceModal">View</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">001</th>
                                <td>Ravi Kumar</td>
                                <td>
                                    <button class="btn btn-success btn-sm">Present</button>
                                    <button class="btn btn-danger btn-sm">Absent</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewAttendanceModal">View</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">001</th>
                                <td>Ravi Kumar</td>
                                <td>
                                    <button class="btn btn-success btn-sm">Present</button>
                                    <button class="btn btn-danger btn-sm">Absent</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewAttendanceModal">View</button>
                                </td>
                            </tr>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</body>

</html>