<?php





// mark_attendance.php for attendance module





include "../admin/database.php";





header('Content-Type: application/json');


date_default_timezone_set('Asia/Kolkata');





if ($_SERVER['REQUEST_METHOD'] === 'POST') {





    $worker_id = intval($_POST['worker_id'] ?? 0);





    $date = date('Y-m-d');





    if ($worker_id) {





        $stmt = $conn->prepare("SELECT id FROM worker_attendance WHERE worker_id=? AND date=?");





        $stmt->bind_param("is", $worker_id, $date);





        $stmt->execute();





        $stmt->store_result();





        if ($stmt->num_rows > 0) {





            echo json_encode(['status'=>'already','msg'=>'Attendance already marked today.']);





        } else {





            $stmt2 = $conn->prepare("INSERT INTO worker_attendance (worker_id, date, status) VALUES (?, ?, 'Present')");





            $stmt2->bind_param("is", $worker_id, $date);





            if ($stmt2->execute()) {





                echo json_encode(['status'=>'success','msg'=>'Attendance marked!']);





            } else {





                echo json_encode(['status'=>'fail','msg'=>'Failed to mark attendance. MySQL: ' . $stmt2->error]);





            }





        }





    } else {





        echo json_encode(['status'=>'fail','msg'=>'Invalid worker ID.']);





    }





} else {





    echo json_encode(['status'=>'fail','msg'=>'Invalid request.']);


}



?>
<script>
// Auto-refresh every 1 minute
setInterval(function() {
    location.reload();
}, 60000);
</script>





