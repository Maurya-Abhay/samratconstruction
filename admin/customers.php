<?php
// contact_manager.php

require_once "lib_common.php";

$message = "";

// -----------------------------------------------------------------------------
// 1. Handle POST Actions (Add, Edit, Add Payment)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Common Sanitization
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $aadhaar = trim($_POST['aadhaar'] ?? '');
    $joining_date = trim($_POST['joining_date'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $password = trim($_POST['password'] ?? '');
    $contract_amount = floatval($_POST['contract_amount'] ?? 0);
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $photo = $_FILES['photo'] ?? null;

    // --- Action: ADD Contact ---
    if ($action === 'add') {
        if ($name && $email && $phone && $password && $photo && $photo['name']) {
            $uploadOk = 1;
            if (getimagesize($photo["tmp_name"]) === false) { $message = "File is not an image."; $uploadOk = 0; }
            if ($photo["size"] > 2000000) { $message = "File too large (Max 2MB)."; $uploadOk = 0; }
            $file_extension = strtolower(pathinfo($photo["name"], PATHINFO_EXTENSION));
            if (!in_array($file_extension, ["jpg","jpeg","png","gif","webp"])) { $message = "Only JPG, PNG, GIF allowed."; $uploadOk = 0; }

            if ($uploadOk == 1) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
                $file_name = 'customer_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $target_path = $upload_dir . $file_name;
                if (move_uploaded_file($photo['tmp_name'], $target_path)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO contacts (name, email, phone, address, aadhaar, joining_date, status, password, photo, notes, contract_amount, amount_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $j_date = !empty($joining_date) ? $joining_date : null;
                    $photo_path = 'uploads/' . $file_name;
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssssssdd", $name, $email, $phone, $address, $aadhaar, $j_date, $status, $hashed_password, $photo_path, $notes, $contract_amount, $amount_paid);
                    if ($stmt->execute()) {
                        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                        exit();
                    } else {
                        $message = "Database Error: " . $stmt->error;
                    }
                } else {
                    $message = "Error uploading photo to server.";
                }
            }
        } else {
            $message = "Required fields (Name, Email, Phone, Password, Photo) missing.";
        }
    } 
    
    // --- Action: EDIT Contact ---
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id && $name && $email) {
            $sql = "UPDATE contacts SET name=?, email=?, phone=?, address=?, aadhaar=?, joining_date=?, status=?, notes=?, contract_amount=?, amount_paid=?";
            $params = [$name, $email, $phone, $address, $aadhaar, $joining_date ?: null, $status, $notes, $contract_amount, $amount_paid];
            $types = "ssssssssdd";
                if ($message) {
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Customer Not Found',text:'Customer not found!',showConfirmButton:false,timer:2000});</script>";
                }

            // Handle Photo
            if ($photo && $photo['name']) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
                $file_name = 'customer_' . $id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $file_name;
                if (move_uploaded_file($photo['tmp_name'], $target_path)) {
                    $sql .= ", photo=?";
                    $types .= "s";
                    $params[] = 'uploads/' . $file_name;
                }
            }

            // Handle Password
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password=?";
                $types .= "s";
                $params[] = $hashed_password;
            }

            $sql .= " WHERE id=?";
            $types .= "i";
            $params[] = $id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                exit();
            } else {
                $message = "Update Failed: " . $stmt->error;
            }
        }
    } 
    
    // --- Action: ADD PAYMENT ---
    elseif ($action === 'add_payment') {
        $contact_id = intval($_POST['contact_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $notes = trim($_POST['payment_notes'] ?? '');

        if ($contact_id && $amount > 0) {
            // 1. Insert History
            $stmt1 = $conn->prepare("INSERT INTO contact_payments (contact_id, amount, payment_date, notes) VALUES (?, ?, ?, ?)");
            $stmt1->bind_param("idss", $contact_id, $amount, $payment_date, $notes);
            $stmt1->execute();

            // 2. Update Total Paid
            $stmt2 = $conn->prepare("UPDATE contacts SET amount_paid = amount_paid + ? WHERE id = ?");
            $stmt2->bind_param("di", $amount, $contact_id);
            $stmt2->execute();
            
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit();
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Handle DELETE
// -----------------------------------------------------------------------------
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // Delete payments first (FK constraint usually)
    $conn->query("DELETE FROM contact_payments WHERE contact_id=$id");
    // Delete contact
    $conn->query("DELETE FROM contacts WHERE id=$id");

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// -----------------------------------------------------------------------------
// 3. Fetch Data
// -----------------------------------------------------------------------------
$contacts = $conn->query("SELECT * FROM contacts ORDER BY id DESC");
$total_contacts = $contacts->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Manager | Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
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

        /* Summary Box */
        .summary-box {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
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
        .contact-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #eaecf4;
        }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<?php include "topheader.php"; ?>
<?php include "sidenavbar.php"; ?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-person-lines-fill text-primary me-2"></i>Contact Manager</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addContactModal">
            <i class="bi bi-person-plus-fill me-1"></i> Add New Contact
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="summary-box">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8;">Total Contacts</h6>
                    <h2 class="mb-0 fw-bold"><?= $total_contacts ?></h2>
                </div>
                <div class="fs-1" style="opacity: 0.3;"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">ID</th>
                            <th width="20%">Contact Info</th>
                            <th width="15%">Contact Details</th>
                            <th width="10%">Status</th>
                            <th width="15%">Financials</th>
                            <th width="10%">Due</th>
                            <th width="25%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($contacts->num_rows > 0): ?>
                            <?php while ($row = $contacts->fetch_assoc()): 
                                $due = $row['contract_amount'] - $row['amount_paid'];
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#<?= $row['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                                                                <?php
                                                                                    $img = !empty($row['photo']) ? htmlspecialchars($row['photo']) : 'assets/default-avatar.png';
                                                                                    $img_url = $img;
                                                                                    if (strpos($img, 'http') === 0) {
                                                                                        // External image
                                                                                        $img_url .= '?v=' . time();
                                                                                    } else if (file_exists($img)) {
                                                                                        $img_url .= '?v=' . filemtime($img);
                                                                                    } else {
                                                                                        $img_url .= '?v=' . time();
                                                                                    }
                                                                                ?>
                                                                                <img src="<?= $img_url ?>" class="contact-thumb me-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['address'] ?? 'N/A') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($row['email']) ?></div>
                                    <div class="small"><i class="bi bi-phone me-1"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'Active'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-muted">Contract: <span class="fw-bold text-dark">₹<?= number_format($row['contract_amount']) ?></span></div>
                                    <div class="small text-muted">Paid: <span class="fw-bold text-success">₹<?= number_format($row['amount_paid']) ?></span></div>
                                </td>
                                <td>
                                    <?php if ($due > 0): ?>
                                        <span class="fw-bold text-danger">₹<?= number_format($due) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="contact_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                        
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="showPayments(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')" 
                                                data-bs-toggle="modal" data-bs-target="#paymentModal" title="Payments">
                                            <i class="bi bi-wallet2"></i>
                                        </button>
                                        
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="populateEditForm(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                                data-bs-toggle="modal" data-bs-target="#editContactModal" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                                     <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                                         onclick="event.preventDefault(); showDeleteContactSwal(this);" title="Delete">
                                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                            <script>
                                            function showDeleteContactSwal(el) {
                                                Swal.fire({
                                                    title: 'Delete Contact?',
                                                    text: 'Delete this contact and all payments?',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    cancelButtonColor: '#aaa',
                                                    confirmButtonText: 'Yes, delete',
                                                    cancelButtonText: 'Cancel',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        window.location.href = el.getAttribute('href');
                                                    }
                                                });
                                            }
                                            </script>
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No contacts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="addContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Contact</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" placeholder="Name" required>
                                <label>Full Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" name="email" placeholder="Email" required>
                                <label>Email Address *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="phone" placeholder="Phone" required>
                                <label>Phone Number *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="aadhaar" placeholder="Aadhaar">
                                <label>Aadhaar Number</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="address" placeholder="Address">
                                <label>Full Address</label>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="joining_date">
                                <label>Joining Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <label>Status</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                                <label>Password *</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="contract_amount" min="0" step="0.01" required>
                                <label>Contract Amount (₹)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="amount_paid" min="0" step="0.01" value="0">
                                <label>Initial Paid Amount (₹)</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" name="notes" style="height: 100px" placeholder="Notes"></textarea>
                                <label>Notes</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Upload Photo *</label>
                            <input type="file" class="form-control" name="photo" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editContactId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editContactName" name="name" placeholder="Name" required>
                                <label>Full Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="editContactEmail" name="email" placeholder="Email" required>
                                <label>Email Address *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editContactPhone" name="phone" placeholder="Phone" required>
                                <label>Phone Number *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editContactAadhaar" name="aadhaar" placeholder="Aadhaar">
                                <label>Aadhaar Number</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="editContactAddress" name="address" placeholder="Address">
                                <label>Full Address</label>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="editContactJoiningDate" name="joining_date">
                                <label>Joining Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="editContactStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <label>Status</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="password" class="form-control" name="password" placeholder="New Password">
                                <label>New Password (Optional)</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="editContactContractAmount" name="contract_amount" min="0" step="0.01" required>
                                <label>Contract Amount (₹)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="editContactAmountPaid" name="amount_paid" min="0" step="0.01" required>
                                <label>Total Paid (₹)</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="editContactNotes" name="notes" style="height: 100px" placeholder="Notes"></textarea>
                                <label>Notes</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Update Photo (Optional)</label>
                            <input type="file" class="form-control" name="photo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4">Update Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Payments for <span id="paymentContactName" class="fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <div class="mb-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Payment History</h6>
                    <div id="paymentHistory" class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                        <div class="text-center text-muted small py-3">Loading history...</div>
                    </div>
                </div>

                <hr>

                <h6 class="text-uppercase text-success small fw-bold mb-3">Add New Payment</h6>
                <form method="POST">
                    <input type="hidden" name="action" value="add_payment">
                    <input type="hidden" name="contact_id" id="paymentContactId">
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="amount" min="1" step="0.01" placeholder="Amount" required>
                                <label>Amount (₹)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                                <label>Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="payment_notes" placeholder="Notes">
                                <label>Note (Optional)</label>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success px-4"><i class="bi bi-plus-lg me-1"></i> Add Payment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "downfooter.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Populate Edit Form
    function populateEditForm(row) {
        if (typeof row === 'string') row = JSON.parse(row);
        
        document.getElementById('editContactId').value = row.id;
        document.getElementById('editContactName').value = row.name;
        document.getElementById('editContactEmail').value = row.email;
        document.getElementById('editContactPhone').value = row.phone;
        document.getElementById('editContactAddress').value = row.address || '';
        document.getElementById('editContactAadhaar').value = row.aadhaar || '';
        document.getElementById('editContactJoiningDate').value = row.joining_date || '';
        document.getElementById('editContactStatus').value = row.status;
        document.getElementById('editContactContractAmount').value = row.contract_amount;
        document.getElementById('editContactAmountPaid').value = row.amount_paid;
        document.getElementById('editContactNotes').value = row.notes || '';
    }

    // Show Payments
    function showPayments(contactId, contactName) {
        document.getElementById('paymentContactId').value = contactId;
        document.getElementById('paymentContactName').innerText = contactName;
        
        // Reset View
        const historyContainer = document.getElementById('paymentHistory');
        historyContainer.innerHTML = '<div class="text-center text-muted small py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</div>';

        // Fetch History
        fetch('contact_payments_api.php?contact_id=' + contactId)
            .then(res => res.text())
            .then(html => { 
                historyContainer.innerHTML = html; 
            })
            .catch(error => {
                historyContainer.innerHTML = '<div class="text-center text-danger small py-3">Failed to load payments.</div>';
            });
    }

    // Auto-refresh every 1 minute for admin panel
    setInterval(function() {
        location.reload();
    }, 60000);
</script>

</body>
</html>