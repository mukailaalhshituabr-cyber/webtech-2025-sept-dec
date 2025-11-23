<?php
session_start();

require_once "config.php";

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];


$sql = "SELECT r.id AS request_id, 
               s.name AS student_name, 
               c.course_code, 
               c.course_name, 
               r.request_date,
               r.status
        FROM course_requests r
        JOIN student s ON r.student_id = s.student_id
        JOIN course c ON r.course_id = c.course_id
        WHERE c.faculty_id = '$faculty_id'
        ORDER BY r.request_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Course Requests</title>
    <style>
        body { font-family: Arial; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #333; color: white; }
        .approve-btn {
            background-color: green; color: white; padding: 8px 14px;
            border: none; cursor: pointer;
        }
        .reject-btn {
            background-color: red; color: white; padding: 8px 14px;
            border: none; cursor: pointer;
        }
        .pending { color: orange; font-weight: bold; }
        .approved { color: green; font-weight: bold; }
        .rejected { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h2>Manage Student Course Requests</h2>

<table>
    <tr>
        <th>Student Name</th>
        <th>Course</th>
        <th>Request Date</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['student_name']; ?></td>
            <td><?php echo $row['course_code'] . " - " . $row['course_name']; ?></td>
            <td><?php echo $row['request_date']; ?></td>
            <td class="<?php echo strtolower($row['status']); ?>">
                <?php echo ucfirst($row['status']); ?>
            </td>
            <td>
                <?php if ($row['status'] == 'Pending') { ?>
                    <a href="faculty_approve.php?id=<?php echo $row['request_id']; ?>">
                        <button class="approve-btn">Approve</button>
                    </a>
                    <a href="faculty_reject.php?id=<?php echo $row['request_id']; ?>">
                        <button class="reject-btn">Reject</button>
                    </a>
                <?php } else { ?>
                    No Action Available
                <?php } ?>
            </td>
        </tr>
    <?php } ?>

</table>

</body>
</html>