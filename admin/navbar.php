<div class="modal fade" id="newRegisterModal" tabindex="-1" aria-labelledby="newRegisterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newRegisterModalLabel">New User Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="registerFullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="registerFullName" placeholder="Enter your full name"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="registerEmail" placeholder="Enter your email"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="registerPassword"
                            placeholder="Create a password" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerConfirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="registerConfirmPassword"
                            placeholder="Confirm your password" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerConfirmPassword" class="form-label">Image</label>
                        <input type="image" class="form-control" id="registerConfirmPassword"
                            placeholder="Confirm your password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Profile Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="profileName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="profileName" value="Abhay Prasad" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="profileEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="profileEmail" value="abhay.prasad@example.com"
                            disabled>
                    </div>
                    <div class="mb-3">
                        <label for="profileRole" class="form-label">Role</label>
                        <input type="text" class="form-control" id="profileRole" value="Administrator" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="profileStatus" class="form-label">Status</label>
                        <input type="text" class="form-control" id="profileStatus" value="Active" disabled>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Edit Profile</button>
            </div>
        </div>
    </div>
</div>
<style>
    .bg-light-radial {
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2), rgba(0, 0, 0, 0.4));
    }
</style>


<div class="container-fluid position-relative">
    <div class="row flex-nowrap">

        <div class="col-auto px-0 bg-dark">
            <div
                class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-100 pt-5">
                <a href="/" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                    <span class="fs-5 d-none ">Dashboard</span>
                </a>
                <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start" id="menu">
                    <li>
                        <a href="dashboard.php" class="nav-link px-0 align-middle pe-4 ps-4 mt-3">
                            <i class="bi-house-door"></i> <span class="ms-1 d-none d-sm-inline">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="services.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="bi-building"></i> <span class="ms-1 d-none d-sm-inline">Category</span>
                        </a>
                    </li>
                    <li>
                        <a href="workers.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-hard-hat"></i> <span class="ms-1 d-none d-sm-inline">Worker</span>
                        </a>
                    </li>
                    <li>
                        <a href="customers.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-users"></i> <span class="ms-1 d-none d-sm-inline">Customers</span>
                        </a>
                    </li>
                    <li>
                        <a href="notification.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="bi-bell"></i> <span class="ms-1 d-none d-sm-inline">Notifications</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-chart-pie"></i> <span class="ms-1 d-none d-sm-inline">Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendence.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-check-circle"></i> <span class="ms-1 d-none d-sm-inline">Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="contracts.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-scroll"></i> <span class="ms-1 d-none d-sm-inline">Contracts</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-cogs"></i> <span class="ms-1 d-none d-sm-inline">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="budget.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-wallet"></i> <span class="ms-1 d-none d-sm-inline">Budget</span>
                        </a>
                    </li>
                    <li>
                        <a href="materials.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-box"></i> <span class="ms-1 d-none d-sm-inline">Materials</span>
                        </a>
                    </li>
                    <li>
                        <a href="help.php" class="nav-link px-0 align-middle pe-4 ps-4">
                            <i class="fa fa-life-ring"></i> <span class="ms-1 d-none d-sm-inline">Help/Support</span>
                        </a>
                    </li>

                </ul>
            </div>
        </div>