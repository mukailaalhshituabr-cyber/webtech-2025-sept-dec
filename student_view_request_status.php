<?php
session_start();

require_once "config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT r.id, r.request_date, r.status,
               c.course_code, c.course_name
        FROM course_requests r
        JOIN course c ON r.course_id = c.course_id
        WHERE r.student_id = '$student_id'
        ORDER BY r.request_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Course Requests</title>
    <style>
        body { font-family: Arial; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #333; color: white; }
        .pending { color: orange; font-weight: bold; }
        .approved { color: green; font-weight: bold; }
        .rejected { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h2>My Course Request Status</h2>

<table>
    <tr>
        <th>Course Code</th>
        <th>Course Name</th>
        <th>Request Date</th>
        <th>Status</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['course_code']; ?></td>
            <td><?php echo $row['course_name']; ?></td>
            <td><?php echo $row['request_date']; ?></td>
            <td class="<?php echo strtolower($row['status']); ?>">
                <?php echo $row['status']; ?>
            </td>
        </tr>
    <?php } ?>

</table>

</body>
</html>