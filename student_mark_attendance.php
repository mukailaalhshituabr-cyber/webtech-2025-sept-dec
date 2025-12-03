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

/* Get student's enrolled courses */
$courses = $conn->query("
    SELECT c.course_id, c.course_code, c.course_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE e.student_id = '$student_id'
");

if (isset($_POST['mark_attendance'])) {
    $course_id = (int)$_POST['course_id'];
    $code = trim($_POST['attendance_code']);

    if (empty($course_id) || empty($code)) {
        $message = "Please select a course and enter the code.";
    } else {
        // Check enrolled
        $checkEnrolled = $conn->query("
            SELECT * FROM enrollments
            WHERE student_id='$student_id' AND course_id='$course_id'
        ");

        if ($checkEnrolled->num_rows == 0) {
            $message = "You are not enrolled in this course.";
        } else {
            // Find open session with that code for this course
            $code = $conn->real_escape_string($code);
            $sessionRes = $conn->query("
                SELECT * FROM sessions
                WHERE course_id='$course_id'
                  AND attendance_code='$code'
                  AND status='Open'
                ORDER BY session_date DESC
                LIMIT 1
            ");

            if ($sessionRes->num_rows == 0) {
                $message = "Invalid code or session is closed.";
            } else {
                $session = $sessionRes->fetch_assoc();
                $session_id = $session['session_id'];

                // Check if already marked
                $attRes = $conn->query("
                    SELECT * FROM attendance
                    WHERE session_id='$session_id' AND student_id='$student_id'
                ");

                if ($attRes->num_rows > 0) {
                    $attRow = $attRes->fetch_assoc();
                    if ($attRow['status'] === 'Present') {
                        $message = "You have already marked attendance for this session.";
                    } else {
                        $conn->query("
                            UPDATE attendance
                            SET status='Present', marked_at = NOW()
                            WHERE attendance_id = '{$attRow['attendance_id']}'
                        ");
                        $message = "Attendance updated to Present.";
                    }
                } else {
                    $conn->query("
                        INSERT INTO attendance (session_id, student_id, marked_at, status)
                        VALUES ('$session_id', '$student_id', NOW(), 'Present')
                    ");
                    $message = "Attendance marked successfully!";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance</title>
    <style>
        body { font-family: Arial; }
        .box { max-width: 400px; margin: 40px auto; padding: 20px; border:1px solid #ccc; border-radius:8px; background:#fff; }
        input, select, button { width:100%; padding:10px; margin:8px 0; }
    </style>
</head>
<body>

<div class="box">
    <h2>Mark Attendance</h2>
    <p>Student: <?php echo htmlspecialchars($name); ?></p>
    <a href="student_page.php">Back to Dashboard</a><br><br>

    <?php if (!empty($message)) : ?>
        <p style="padding:10px; background:#d4edda; color:#155724;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <label>Course</label>
        <select name="course_id" required>
            <option value="">-- Select Course --</option>
            <?php while ($c = $courses->fetch_assoc()) : ?>
                <option value="<?php echo $c['course_id']; ?>">
                    <?php echo htmlspecialchars($c['course_code'] . " - " . $c['course_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Attendance Code</label>
        <input type="text" name="attendance_code" placeholder="Enter code given in class" required>

        <button type="submit" name="mark_attendance">Submit</button>
    </form>
</div>

</body>
</html>
