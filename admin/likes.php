<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "project1");

$message = ""; // Variable to store success/error message

// Add new customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    // Check if any fields are empty
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $message = "All fields are required!";
    } else {
        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO customers (name, email, phone, password) VALUES ('$name', '$email', '$phone', '$hashed_password')";

        if ($conn->query($sql) === TRUE) {
            $message = "New customer added successfully!";
            header("Location: " . $_SERVER['PHP_SELF']); // Redirect to prevent form resubmission
            exit();
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Edit customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    // Check if any fields are empty
    if (empty($name) || empty($email) || empty($phone)) {
        $message = "Name, email, and phone are required!";
    } else {
        if (!empty($password)) {
            // If password is entered, hash and update it
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE customers SET name='$name', email='$email', phone='$phone', password='$hashed_password' WHERE id='$id'";
        } else {
            // If password is not entered, do not change the password
            $sql = "UPDATE customers SET name='$name', email='$email', phone='$phone' WHERE id='$id'";
        }

        if ($conn->query($sql) === TRUE) {
            $message = "Customer updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']); // Redirect to prevent form resubmission
            exit();
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Delete customer
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $sql = "DELETE FROM customers WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        $message = "Customer deleted successfully!";
        header("Location: " . $_SERVER['PHP_SELF']); // Redirect to prevent form resubmission
        exit();
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch customer data
$sql = "SELECT * FROM customers";
$result = $conn->query($sql);
$customers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .alert-dismissible {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 999;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <h1 class="section-title">Customers</h1>

        <table class="table table-bordered">
            <thead>
                <tr class="bg-primary">
                    <th scope="col">Customer ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Password</th> <!-- Added Password column -->
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr data-id="<?= $customer['id'] ?>">
                    <th scope="row"><?= $customer['id'] ?></th>
                    <td><?= $customer['name'] ?></td>
                    <td><?= $customer['email'] ?></td>
                    <td><?= $customer['phone'] ?></td>
                    <td><?= '******' ?></td> <!-- Masked password for display -->
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCustomerModal" onclick="populateEditForm(<?= $customer['id'] ?>)">Edit</button>
                        <a href="?delete_id=<?= $customer['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Add New Customer</button>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" name="name" id="customerName" placeholder="Enter customer name">
                        </div>
                        <div class="mb-3">
                            <label for="customerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="customerEmail" placeholder="Enter customer email">
                        </div>
                        <div class="mb-3">
                            <label for="customerPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="customerPhone" placeholder="Enter customer phone number">
                        </div>
                        <div class="mb-3">
                            <label for="customerPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="customerPassword" placeholder="Enter password">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Customer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editCustomerId">
                        <div class="mb-3">
                            <label for="editCustomerName" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" name="name" id="editCustomerName" placeholder="Enter customer name">
                        </div>
                        <div class="mb-3">
                            <label for="editCustomerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editCustomerEmail" placeholder="Enter customer email">
                        </div>
                        <div class="mb-3">
                            <label for="editCustomerPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="editCustomerPhone" placeholder="Enter customer phone number">
                        </div>
                        <div class="mb-3">
                            <label for="editCustomerPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="editCustomerPassword" placeholder="Enter new password (leave blank to keep current)">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function populateEditForm(id) {
            const customerRow = document.querySelector(`tr[data-id='${id}']`);
            const customerName = customerRow.querySelector('td:nth-child(2)').textContent;
            const customerEmail = customerRow.querySelector('td:nth-child(3)').textContent;
            const customerPhone = customerRow.querySelector('td:nth-child(4)').textContent;

            document.getElementById('editCustomerId').value = id;
            document.getElementById('editCustomerName').value = customerName;
            document.getElementById('editCustomerEmail').value = customerEmail;
            document.getElementById('editCustomerPhone').value = customerPhone;
        }
    </script>

</body>

</html>
