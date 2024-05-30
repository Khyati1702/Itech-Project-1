<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: experiment2.php');
    exit();
}

// Fetch assignments and exams
$assignments = $config->query("SELECT AssignmentID, Title, Description, DueDate FROM assignments");
$exams = $config->query("SELECT CourseID, Name FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments and Exams</title>
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="assignment.css">
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
                <a href="assignment.php">Grading</a>
                <a href="Profile.php">Students</a>
                <a href="#">Contact</a>
                <a href="#">Help</a>
            <div class="search-container">
                <input type="search" placeholder="Search">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-button">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main>
        <h1>Assignments and Exams</h1>
        <div class="card">
            <div class="tabs">
                <button class="tab-button active" onclick="showTab('assignments')">Assignments</button>
                <button class="tab-button" onclick="showTab('exams')">Exams</button>
            </div>
            <div id="assignments" class="tab-content active">
                <?php while ($assignment = $assignments->fetch_assoc()): ?>
                <div class="assignment-details">
                    <h2><?php echo htmlspecialchars($assignment['Title']); ?></h2>
                    <p><?php echo htmlspecialchars($assignment['Description']); ?></p>
                    <p><strong>Due Date:</strong> <?php echo htmlspecialchars($assignment['DueDate']); ?></p>
                    <a href="assignment_input.php?AssignmentID=<?php echo $assignment['AssignmentID']; ?>" class="input-link">Input Score</a>
                </div>
                <?php endwhile; ?>
            </div>
            <div id="exams" class="tab-content">
                <?php while ($exam = $exams->fetch_assoc()): ?>
                <div class="exam-details">
                    <h2><?php echo htmlspecialchars($exam['Name']); ?></h2>
                    <a href="exam_input.php?CourseID=<?php echo $exam['CourseID']; ?>" class="input-link">Input Score</a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
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

        function showTab(tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');

            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            document.querySelector(`.tab-button[onclick="showTab('${tabId}')"]`).classList.add('active');
        }
    </script>
</body>
</html>
