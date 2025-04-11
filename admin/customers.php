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
                    <h1 class="section-title">Customers</h1>
                </div>

                <div class="container">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th scope="col">Customer ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">001</th>
                                <td>John Doe</td>
                                <td>johndoe@example.com</td>
                                <td>+91 98765 43210</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editCustomerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">002</th>
                                <td>Jane Smith</td>
                                <td>janesmith@example.com</td>
                                <td>+91 91234 56789</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editCustomerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">003</th>
                                <td>Amit Sharma</td>
                                <td>amit.sharma@example.com</td>
                                <td>+91 98765 11223</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editCustomerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">004</th>
                                <td>Neha Gupta</td>
                                <td>neha.gupta@example.com</td>
                                <td>+91 99887 66554</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editCustomerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">005</th>
                                <td>Rahul Kumar</td>
                                <td>rahul.kumar@example.com</td>
                                <td>+91 91234 67890</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editCustomerModal">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal"
                        data-bs-target="#addCustomerModal">Add New Customer</button>
                </div>
            </div>

            <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="customerName" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="customerName"
                                        placeholder="Enter customer name">
                                </div>
                                <div class="mb-3">
                                    <label for="customerEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="customerEmail"
                                        placeholder="Enter customer email">
                                </div>
                                <div class="mb-3">
                                    <label for="customerPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="customerPhone"
                                        placeholder="Enter customer phone number">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Customer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="editCustomerName" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="editCustomerName" value="John Doe"
                                        placeholder="Enter customer name">
                                </div>
                                <div class="mb-3">
                                    <label for="editCustomerEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="editCustomerEmail"
                                        value="johndoe@example.com" placeholder="Enter customer email">
                                </div>
                                <div class="mb-3">
                                    <label for="editCustomerPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="editCustomerPhone"
                                        value="+91 98765 43210" placeholder="Enter customer phone number">
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