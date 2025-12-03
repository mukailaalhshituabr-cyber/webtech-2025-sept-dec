<?php
require_once "config.php";

if (!isset($_GET['id'])) {
    die("Invalid request!");
}

$request_id = (int)$_GET['id'];

$sql = "SELECT * FROM requests WHERE request_id='$request_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Request not found!");
}

$request = $result->fetch_assoc();

$student_id = $request['student_id'];
$course_id = $request['course_id'];

$conn->query("
    UPDATE requests
    SET status='approved'
    WHERE request_id='$request_id'
");

$check = $conn->query("
    SELECT * FROM enrollments
    WHERE student_id='$student_id' AND course_id='$course_id'
");

if ($check->num_rows == 0) {
    $conn->query("
        INSERT INTO enrollments (student_id, course_id)
        VALUES ('$student_id', '$course_id')
    ");
}

header("Location: faculty_manage_requests.php");
exit();
?>





<?php

/*require_once "config.php";

if (!isset($_GET['id'])) {
    die("Invalid request!");
}

$request_id = $_GET['id'];


$sql = "SELECT * FROM course_requests WHERE id='$request_id'";
$result = $conn->query($sql);
$request = $result->fetch_assoc();

$student_id = $request['student_id'];
$course_id = $request['course_id'];
$year = date("Y");
$semester = "Fall";


$conn->query("UPDATE course_requests SET status='Approved' WHERE id='$request_id'");


$check = $conn->query("SELECT * FROM enrollstudent 
                       WHERE student_id='$student_id' AND course_id='$course_id'");

if ($check->num_rows == 0) {
    $conn->query("INSERT INTO enrollstudent(student_id, course_id, academic_year, semester)
                  VALUES('$student_id', '$course_id', '$year', '$semester')");
}

header("Location: faculty_manage_requests.php");
exit();
?>
*/