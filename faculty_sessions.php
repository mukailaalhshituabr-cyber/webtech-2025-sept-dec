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

/* Handle create session */
if (isset($_POST['create_session'])) {
    $course_id = (int)$_POST['course_id'];
    $session_date = $_POST['session_date']; // datetime-local string

    if (empty($course_id) || empty($session_date)) {
        $message = "Please select a course and date.";
    } else {
        $code = rand(100000, 999999); // 6-digit code
        $session_date = $conn->real_escape_string($session_date);

        $insert = $conn->query("
            INSERT INTO sessions (course_id, session_date, attendance_code, status)
            VALUES ('$course_id', '$session_date', '$code', 'Open')
        ");

        if ($insert) {
            $message = "Session created successfully. Code: $code";
        } else {
            $message = "Error creating session.";
        }
    }
}

/* Handle status change or delete */
if (isset($_POST['session_action']) && isset($_POST['session_id'])) {
    $session_id = (int)$_POST['session_id'];
    $action = $_POST['session_action'];

    // Make sure this session belongs to this faculty
    $check = $conn->query("
        SELECT s.*, c.faculty_id
        FROM sessions s
        JOIN courses c ON s.course_id = c.course_id
        WHERE s.session_id = '$session_id' AND c.faculty_id = '$faculty_id'
    ");

    if ($check->num_rows > 0) {
        if ($action === 'open') {
            $conn->query("UPDATE sessions SET status='Open' WHERE session_id='$session_id'");
            $message = "Session opened.";
        } elseif ($action === 'close') {
            $conn->query("UPDATE sessions SET status='Closed' WHERE session_id='$session_id'");
            $message = "Session closed.";
        } elseif ($action === 'delete') {
            // delete attendance and then session
            $conn->query("DELETE FROM attendance WHERE session_id='$session_id'");
            $conn->query("DELETE FROM sessions WHERE session_id='$session_id'");
            $message = "Session and its attendance deleted.";
        }
    } else {
        $message = "Invalid session or not your course.";
    }
}

/* Get faculty's courses */
$courses = $conn->query("
    SELECT * FROM courses WHERE faculty_id='$faculty_id'
");

/* Get sessions for faculty's courses */
$sessions = $conn->query("
    SELECT s.*, c.course_code, c.course_name
    FROM sessions s
    JOIN courses c ON s.course_id = c.course_id
    WHERE c.faculty_id = '$faculty_id'
    ORDER BY s.session_date DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Sessions</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <h1>Manage Sessions (<?php echo htmlspecialchars($name); ?>)</h1>
    <a href="faculty_page.php">Back to Dashboard</a> |
    <a href="logout.php">Logout</a>
    <br><br>

    <?php if (!empty($message)) : ?>
        <p class="error-message" style="background:#d4edda; color:#155724;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <div class="form-box active">
        <h2>Create a New Session</h2>
        <form method="post">
            <label>Select Course</label>
            <select name="course_id" required>
                <option value="">-- Select Course --</option>
                <?php while ($row = $courses->fetch_assoc()) : ?>
                    <option value="<?php echo $row['course_id']; ?>">
                        <?php echo htmlspecialchars($row['course_code'] . " - " . $row['course_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Session Date & Time</label>
            <input type="datetime-local" name="session_date" required>

            <button type="submit" name="create_session">Create Session</button>
        </form>
    </div>

    <br><br>

    <h2>Your Sessions</h2>

    <?php if ($sessions->num_rows > 0) : ?>
        <table>
            <tr>
                <th>Course</th>
                <th>Date & Time</th>
                <th>Code</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php while ($s = $sessions->fetch_assoc()) : ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($s['course_code'] . " - " . $s['course_name']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($s['session_date']); ?></td>
                    <td><?php echo htmlspecialchars($s['attendance_code']); ?></td>
                    <td class="<?php echo strtolower($s['status']); ?>">
                        <?php echo htmlspecialchars($s['status']); ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="session_id" value="<?php echo $s['session_id']; ?>">
                            <?php if ($s['status'] == 'Open') : ?>
                                <button type="submit" name="session_action" value="close" class="reject-btn">Close</button>
                            <?php else : ?>
                                <button type="submit" name="session_action" value="open" class="approve-btn">Open</button>
                            <?php endif; ?>
                        </form>

                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this session?');">
                            <input type="hidden" name="session_id" value="<?php echo $s['session_id']; ?>">
                            <button type="submit" name="session_action" value="delete" class="reject-btn">Delete</button>
                        </form>

                        <a href="faculty_session_details.php?session_id=<?php echo $s['session_id']; ?>">
                            View / Mark Attendance
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else : ?>
        <p>No sessions created yet.</p>
    <?php endif; ?>

</div>
</body>
</html>
