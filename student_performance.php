<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php'); // Redirect to the login page if the user is not logged in
    exit();
}

if (!isset($_GET['UserID'])) {
    header('Location: Profile.php'); // Redirect to the profile page if no user ID is provided
    exit();
}

$UserID = $_GET['UserID'];
$loggedInUserRole = $_SESSION['Role']; // Get the role of the logged-in user

// Fetch student information
$query = $config->prepare("SELECT Name, Course FROM users WHERE UserID = ?");
if (!$query) {
    die('Prepare failed: ' . $config->error);
}
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();
if (!$result) {
    die('Execute failed: ' . $query->error);
}
$student = $result->fetch_assoc();
if (!$student) {
    die('Fetch failed: ' . $query->error);
}

// Fetch attendance records
$attendanceQuery = $config->prepare("SELECT Date, Status, c.Name as Course FROM attendance a JOIN courses c ON a.CourseID = c.CourseID WHERE a.StudentID = ?");
if (!$attendanceQuery) {
    die('Prepare failed: ' . $config->error);
}
$attendanceQuery->bind_param("i", $UserID);
$attendanceQuery->execute();
$attendanceResult = $attendanceQuery->get_result();
if (!$attendanceResult) {
    die('Execute failed: ' . $attendanceQuery->error);
}

// Fetch grades
$gradesQuery = $config->prepare("SELECT g.GradingID, g.Grade, g.Comments, g.GradingTimestamp, c.Name as Course FROM gradings g JOIN submissions s ON g.SubmissionID = s.SubmissionID JOIN assignments a ON s.AssignmentID = a.AssignmentID JOIN courses c ON a.CourseID = c.CourseID WHERE s.StudentID = ?");
if (!$gradesQuery) {
    die('Prepare failed: ' . $config->error);
}
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
if (!$gradesResult) {
    die('Execute failed: ' . $gradesQuery->error);
}

// Fetch exam scores
$examScoresQuery = $config->prepare("SELECT es.ExamID, es.Score, es.Comments, es.LastUpdated, c.Name as Course FROM exam_scores es JOIN courses c ON es.CourseID = c.CourseID WHERE es.StudentID = ?");
if (!$examScoresQuery) {
    die('Prepare failed: ' . $config->error);
}
$examScoresQuery->bind_param("i", $UserID);
$examScoresQuery->execute();
$examScoresResult = $examScoresQuery->get_result();
if (!$examScoresResult) {
    die('Execute failed: ' . $examScoresQuery->error);
}

// Handle the exam score update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_exam_score']) && $loggedInUserRole == 'Teacher') {
    $scoreID = $_POST['score_id'];
    $newScore = $_POST['new_score'];

    // Update the score and timestamp in the database
    $updateQuery = $config->prepare("UPDATE exam_scores SET Score = ?, LastUpdated = NOW() WHERE ExamID = ?");
    if (!$updateQuery) {
        die('Prepare failed: ' . $config->error);
    }
    $updateQuery->bind_param("di", $newScore, $scoreID);
    if ($updateQuery->execute()) {
        echo "<p>Exam score updated successfully.</p>";
    } else {
        echo "<p>Failed to update exam score.</p>";
    }
}

// Handle the grade update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_grade']) && $loggedInUserRole == 'Teacher') {
    $gradingID = $_POST['grading_id'];
    $newGrade = $_POST['new_grade'];

    // Update the grade and timestamp in the database
    $updateGradeQuery = $config->prepare("UPDATE gradings SET Grade = ?, GradingTimestamp = NOW() WHERE GradingID = ?");
    if (!$updateGradeQuery) {
        die('Prepare failed: ' . $config->error);
    }
    $updateGradeQuery->bind_param("di", $newGrade, $gradingID);
    if ($updateGradeQuery->execute()) {
        echo "<p>Grade updated successfully.</p>";
    } else {
        echo "<p>Failed to update grade.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Performance</title>
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="student_performance.css">
</head>
<body>
    <header class="main-header">
        <div class="logo-container">
            <img class="header-title" src="Images/REAL_SACE.png" alt="SACE Portal Logo">
            <span class="header-title">SACE Portal</span>
        </div>
        <div class="nav-container">
            <span class="menu-toggle" onclick="toggleMenu()">☰</span>
            <nav class="main-nav">
                <a href="Mainpage.php">Home</a>
                <a href="assignment.php">Grading</a>
                <a href="Profile.php">Students</a>
                <a href="#">Contact</a>
                <a href="#">Help</a>
            </nav>
            <div class="search-container">
                <input type="search" placeholder="Search">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-button">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main>
        <h1>Student Performance</h1>
        <h2><?php echo htmlspecialchars($student['Name']); ?> - <?php echo htmlspecialchars($student['Course']); ?></h2>
        
        <section>
            <h3>Attendance</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Course</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $attendanceResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Date']); ?></td>
                        <td><?php echo htmlspecialchars($row['Status']); ?></td>
                        <td><?php echo htmlspecialchars($row['Course']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
        
        <section>
            <h3>Grades</h3>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Grade</th>
                        <th>Comments</th>
                        <th>Timestamp</th>
                        <?php if ($loggedInUserRole == 'Teacher'): ?>
                        <th>Update Grade</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $gradesResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Course']); ?></td>
                        <td><?php echo htmlspecialchars($row['Grade']); ?></td>
                        <td><?php echo htmlspecialchars($row['Comments']); ?></td>
                        <td><?php echo htmlspecialchars($row['GradingTimestamp']); ?></td>
                        <?php if ($loggedInUserRole == 'Teacher'): ?>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="grading_id" value="<?php echo $row['GradingID']; ?>">
                                <input type="number" name="new_grade" value="<?php echo $row['Grade']; ?>" required>
                                <button type="submit" name="update_grade">Update Grade</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3>Exam Scores</h3>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Score</th>
                        <th>Comments</th>
                        <th>Last Updated</th>
                        <?php if ($loggedInUserRole == 'Teacher'): ?>
                        <th>Update Score</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $examScoresResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Course']); ?></td>
                        <td><?php echo htmlspecialchars($row['Score']); ?></td>
                        <td><?php echo htmlspecialchars($row['Comments']); ?></td>
                        <td><?php echo htmlspecialchars($row['LastUpdated']); ?></td>
                        <?php if ($loggedInUserRole == 'Teacher'): ?>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="score_id" value="<?php echo $row['ExamID']; ?>">
                                <input type="number" name="new_score" value="<?php echo $row['Score']; ?>" required>
                                <button type="submit" name="update_exam_score">Update Score</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <div class="quick-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Services</a></li>
                    <li><a href="#">Student Info</a></li>
                    <li><a href="#">Contacts</a></li>
                    <li><a href="#">Help</a></li>
                </ul>
            </div>
            <div class="contact-us">
                <h3>Contact Us</h3>
                <ul>
                    <li><a href="#">Instagram</a></li>
                    <li><a href="#">Facebook</a></li>
                    <li><a href="#">YouTube</a></li>
                </ul>
            </div>
            <div class="address">
                <h3>Address</h3>
                <p>Level 5/118 King William St<br>Adelaide, SA<br>Phone: (08) 5555 5555</p>
            </div>
        </div>
        <div class="footer-bottom">
            <img src="Images/REAL_SACE.png" alt="SACE Portal Logo">
            <p>&copy; SACE Student Portal</p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const nav = document.querySelector('.main-nav');
            nav.classList.toggle('active');
            console.log('Menu toggled.');
        }
    </script>
</body>
</html>
