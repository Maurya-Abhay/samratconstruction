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
    <title>Admin Construction Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: #f4f4f4;
            font-family: 'Arial', sans-serif;
        }

        .service-item {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            padding: 20px;
            cursor: pointer;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }

        .service-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .service-icon {
            position: relative;
            z-index: 1;
        }

        .service-icon i {
            font-size: 3rem;
        }

        .service-item .service-icon span {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.3);
            padding: 5px 10px;
            border-radius: 50%;
        }

        .service-item h5 {
            font-size: 1.2rem;
            font-weight: bold;
            color: #fff;
        }

        .service-item .service-icon:hover {
            color: #ffd700;
        }

        .bg-primary {
            background-color: #005f73 !important;
        }

        .bg-warning {
            background-color: #ffb703 !important;
        }

        .bg-danger {
            background-color: #d00000 !important;
        }

        .bg-success {
            background-color: #2a9d8f !important;
        }

        .bg-info {
            background-color: #264653 !important;
        }

        .nav-link {
            color: white !important;
        }

        .nav-link:hover {
            background-color: #575757 !important;
            color: white !important;
        }


        .table-striped tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }

    </style>
</head>

<body>
    <?php
        include "header.php";
        include "navbar.php";
    ?>

    <div class="col py-3">
        <div class="row g-2 p-lg-4 p-sm-4 mt-4">
            <h1>Dashboard</h1>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="service-item bg-warning rounded d-flex flex-column align-items-center text-center border">
                    <a href="services.php" class="text-decoration-none">
                        <div class="service-icon">
                            <i class="fa fa-hammer text-white-50"><span class="text-white p-1">80</span></i>
                            <h5 class="mt-3 text-white">Services</h5>
                        </div>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="service-item bg-danger rounded d-flex flex-column align-items-center text-center border">
                    <a href="customers.php" class="text-decoration-none">
                        <div class="service-icon">
                            <i class="fa fa-users text-white-50"><span class="text-white p-1">80</span></i>
                            <h5 class="mt-3 text-white">Customers</h5>
                        </div>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="service-item bg-primary rounded d-flex flex-column align-items-center text-center border">
                    <a href="workers.php" class="text-decoration-none">
                        <div class="service-icon">
                            <i class="fa fa-hard-hat text-white-50"><span class="text-white p-1">80</span></i>
                            <h5 class="mt-3 text-white">Workers</h5>
                        </div>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="service-item bg-success rounded d-flex flex-column align-items-center text-center border">
                    <a href="attendance.php" class="text-decoration-none">
                        <div class="service-icon">
                            <i class="fa fa-clock text-white-50"><span class="text-white p-1">80</span></i>
                            <h5 class="mt-3 text-white">Attendance</h5>
                        </div>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="service-item bg-info rounded d-flex flex-column align-items-center text-center border">
                    <a href="tools.php" class="text-decoration-none">
                        <div class="service-icon">
                            <i class="fa fa-wrench text-white-50"><span class="text-white p-1">80</span></i>
                            <h5 class="mt-3 text-white">Tools</h5>
                        </div>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                <div class="service-item bg-dark rounded d-flex flex-column align-items-center text-center border">
                    <a href="likes.php" class="text-decoration-none">
                        <div class="service-icon">
                            <i class="fa fa-cogs text-white-50"><span class="text-white p-1">80</span></i>
                            <h5 class="mt-3 text-white">Settings</h5>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="container">
            <table class="table table-striped table-hover">
                <thead>
                    <tr class="bg-primary">
                        <th scope="col">Worker ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Position</th>
                        <th scope="col">Hourly Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">001</th>
                        <td>Ravi Kumar</td>
                        <td>Carpenter</td>
                        <td>₹1,500/day</td>
                    </tr>
                    <tr>
                        <th scope="row">002</th>
                        <td>Priya Patel</td>
                        <td>Electrician</td>
                        <td>₹1,800/day</td>
                    </tr>
                    <tr>
                        <th scope="row">003</th>
                        <td>Amit Sharma</td>
                        <td>Plumber</td>
                        <td>₹1,600/day</td>
                    </tr>
                    <tr>
                        <th scope="row">004</th>
                        <td>Sunita Rani</td>
                        <td>Foreman</td>
                        <td>₹2,000/day</td>
                    </tr>
                    <tr>
                        <th scope="row">005</th>
                        <td>Vikas Yadav</td>
                        <td>Mason</td>
                        <td>₹1,400/day</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
