<?php
// Block direct access to this file
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
	http_response_code(403);
	echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Forbidden</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:linear-gradient(135deg,#e0e7ff,#b4c2ff);min-height:100vh;display:flex;align-items:center;justify-content:center}.card{border-radius:1rem;box-shadow:0 1rem 3rem rgba(0,0,0,0.10);max-width:400px;margin:auto}</style></head><body><div class="card p-4 text-center"><h2 class="text-danger mb-3">Access Denied</h2><p class="mb-3 text-secondary">Direct access to this file is forbidden.<br>Database connection is protected for security reasons.</p><a href="index.php" class="btn btn-primary">Go to Admin Panel</a><div class="mt-3 text-muted small">&copy; 2025 Samrat Construction Private Limited</div></div></body></html>';
	exit;
}

$conn = mysqli_connect("127.0.0.1", "root", "", "smrt_db", 3307);

//$conn=mysqli_connect("sql210.infinityfree.com","if0_40234198","8unE9d7k1tb9mR","if0_40234198_samrat_db");
?>