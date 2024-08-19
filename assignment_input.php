<?php
session_start();
require 'configure.php';

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

// Get the student ID from the request
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id == 0) {
    die('Invalid student ID');
}

// Fetch student details
$studentQuery = $config->query("SELECT Name FROM users WHERE UserID = $student_id AND Role = 'Stage1Students'");
$student = $studentQuery->fetch_assoc();

// Assessments for Stage 1 based on the provided PDF
$stage1Assessments = [
    "Interaction",
    "Text Analysis",
    "Text Production",
    "Investigation Task Part A: PPT presentation",
    "Investigation Task Part B: Reflective Writing in English"
];

// Fetch grades if already entered
$gradesQuery = $config->query("SELECT assessment_name, grade FROM student_assessment_grades WHERE student_id = $student_id");
$existingGrades = [];
while ($row = $gradesQuery->fetch_assoc()) {
    $existingGrades[$row['assessment_name']] = $row['grade'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($stage1Assessments as $assessment) {
        $grade = isset($_POST['grade_' . md5($assessment)]) ? $_POST['grade_' . md5($assessment)] : '';
        $config->query("REPLACE INTO student_assessment_grades (student_id, assessment_name, grade) VALUES ($student_id, '$assessment', '$grade')");
    }
    header("Location: assignment.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Grades for <?php echo htmlspecialchars($student['Name']); ?></title>
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="assignment_input.css">
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
        <h1>Input Grades for <?php echo htmlspecialchars($student['Name']); ?></h1>
        <form method="post">
            <table>
                <thead>
                    <tr>
                        <th>Assessment</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stage1Assessments as $assessment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assessment); ?></td>
                            <td>
                                <input type="text" name="grade_<?php echo md5($assessment); ?>" value="<?php echo isset($existingGrades[$assessment]) ? htmlspecialchars($existingGrades[$assessment]) : ''; ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit">Save Grades</button>
        </form>
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
</body>
</html>
