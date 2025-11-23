<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT c.course_code, c.course_name, f.name AS faculty_name,
               e.academic_year, e.semester
        FROM enrollstudent e
        JOIN course c ON e.course_id = c.course_id
        JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = '$student_id'
        ORDER BY e.academic_year DESC, e.semester DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Enrolled Courses</title>
    <style>
        body { font-family: Arial; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #333; color: white; }
    </style>
</head>
<body>

<h2>My Enrolled Courses</h2>

<table>
    <tr>
        <th>Course Code</th>
        <th>Course Name</th>
        <th>Faculty</th>
        <th>Academic Year</th>
        <th>Semester</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['course_code']; ?></td>
            <td><?php echo $row['course_name']; ?></td>
            <td><?php echo $row['faculty_name']; ?></td>
            <td><?php echo $row['academic_year']; ?></td>
            <td><?php echo $row['semester']; ?></td>
        </tr>
    <?php } ?>

</table>

</body>
</html>