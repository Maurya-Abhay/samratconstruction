<?php

  // Determine current script for active nav styling

  $__path = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');

  $__current = basename(parse_url($__path, PHP_URL_PATH));

  function __navActive($files) {

    global $__current;

    if (is_string($files)) { $files = [$files]; }

    return in_array($__current, $files, true) ? ' active' : '';

  }

?>

<!-- sidenavbar.php -->

<div class="sidebar" id="sidebar">

  <div class="logo"><img src="./assets/jp_construction_logo.webp" style="width:48px;"> JP</div>

  <nav class="nav flex-column">

    <a class="nav-link<?= __navActive(['dashboard.php','dash.php']) ?>" href="dashboard.php"><i class="bi bi-speedometer"></i> Dashboard</a>

    <a class="nav-link<?= __navActive('services.php') ?>" href="services.php"><i class="bi bi-grid-3x3-gap"></i> Category</a>

    <a class="nav-link<?= __navActive(['workers.php','workers_new.php']) ?>" href="workers.php"><i class="bi bi-people-fill"></i> Worker</a>

    <a class="nav-link<?= __navActive(['customers.php','contact_detail.php']) ?>" href="customers.php"><i class="bi bi-person-vcard"></i> Customers</a>

    <a class="nav-link<?= __navActive('reports.php') ?>" href="reports.php"><i class="bi bi-graph-up-arrow"></i> Reports</a>

    <a class="nav-link<?= __navActive('attendance_marking.php') ?>" href="attendance_marking.php"><i class="bi bi-fingerprint"></i> Mark Attendance</a>

    <a class="nav-link<?= __navActive('workers_face_list.php') ?>" href="workers_face_list.php"><i class="bi bi-person-bounding-box"></i> Worker Face List</a>
    <a class="nav-link<?= __navActive('logs_viewer.php') ?>" href="logs_viewer.php"><i class="bi bi-file-earmark-text"></i> Logs Viewer</a>
    <a class="nav-link<?= __navActive('send_reminders.php') ?>" href="send_reminders.php"><i class="bi bi-bell"></i> Send Reminders</a>

    <a class="nav-link<?= __navActive('manage_users.php') ?>" href="manage_users.php"><i class="bi bi-person-check"></i> Attendance Users</a>

    <a class="nav-link<?= __navActive('leave_management.php') ?>" href="leave_management.php"><i class="bi bi-calendar2-x"></i> Leave Management</a>

    <a class="nav-link<?= __navActive('holidays.php') ?>" href="holidays.php"><i class="bi bi-calendar2-event"></i> Holidays</a>

    <a class="nav-link<?= __navActive('notice_management.php') ?>" href="notice_management.php"><i class="bi bi-megaphone-fill"></i> Notice Management</a>

    <a class="nav-link<?= __navActive('contract_status.php') ?>" href="contract_status.php"><i class="bi bi-file-earmark-medical"></i> Contracts</a>

    <a class="nav-link<?= __navActive('budget.php') ?>" href="budget.php"><i class="bi bi-currency-rupee"></i> Budget</a>

    <a class="nav-link<?= __navActive('register_worker_face.php') ?>" href="register_worker_face.php"><i class="bi bi-person-plus"></i> Face Register</a>

    <a class="nav-link<?= __navActive(['settings.php']) ?>" href="settings.php"><i class="bi bi-sliders2"></i> Settings</a>


    <a class="nav-link<?= __navActive(['contact_messages.php']) ?>" href="contact_messages.php"><i class="bi bi-envelope-paper"></i> Contact Messages</a>
    <a class="nav-link<?= __navActive(['site_settings.php']) ?>" href="site_settings.php"><i class="bi bi-sliders2-vertical"></i> Site Settings</a>

    <a class="nav-link<?= __navActive('db_cleaner.php') ?>" href="db_cleaner.php"><i class="bi bi-database-gear"></i> DB Cleaner</a>

  </nav>

</div>