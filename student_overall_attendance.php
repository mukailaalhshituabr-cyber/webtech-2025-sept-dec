<?php
/*session_start();
require_once  "config.php";

if ($_SESSION['role'] != 'Student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['id'];
$course_id = $_GET['course_id'];

$course = $conn->query("SELECT * FROM courses WHERE course_id = $course_id")->fetch_assoc();

$total_sessions = $conn->query("
    SELECT COUNT(*) AS total 
    FROM sessions 
    WHERE course_id = $course_id
")->fetch_assoc()['total'];

$present = $conn->query("
    SELECT COUNT(*) AS c 
    FROM attendance a
    JOIN sessions ss ON a.session_id = ss.session_id
    WHERE a.student_id = $student_id AND ss.course_id = $course_id AND a.status = 'Present'
")->fetch_assoc()['c'];

$late = $conn->query("
    SELECT COUNT(*) AS c
    FROM attendance a
    JOIN sessions ss ON a.session_id = ss.session_id
    WHERE a.student_id = $student_id AND ss.course_id = $course_id AND a.status = 'Late'
")->fetch_assoc()['c'];

$absent = $conn->query("
    SELECT COUNT(*) AS c
    FROM attendance a
    JOIN sessions ss ON a.session_id = ss.session_id
    WHERE a.student_id = $student_id AND ss.course_id = $course_id AND a.status = 'Absent'
")->fetch_assoc()['c'];

// Grade
$grade_row = $conn->query("
    SELECT grade FROM grades 
    WHERE student_id = $student_id AND course_id = $course_id
")->fetch_assoc();
$grade = $grade_row ? $grade_row['grade'] : "N/A";
?>
<!DOCTYPE html>
<html>
<head>
<title>My Attendance & Grade</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2><?php echo $course['course_name']; ?> — Attendance Summary</h2>

<p><b>Total Sessions:</b> <?php echo $total_sessions; ?></p>
<p><b>Present:</b> <?php echo $present; ?></p>
<p><b>Late:</b> <?php echo $late; ?></p>
<p><b>Absent:</b> <?php echo $absent; ?></p>

<?php
$attendance_percent = ($total_sessions > 0)
    ? round(($present / $total_sessions) * 100, 2)
    : 0;
?>

<p><b>Attendance Percentage:</b> <?php echo $attendance_percent . "%"; ?></p>
<p><b>Your Grade:</b> <?php echo $grade; ?></p>

<h3>Attendance Chart</h3>
<canvas id="attendanceChart" width="300" height="300"></canvas>

<script>
var ctx = document.getElementById("attendanceChart");
new Chart(ctx, {
    type: "pie",
    data: {
        labels: ["Present", "Late", "Absent"],
        datasets: [{
            data: [<?php echo $present; ?>, <?php echo $late; ?>, <?php echo $absent; ?>]
        }]
    }
});
</script>

</body>
</html>





<?php
*/
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

// For each enrolled course, calculate total sessions and attended 
$sql = "
    SELECT 
        c.course_id,
        c.course_code,
        c.course_name,
        COUNT(DISTINCT s.session_id) AS total_sessions,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS attended
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN sessions s ON c.course_id = s.course_id
    LEFT JOIN attendance a ON a.session_id = s.session_id AND a.student_id = '$student_id'
    WHERE e.student_id = '$student_id'
    GROUP BY c.course_id, c.course_code, c.course_name
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Overall Attendance</title>
    <style>
        body { font-family: Arial; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #333; color: white; }
    </style>
</head>
<body>

<h2>Overall Attendance</h2>
<p>Student: <?php echo htmlspecialchars($name); ?></p>
<a href="student_page.php">Back to Dashboard</a><br><br>

<table>
    <tr>
        <th>Course Code</th>
        <th>Course Name</th>
        <th>Total Sessions</th>
        <th>Attended</th>
        <th>Attendance %</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) : 
        $total = (int)$row['total_sessions'];
        $attended = (int)$row['attended'];
        $percent = $total > 0 ? round(($attended / $total) * 100) : 0;
    ?>
        <tr>
            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
            <td><?php echo $total; ?></td>
            <td><?php echo $attended; ?></td>
            <td><?php echo $percent; ?>%</td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>

