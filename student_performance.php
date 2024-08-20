<?php

session_start();
require 'configure.php';

if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

if (!isset($_GET['UserID'])) {
    header('Location: Profile.php');
    exit();
}

$UserID = $_GET['UserID'];
$loggedInUserRole = $_SESSION['Role'];

// Fetch student information
$query = $config->prepare("SELECT Name, Course, Role FROM users WHERE UserID = ?");
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$studentRole = $student['Role'];

// Determine the assessments to display based on the student's role
$assessments = ($studentRole == 'Stage1Students') ? [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Investigation_Task_Part_A", "Investigation_Task_Part_B"
] : [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Oral_Presentation", "Response_Japanese", "Response_English"
];

// Fetch grades, comments, and teacher notes from gradings table
$gradesQuery = $config->prepare("
    SELECT * FROM gradings 
    WHERE StudentID = ?");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

// Fetch exam scores and comments from exam_scores table
$examScoresQuery = $config->prepare("
    SELECT * FROM exam_scores 
    WHERE StudentID = ?");
$examScoresQuery->bind_param("i", $UserID);
$examScoresQuery->execute();
$examScoresResult = $examScoresQuery->get_result();

// Handle grade update for assessments
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_grade']) && $loggedInUserRole == 'Teacher') {
    $assessment = $_POST['assessment'];
    $newGrade = $_POST['new_grade'];
    $newComment = $_POST['new_comment'];

    $updateQuery = $config->prepare("
        UPDATE gradings 
        SET $assessment = ?, Comments_$assessment = ?, GradingTimestamp = NOW()
        WHERE StudentID = ? AND TeacherID = ?");
    $updateQuery->bind_param("dsii", $newGrade, $newComment, $UserID, $_SESSION['UserID']);

    if ($updateQuery->execute()) {
        echo "<p>Grade updated successfully.</p>";
    } else {
        echo "<p>Failed to update grade.</p>";
    }

    // Refresh to show updated data
    header("Location: student_performance.php?UserID=" . $UserID);
    exit();
}

// Handle exam score update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_exam']) && $loggedInUserRole == 'Teacher') {
    $examID = $_POST['exam_id'];
    $examType = $_POST['exam_type'];
    $newScore = $_POST['new_score'];
    $newComment = $_POST['new_comment'];

    $updateExamQuery = $config->prepare("
        UPDATE exam_scores 
        SET $examType = ?, Comments_$examType = ?, ScoreTimestamp = NOW()
        WHERE ExamID = ? AND StudentID = ? AND TeacherID = ?");
    $updateExamQuery->bind_param("dsiii", $newScore, $newComment, $examID, $UserID, $_SESSION['UserID']);

    if ($updateExamQuery->execute()) {
        echo "<p>Exam score updated successfully.</p>";
    } else {
        echo "<p>Failed to update exam score.</p>";
    }

    // Refresh to show updated data
    header("Location: student_performance.php?UserID=" . $UserID);
    exit();
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
        
        <!-- Download PDF Report Button -->
        <section>
            <a href="generate_report.php?UserID=<?php echo $UserID; ?>" class="btn btn-primary">Download PDF Report</a>
            <a href="generate_word_report.php?UserID=<?php echo $UserID; ?>" class="btn btn-primary">Download Word Report</a>

        </section>

        <!-- Assessment Grades Table -->
        <section>
            <h3>Grades and Comments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Assessment</th>
                        <th>Grade</th>
                        <th>Comment</th>
                        <th>Timestamp</th>
                        <?php if ($loggedInUserRole == 'Teacher'): ?>
                        <th>Update Grade</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assessments as $assessment): ?>
                        <?php if (!empty($grades[$assessment]) || !empty($grades['Comments_' . $assessment])): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', $assessment)); ?></td>
                                <td><?php echo htmlspecialchars($grades[$assessment]); ?></td>
                                <td><?php echo htmlspecialchars($grades['Comments_' . $assessment]); ?></td>
                                <td><?php echo htmlspecialchars($grades['GradingTimestamp']); ?></td>
                                <?php if ($loggedInUserRole == 'Teacher'): ?>
                                <td>
                                    <form method="POST" class="update-grade-form">
                                        <input type="hidden" name="assessment" value="<?php echo $assessment; ?>">
                                        <input type="number" name="new_grade" value="<?php echo $grades[$assessment]; ?>" required>
                                        <input type="text" name="new_comment" value="<?php echo $grades['Comments_' . $assessment]; ?>">
                                        <button type="submit" name="update_grade">Update</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Exam Scores Table -->
        <section>
            <h3>Exam Scores</h3>
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Score</th>
                        <th>Comment</th>
                        <th>Timestamp</th>
                        <?php if ($loggedInUserRole == 'Teacher'): ?>
                            <th>Update Score</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($exam = $examScoresResult->fetch_assoc()): ?>
                        <?php foreach (['Exam1', 'Exam2'] as $examType): ?>
                            <?php if (!empty($exam[$examType])): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($examType); ?></td>
                                    <td><?php echo htmlspecialchars($exam[$examType]); ?></td>
                                    <td><?php echo htmlspecialchars($exam['Comments_' . $examType]); ?></td>
                                    <td><?php echo htmlspecialchars($exam['ScoreTimestamp']); ?></td>
                                    <?php if ($loggedInUserRole == 'Teacher'): ?>
                                        <td>
                                            <form method="POST" class="update-grade-form"> <!-- same class for consistent styling -->
                                                <input type="hidden" name="exam_id" value="<?php echo $exam['ExamID']; ?>">
                                                <input type="hidden" name="exam_type" value="<?php echo $examType; ?>">
                                                <input type="number" name="new_score" value="<?php echo $exam[$examType]; ?>" required>
                                                <input type="text" name="new_comment" value="<?php echo $exam['Comments_' . $examType]; ?>">
                                                <button type="submit" name="update_exam">Update</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <?php if ($loggedInUserRole == 'Teacher'): ?>
            <section class="teacher-notes-section">
                <div class="teacher-notes-header">
                    Teacher Notes
                </div>
                <div class="teacher-notes-content">
                    <p><?php echo htmlspecialchars($grades['TeacherNote']); ?></p>
                </div>
            </section>
        <?php endif; ?>
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
