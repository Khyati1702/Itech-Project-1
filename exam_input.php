<?php
session_start();
require 'configure.php';

if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

$stage = isset($_GET['stage']) ? $_GET['stage'] : null;
$students = ($stage == 1) ? $config->query("SELECT UserID, Name FROM users WHERE Role='Stage1Students'") : $config->query("SELECT UserID, Name FROM users WHERE Role='Stage2Students'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentID = $_POST['studentID'];
    $exam = $_POST['exam']; // Exam1, Exam2, etc.
    $score = $_POST['score'];
    $comments = $_POST['comments'];

    // Validate inputs
    if (!empty($studentID) && !empty($exam) && !empty($score) && is_numeric($score)) {
        $query = $config->prepare("INSERT INTO exam_scores (StudentID, TeacherID, $exam, Comments_$exam) VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE $exam = VALUES($exam), Comments_$exam = VALUES(Comments_$exam), ScoreTimestamp = NOW()");
        
        if (!$query) {
            die('Prepare failed: ' . $config->error);
        }
        
        $query->bind_param("iids", $studentID, $_SESSION['UserID'], $score, $comments);
        
        if (!$query->execute()) {
            die('Execute failed: ' . $query->error);
        }
        echo "Exam score added successfully.";
    } else {
        echo 'Validation failed: Missing or incorrect input.';
    }
}

// Fetch courses (if needed for selection)
$courses = $config->query("SELECT CourseID, Name FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Exam Score</title>
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="exam_input.css">
</head>
<body>
    <header class="main-header">
        <div class="logo-container">
            <img class="header-title" src="Images/Real_logo.png" alt="SACE Portal Logo">
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
        <h1>Input Exam Score</h1>
        <form action="exam_input.php?stage=<?php echo $stage; ?>" method="post">
            <div>
                <label for="studentID">Student:</label>
                <select name="studentID" id="studentID">
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo $student['UserID']; ?>"><?php echo $student['Name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label for="exam">Exam:</label>
                <select name="exam" id="exam">
                    <option value="Exam1">Exam 1</option>
                    <option value="Exam2">Exam 2</option>
                    <!-- Add more exams as needed -->
                </select>
            </div>
            <div>
                <label for="score">Score:</label>
                <input type="number" step="0.01" name="score" id="score">
            </div>
            <div>
                <label for="comments">Comments:</label>
                <textarea name="comments" id="comments"></textarea>
            </div>
            <button type="submit">Submit</button>
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
