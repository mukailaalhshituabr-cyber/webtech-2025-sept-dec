<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];
$queryUser = $conn->query("SELECT * FROM users WHERE email='$email'");
$user = $queryUser->fetch_assoc();
$student_id = $user['id'];

$message = "";

if (isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];

    $check = $conn->query("
        SELECT * FROM requests 
        WHERE student_id='$student_id' AND course_id='$course_id'
    ");

    $checkEnrolled = $conn->query("
        SELECT * FROM enrollments
        WHERE student_id='$student_id' AND course_id='$course_id'
    ");

    if ($checkEnrolled->num_rows > 0) {
        $message = "You are already enrolled in this course!";
    } else if ($check->num_rows > 0) {
        $message = "You already requested this course!";
    } else {
        $conn->query("
            INSERT INTO requests (student_id, course_id, status)
            VALUES ('$student_id', '$course_id', 'pending')
        ");

        $message = "Course request submitted successfully!";
    }
} else {
    $message = "Invalid request.";
}

$_SESSION['request_message'] = $message;

header("Location: student_page.php");
exit();

?>