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
$name = $user['name'];

$message = "";

if (isset($_POST['request_course'])) {
    $course_id = $_POST['course_id'];

    $check = $conn->query("
        SELECT * FROM requests 
        WHERE student_id='$student_id' AND course_id='$course_id'
    ");

    if ($check->num_rows > 0) {
        $message = "You already sent a request for this course!";
    } else {
        $conn->query("
            INSERT INTO requests (student_id, course_id, status)
            VALUES ('$student_id', '$course_id', 'pending')
        ");
        $message = "Request sent successfully!";
    }
}

$allCourses = $conn->query("
    SELECT courses.*, users.name AS faculty_name
    FROM courses
    JOIN users ON courses.faculty_id = users.id
");

$enrolled = $conn->query("
    SELECT courses.course_code, courses.course_name
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.course_id
    WHERE enrollments.student_id='$student_id'
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="container">

    <h1>Welcome, <?php echo $name; ?> (Student)</h1>
    <a href="logout.php">Logout</a>
    <br><br>

    <?php if (!empty($message)) : ?>
        <p class="error-message" style="background:#d4edda; color:#155724;">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <h2>Your Enrolled Courses</h2>

    <?php if ($enrolled->num_rows > 0) : ?>
        <table border="1" cellpadding="12" style="width:100%; border-collapse:collapse;">
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
            </tr>

            <?php while ($row = $enrolled->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo $row['course_code']; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                </tr>
            <?php endwhile; ?>

        </table>
    <?php else : ?>
        <p>You are not enrolled in any course yet.</p>
    <?php endif; ?>


    <br><br>

    <h2>Available Courses</h2>

    <?php if ($allCourses->num_rows > 0) : ?>
        <table border="1" cellpadding="12" style="width:100%; border-collapse:collapse;">
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Faculty</th>
                <th>Action</th>
            </tr>

            <?php while ($row = $allCourses->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo $row['course_code']; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                    <td><?php echo $row['faculty_name']; ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="course_id" value="<?php echo $row['course_id']; ?>">
                            <button type="submit" name="request_course">Request Join</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>

        </table>
    <?php else : ?>
        <p>No courses available yet.</p>
    <?php endif; ?>

</div>
</body>
</html>