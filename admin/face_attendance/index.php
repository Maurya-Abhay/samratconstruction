<?php include '../topheader.php'; ?>
<?php include '../sidenavbar.php'; ?>
<style>
	body { background: #f4f6f9; }
	.info-card { max-width: 480px; margin: 2rem auto; box-shadow: 0 2px 16px #0002; border-radius: 16px; background: #fff; padding: 2.5rem; }
</style>
<div class="container py-4">
	<div class="info-card text-center">
		<h3 class="mb-3 text-primary">Face Attendance System</h3>
		<p class="mb-4 text-secondary">Scan, register, and manage worker face attendance easily.<br>Choose an action below:</p>
		<a href="../attendance_marking.php#face" class="btn btn-success btn-lg mb-3 w-100"><i class="bi bi-person-bounding-box me-2"></i> Scan Face for Attendance</a>
		<a href="../register_worker_face.php" class="btn btn-primary btn-lg mb-3 w-100"><i class="bi bi-person-plus me-2"></i> Register Worker Face</a>
		<a href="../workers_face_list.php" class="btn btn-info btn-lg w-100"><i class="bi bi-list-ul me-2"></i> View Registered Faces</a>
	</div>
</div>
<?php include '../downfooter.php'; ?>
