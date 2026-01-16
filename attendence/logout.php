<?php


session_start();
$_SESSION = [];
session_unset();
session_destroy();
header('Location: login.php');
exit;

// Remove all cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600);
    }
}
// Hindi: Logout ke baad session cookie bhi expire karo
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Logging out...</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <!-- SweetAlert2 -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



    <!-- Google Font -->

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">



    <style>

        body {

            font-family: 'Inter', sans-serif;

            background: linear-gradient(120deg, #f3f4f6, #e2e8f0);

            height: 100vh;

            margin: 0;

            display: flex;

            justify-content: center;

            align-items: center;

        }

    </style>

</head>

<body>



<script>

    Swal.fire({

        icon: 'success',

        title: 'Logged out successfully!',

        showConfirmButton: false,

        timer: 2000,

        timerProgressBar: true,

        willClose: () => {

            window.location.href = 'login.php'; // redirect to login page

        }

    });

</script>



</body>

</html>