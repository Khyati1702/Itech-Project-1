<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username'])) {
    header('Location: experiment2.php');
    exit();
}

if (!isset($_GET['UserID'])) {
    header('Location: Profile.php');
    exit();
}

$UserID = $_GET['UserID'];

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
$gradesQuery = $config->prepare("SELECT g.Grade, g.Comments, g.GradingTimestamp, c.Name as Course FROM gradings g JOIN submissions s ON g.SubmissionID = s.SubmissionID JOIN assignments a ON s.AssignmentID = a.AssignmentID JOIN courses c ON a.CourseID = c.CourseID WHERE s.StudentID = ?");
if (!$gradesQuery) {
    die('Prepare failed: ' . $config->error);
}
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
if (!$gradesResult) {
    die('Execute failed: ' . $gradesQuery->error);
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
            <img class="header-title" src="Eximages/REAL_SACE.png" alt="SACE Portal Logo">
            <span class="header-title">SACE Portal</span>
        </div>
        <div class="nav-container">
            <span class="menu-toggle" onclick="toggleMenu()">☰</span>
            <nav class="main-nav">
                <a href="Mainpage.php">Home</a>
                <a href="#">Services</a>
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
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $gradesResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Course']); ?></td>
                        <td><?php echo htmlspecialchars($row['Grade']); ?></td>
                        <td><?php echo htmlspecialchars($row['Comments']); ?></td>
                        <td><?php echo htmlspecialchars($row['GradingTimestamp']); ?></td>
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
            <img src="Eximages/REAL_SACE.png" alt="SACE Portal Logo">
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
