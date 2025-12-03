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

$selected_course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

/* Get student's courses */
$courses = $conn->query("
    SELECT c.course_id, c.course_code, c.course_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE e.student_id = '$student_id'
");

/* If a course is selected, get its sessions + attendance */
$attendanceRows = [];
if ($selected_course_id > 0) {
    $sql = "
        SELECT s.session_date,
               COALESCE(a.status, 'Absent') AS status
        FROM sessions s
        LEFT JOIN attendance a
          ON a.session_id = s.session_id
         AND a.student_id = '$student_id'
        WHERE s.course_id = '$selected_course_id'
        ORDER BY s.session_date ASC
    ";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $attendanceRows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Attendance</title>
    <style>
        body { font-family: Arial; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #333; color: white; }
        .Present { color: green; font-weight: bold; }
        .Absent { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h2>My Attendance (Daily)</h2>
<p>Student: <?php echo htmlspecialchars($name); ?></p>
<a href="student_page.php">Back to Dashboard</a><br><br>

<form method="get">
    <label>Select Course: </label>
    <select name="course_id" onchange="this.form.submit()">
        <option value="0">-- Select Course --</option>
        <?php
        // reset pointer for display
        mysqli_data_seek($courses, 0);
        while ($c = $courses->fetch_assoc()) :
        ?>
            <option value="<?php echo $c['course_id']; ?>"
                <?php if ($selected_course_id == $c['course_id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($c['course_code'] . " - " . $c['course_name']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if ($selected_course_id > 0) : ?>
    <?php if (count($attendanceRows) > 0) : ?>
        <table>
            <tr>
                <th>Session Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($attendanceRows as $row) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['session_date']); ?></td>
                    <td class="<?php echo htmlspecialchars($row['status']); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else : ?>
        <p>No sessions for this course yet.</p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
