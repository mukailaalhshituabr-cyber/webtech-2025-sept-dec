<?php
session_start();
require_once "config.php";


if (!isset($_SESSION['email']) || $_SESSION['role'] != 'faculty') {
    header("Location: index.php");
    exit();
}


$email = $_SESSION['email'];
$queryUser = $conn->query("SELECT * FROM users WHERE email='$email'");
$user = $queryUser->fetch_assoc();
$faculty_id = $user['id'];
$name = $user['name'];


$message = "";

if (isset($_POST['create_course'])) {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];


    $insert = $conn->query("
        INSERT INTO courses (course_code, course_name, faculty_id)
        VALUES ('$course_code', '$course_name', '$faculty_id')
    ");

    if ($insert) {
        $message = "Course created successfully!";
    } else {
        $message = "Error creating course.";
    }
}


$courses = $conn->query("
    SELECT * FROM courses WHERE faculty_id='$faculty_id'
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="container">

    <h1>Welcome, <?php echo $name; ?> (Faculty)</h1>
    <a href="logout.php">Logout</a>
    <br><br>


    <?php if (!empty($message)) : ?>
        <p class="error-message" style="background:#d4edda; color:#155724;">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>


    <div class="form-box active">
        <h2>Create a New Course</h2>
        <form method="post">
            <input type="text" name="course_code" placeholder="Course Code (e.g. CSC101)" required>
            <input type="text" name="course_name" placeholder="Course Name" required>
            <button type="submit" name="create_course">Create Course</button>
        </form>
    </div>

    <br><br>


    <h2>Your Courses</h2>

    <?php if ($courses->num_rows > 0) : ?>
        <table border="1" cellpadding="12" style="width:100%; border-collapse:collapse;">
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
            </tr>

            <?php while ($row = $courses->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo $row['course_code']; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                </tr>
            <?php endwhile; ?>

        </table>
    <?php else : ?>
        <p>No courses created yet.</p>
    <?php endif; ?>

</div>
</body>
</html>