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
                    <h1 class="section-title">Services</h1>
                </div>
            
                <div class="container">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th scope="col">Service ID</th>
                                <th scope="col">Service Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Rate</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">001</th>
                                <td>Electrical Wiring</td>
                                <td>Electrical</td>
                                <td>₹1,800</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editServiceModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">002</th>
                                <td>Plumbing</td>
                                <td>Plumbing</td>
                                <td>₹1,500</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editServiceModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">003</th>
                                <td>Carpentry</td>
                                <td>Woodworking</td>
                                <td>₹1,200</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editServiceModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">004</th>
                                <td>Painting</td>
                                <td>Decoration</td>
                                <td>₹1,000</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editServiceModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">005</th>
                                <td>Roofing</td>
                                <td>Roofing</td>
                                <td>₹2,000</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editServiceModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add New Service</button>
                </div>
            </div>
            
            <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="serviceName" class="form-label">Service Name</label>
                                    <input type="text" class="form-control" id="serviceName" placeholder="Enter service name">
                                </div>
                                <div class="mb-3">
                                    <label for="serviceCategory" class="form-label">Category</label>
                                    <input type="text" class="form-control" id="serviceCategory" placeholder="Enter service category">
                                </div>
                                <div class="mb-3">
                                    <label for="serviceRate" class="form-label">Rate</label>
                                    <input type="text" class="form-control" id="serviceRate" placeholder="Enter service rate">
                                </div>
                                <div class="mb-3">
                                    <label for="serviceStatus" class="form-label">Status</label>
                                    <select class="form-control" id="serviceStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="on_site">On Site</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Service</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="editServiceName" class="form-label">Service Name</label>
                                    <input type="text" class="form-control" id="editServiceName" value="Electrical Wiring" placeholder="Enter service name">
                                </div>
                                <div class="mb-3">
                                    <label for="editServiceCategory" class="form-label">Category</label>
                                    <input type="text" class="form-control" id="editServiceCategory" value="Electrical" placeholder="Enter service category">
                                </div>
                                <div class="mb-3">
                                    <label for="editServiceRate" class="form-label">Rate</label>
                                    <input type="text" class="form-control" id="editServiceRate" value="₹1,800" placeholder="Enter service rate">
                                </div>
                                <div class="mb-3">
                                    <label for="editServiceStatus" class="form-label">Status</label>
                                    <select class="form-control" id="editServiceStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="on_site">On Site</option>
                                    </select>
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