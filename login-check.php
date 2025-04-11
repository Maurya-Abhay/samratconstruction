<?php
session_start();
include "database.php";
$email=$_POST['email'];
$pass=md5($_POST['password']);

if($con){
	$sql="select * from where email='$email' and password='$pass'";
	$x=mysqli_num_rows(mysqli_query($con,$sql));
	if($x==1){
		$_SESSION['email']=$email;
		header("location: index.php");
	}
	else{ ?>
<script>
alert("wrong login details");
window.location="login.php";
</script>
<?php
	}
}
?>