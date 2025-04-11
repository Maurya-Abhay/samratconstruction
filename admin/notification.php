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
                    <h1 class="section-title">Notifications</h1>
                    <table class="table table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th scope="col">ID</th>
                                <th scope="col">Title</th>
                                <th scope="col">Date</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">001</th>
                                <td>New Project Assigned</td>
                                <td>2025-03-29</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewNotificationModal">View</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">002</th>
                                <td>Meeting Reminder</td>
                                <td>2025-03-28</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewNotificationModal">View</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">003</th>
                                <td>Work Schedule Updated</td>
                                <td>2025-03-27</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#viewNotificationModal">View</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal"
                        data-bs-target="#addNotificationModal">Add New Notification</button>
                </div>
            </div>

            <div class="modal fade" id="addNotificationModal" tabindex="-1" aria-labelledby="addNotificationModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addNotificationModalLabel">Add New Notification</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="notificationTitle" class="form-label">Notification Title</label>
                                    <input type="text" class="form-control" id="notificationTitle"
                                        placeholder="Enter notification title">
                                </div>
                                <div class="mb-3">
                                    <label for="notificationDate" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="notificationDate">
                                </div>
                                <div class="mb-3">
                                    <label for="notificationDetails" class="form-label">Details</label>
                                    <textarea class="form-control" id="notificationDetails" rows="4"
                                        placeholder="Enter notification details"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Notification</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="viewNotificationModal" tabindex="-1"
                aria-labelledby="viewNotificationModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewNotificationModalLabel">View Notification</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Notification ID:</strong> 001</p>
                            <p><strong>Title:</strong> New Project Assigned</p>
                            <p><strong>Date:</strong> 2025-03-29</p>
                            <p><strong>Status:</strong> Active</p>
                            <p><strong>Details:</strong> A new project has been assigned to you. Please review the tasks
                                and report back with your progress.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>