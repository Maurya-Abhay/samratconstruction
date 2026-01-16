<!-- downfooter.php -->
<style>
  .extra-margin { margin-top: 20rem !important; }
</style>
<footer class="bg-white border-top py-3 px-3 mt-5 extra-margin">

  <div class="container-fluid">

    <div class="row align-items-center gy-2">

      <div class="col-md-6 text-muted small text-center text-md-start">

        © 2025 Admin Panel. All rights reserved.

      </div>

      <div class="col-md-6 text-center text-md-end">

        <a href="privacy.php" class="text-muted mx-2 text-decoration-none">Privacy Policy</a>

        <a href="terms.php" class="text-muted mx-2 text-decoration-none">Terms & Conditions</a>

        <a href="help.php" class="text-muted mx-2 text-decoration-none">Help</a>

      </div>

    </div>

  </div>

</footer>

<div id="sidebarOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.15);z-index:999;"></div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>

  <script>

    const sidebar = document.getElementById('sidebar');

    const sidebarToggle = document.getElementById('sidebarToggle');

    const sidebarOverlay = document.getElementById('sidebarOverlay');

    const mainContent = document.getElementById('mainContent');

  let sidebarVisible = false;

    function showSidebar() {

      sidebar.classList.add('show');

      sidebar.classList.remove('hide');

      sidebarOverlay.style.display = window.innerWidth <= 991 ? 'block' : 'none';

      mainContent.style.marginLeft = window.innerWidth > 991 ? '200px' : '0';

      sidebarVisible = true;

    }

    function hideSidebar() {

      sidebar.classList.remove('show');

      sidebar.classList.add('hide');

      sidebarOverlay.style.display = 'none';

      mainContent.style.marginLeft = '0';

      sidebarVisible = false;

    }

    // On page load, always hide sidebar for mobile

    if(window.innerWidth <= 991) {

      hideSidebar();

    } else {

      showSidebar();

      sidebarVisible = true;

    }

    sidebarToggle.addEventListener('click', function(e) {

      e.stopPropagation();

      if (sidebarVisible) {

        hideSidebar();

      } else {

        showSidebar();

      }

    });

    sidebarOverlay.addEventListener('click', function() {

      hideSidebar();

    });

    // Hide sidebar when clicking outside (mobile only)

    document.addEventListener('click', function(e) {

      if(window.innerWidth <= 991 && sidebarVisible) {

        if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {

          hideSidebar();

        }

      }

    });

    window.addEventListener('resize', function() {

      if(window.innerWidth > 991) {

        showSidebar();

      } else {

        hideSidebar();

      }

    });

  </script>

<!-- Bootstrap JS (bundle includes Popper) for modals, dropdowns, etc. -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

