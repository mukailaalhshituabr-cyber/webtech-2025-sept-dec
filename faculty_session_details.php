
<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'faculty') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['session_id'])) {
    die("No session selected.");
}

$session_id = (int)$_GET['session_id'];

$email = $_SESSION['email'];
$message = "";

// CSRF token generation/verification
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

// Fetch user via PDO prepared statement
if (!isset($pdo)) {
    die('Database connection not available.');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    die('User not found.');
}
$faculty_id = (int)$user['id'];
$name = $user['name'];

// Get session and course info, ensure belongs to this faculty
$stmt = $pdo->prepare(
    'SELECT s.*, c.course_code, c.course_name, c.course_id, c.faculty_id
     FROM sessions s
     JOIN courses c ON s.course_id = c.course_id
     WHERE s.session_id = ? AND c.faculty_id = ? LIMIT 1'
);
$stmt->execute([$session_id, $faculty_id]);
$session = $stmt->fetch();
if (!$session) {
    die("Session not found or not your course.");
}
$course_id = (int)$session['course_id'];

/* Handle save attendance with CSRF check */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $statuses = $_POST['status'] ?? [];

    // Prepare statements once
    $selectStmt = $pdo->prepare('SELECT id FROM attendance WHERE session_id = ? AND student_id = ? LIMIT 1');
    $updateStmt = $pdo->prepare('UPDATE attendance SET status = ?, marked_at = NOW() WHERE session_id = ? AND student_id = ?');
    $insertStmt = $pdo->prepare('INSERT INTO attendance (session_id, student_id, marked_at, status) VALUES (?, ?, NOW(), ?)');

    foreach ($statuses as $student_id => $statusValue) {
        $student_id = (int)$student_id;
        $statusValue = ($statusValue === 'Present') ? 'Present' : (($statusValue === 'Absent') ? 'Absent' : '');
        if ($statusValue === '') continue;

        $selectStmt->execute([$session_id, $student_id]);
        $exists = $selectStmt->fetch();
        if ($exists) {
            $updateStmt->execute([$statusValue, $session_id, $student_id]);
        } else {
            $insertStmt->execute([$session_id, $student_id, $statusValue]);
        }
    }

    $message = "Attendance updated.";
}

// Get enrolled students for this course
$stmt = $pdo->prepare(
    'SELECT u.id, u.name
     FROM enrollments e
     JOIN users u ON e.student_id = u.id
     WHERE e.course_id = ?
     ORDER BY u.name ASC'
);
$stmt->execute([$course_id]);
$students = $stmt->fetchAll();

// Get existing attendance for this session
$stmt = $pdo->prepare('SELECT student_id, status FROM attendance WHERE session_id = ?');
$stmt->execute([$session_id]);
$attendanceMap = [];
foreach ($stmt->fetchAll() as $row) {
    $attendanceMap[(int)$row['student_id']] = $row['status'];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<h2>Attendance for <?php echo htmlspecialchars($session['course_code'] . " - " . $session['course_name']); ?>
</h2>
<p>
    Date: <?php echo htmlspecialchars($session['session_date']); ?><br>
    Code: <strong><?php echo htmlspecialchars($session['attendance_code']); ?></strong><br>
    Status: <?php echo htmlspecialchars($session['status']); ?>
</p>

<a href="faculty_sessions.php">Back to Sessions</a>

<?php if (!empty($message)) : ?>
    <p style="padding:10px; background:#d4edda; color:#155724;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<form method="post">
    <table>
        <tr>
            <th>Student</th>
            <th>Mark</th>
            <th>Current Status</th>
        </tr>
        <?php while ($stu = $students->fetch_assoc()) : 
            $sid = $stu['id'];
            $current = isset($attendanceMap[$sid]) ? $attendanceMap[$sid] : 'Not Marked';
        ?>
            <tr>
                <td><?php echo htmlspecialchars($stu['name']); ?></td>
                <td>
                    <select name="status[<?php echo $sid; ?>]">
                        <option value="">-- No Change --</option>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                    </select>
                </td>
                <td class="<?php echo strtolower($current); ?>">
                    <?php echo htmlspecialchars($current); ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <br>
    <button type="submit" name="save_attendance">Save Attendance</button>
</form>

</body>
</html>
