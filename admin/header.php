<style>
.logo-container {
    width: 200px;
    height: 60px;
    overflow: hidden;
    background-color: #000;
}

.logo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    mix-blend-mode: lighten;
}

</style>
<div class="container-fluid sticky-top bg-dark shadow-sm px-5 pe-lg-0 position-fixed">
        <nav class="navbar navbar-expand-lg bg-dark navbar-dark py-2 py-lg-0">
            <a href="dashboard.php" class="navbar-brand">
            <h1 class="m-0 display-9 text-uppercase text-white">
    <div class="logo-container">
        <img src="assets/216952510-removebg-preview (3).jpg" alt="" class="logo-img">
    </div>
</h1>

            </a>
    
            <div class="dropdown pb-4 ms-auto py-2 pe-4 pe-lg-5">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                    id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="assets/14420.png" alt="User Avatar" width="30" height="30" class="rounded-circle">
                    <span class="d-none d-sm-inline mx-1">Abhay Prasad</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow overflow pe-5">
                    <li><a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#newRegisterModal">New Register</a></li>
                    <li><a class="dropdown-item" href="#profileInfo" data-bs-toggle="modal" data-bs-target="#profileModal">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </div>