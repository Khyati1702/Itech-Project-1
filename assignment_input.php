<?php
session_start();
require 'configure.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

// Get URL parameters
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$stage = isset($_GET['stage']) ? $_GET['stage'] : null;
$view = isset($_GET['view']) ? $_GET['view'] : null;

// Ensure TeacherID is available
if (!isset($_SESSION['UserID'])) {
    die('TeacherID is not set. Please log in again.');
}
$teacher_id = $_SESSION['UserID'];

// Fetch assessments based on the stage
$assessments = [];
if ($stage == 1) {
    $assessments = [
        "Interaction", "Text_Analysis", "Text_Production", 
        "Investigation_Task_Part_A", "Investigation_Task_Part_B"
    ];
} elseif ($stage == 2) {
    $assessments = [
        "Interaction", "Text_Analysis", "Text_Production", 
        "Oral_Presentation", "Response_Japanese", "Response_English"
    ];
} else {
    die('Invalid stage provided.');
}

// Handle form submission for inputting grades and comments
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($view == 'student' && $student_id) {
        // Handle saving for student view (already working)
        // Fetch current grades and comments to avoid overwriting existing data
        $existing_grades_query = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? AND TeacherID = ?");
        $existing_grades_query->bind_param("ii", $student_id, $teacher_id);
        $existing_grades_query->execute();
        $existing_grades = $existing_grades_query->get_result()->fetch_assoc();

        $query_base = "INSERT INTO gradings (StudentID, TeacherID, GradingTimestamp";
        $query_values = " VALUES (?, ?, NOW()";
        $query_update = " ON DUPLICATE KEY UPDATE GradingTimestamp = NOW()";
        $params = [$student_id, $teacher_id];
        $types = "ii";

        foreach ($assessments as $assessment) {
            $grade = isset($_POST[str_replace(' ', '_', $assessment)]) ? $_POST[str_replace(' ', '_', $assessment)] : null;
            $comment = isset($_POST[str_replace(' ', '_', $assessment) . '_comment']) ? $_POST[str_replace(' ', '_', $assessment) . '_comment'] : null;

            // Preserve existing grades and comments if no new input is provided
            if ($grade === null || $grade === '') {
                $grade = $existing_grades[$assessment] ?? null;
            }
            if ($comment === null || $comment === '') {
                $comment = $existing_grades['Comments_' . $assessment] ?? null;
            }

            if ($grade !== null || $comment !== null) {
                $query_base .= ", `$assessment`, `Comments_$assessment`";
                $query_values .= ", ?, ?";
                $query_update .= ", `$assessment` = VALUES(`$assessment`), `Comments_$assessment` = VALUES(`Comments_$assessment`)";
                $params[] = $grade;
                $params[] = $comment;
                $types .= "ds"; // d for decimal (grade), s for string (comment)
            }
        }

        // Include Teacher Notes if provided
        if (!empty($_POST['teacher_notes'])) {
            $query_base .= ", `TeacherNote`";
            $query_values .= ", ?";
            $query_update .= ", `TeacherNote` = VALUES(`TeacherNote`)";
            $params[] = $_POST['teacher_notes'];
            $types .= "s"; // s for string (teacher note)
        }

        $query = $query_base . ")" . $query_values . ")" . $query_update;
        $stmt = $config->prepare($query);

        if (!$stmt) {
            die("Query preparation failed: " . $config->error);
        }

        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo "Grades and comments saved successfully!";
        } else {
            echo "Error saving grades and comments: " . $stmt->error;
        }
    } elseif ($view == 'assessment' && $stage) {
        // Handle saving for assessment view
        foreach ($_POST as $student_id => $data) {
            if (is_numeric($student_id)) {
                foreach ($data as $assessment_name => $values) {
                    $grade = $values['grade'] ?? null;
                    $comment = $values['comment'] ?? null;
                    
                    // Preserve existing data if no new input is provided
                    $existing_grades_query = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? AND TeacherID = ?");
                    $existing_grades_query->bind_param("ii", $student_id, $teacher_id);
                    $existing_grades_query->execute();
                    $existing_grades = $existing_grades_query->get_result()->fetch_assoc();

                    if ($grade === null || $grade === '') {
                        $grade = $existing_grades[$assessment_name] ?? null;
                    }
                    if ($comment === null || $comment === '') {
                        $comment = $existing_grades['Comments_' . $assessment_name] ?? null;
                    }

                    if ($grade !== null || $comment !== null) {
                        $update_query = $config->prepare("
                            INSERT INTO gradings (StudentID, TeacherID, `$assessment_name`, `Comments_$assessment_name`, GradingTimestamp) 
                            VALUES (?, ?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE 
                                `$assessment_name` = VALUES(`$assessment_name`), 
                                `Comments_$assessment_name` = VALUES(`Comments_$assessment_name`), 
                                GradingTimestamp = NOW()");
                        if (!$update_query) {
                            die("Update query preparation failed: " . $config->error);
                        }
                        $update_query->bind_param('iiss', $student_id, $teacher_id, $grade, $comment);
                        $update_query->execute();
                    }
                }
            }
        }
        echo "Grades and comments saved successfully!";
    }
}

// Fetch student name for the form
if ($view == 'student' && $student_id) {
    $student_query = $config->prepare("SELECT Name FROM users WHERE UserID = ?");
    $student_query->bind_param("i", $student_id);
    $student_query->execute();
    $student_name = $student_query->get_result()->fetch_assoc()['Name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Grades</title>
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

<script>
    function toggleMenu() {
        const nav = document.querySelector('.main-nav');
        nav.classList.toggle('active');
        console.log('Menu toggled.');
    }
</script>

    <main>
        <?php if ($view == 'student' && $student_id): ?>
            <h1>Input Grades for <?php echo htmlspecialchars($student_name); ?></h1>
            <form method="post">
                <?php foreach ($assessments as $assessment): ?>
                    <div>
                        <label for="<?php echo str_replace(' ', '_', $assessment); ?>"><?php echo htmlspecialchars($assessment); ?></label>
                        <input type="text" id="<?php echo str_replace(' ', '_', $assessment); ?>" name="<?php echo str_replace(' ', '_', $assessment); ?>" placeholder="Enter Grade">
                        <label for="<?php echo str_replace(' ', '_', $assessment) . '_comment'; ?>">Comment:</label>
                        <input type="text" id="<?php echo str_replace(' ', '_', $assessment) . '_comment'; ?>" name="<?php echo str_replace(' ', '_', $assessment) . '_comment'; ?>" placeholder="Enter Comment">
                    </div>
                <?php endforeach; ?>
                <div class="teacher-notes-section">
                    <div class="teacher-notes-header">
                        Teacher Notes
                    </div>
                    <div class="teacher-notes-content">
                        <textarea name="teacher_notes" id="teacher_notes" rows="4" placeholder="Enter your notes here..."></textarea>
                    </div>
                </div>
                <button type="submit">Save Grades</button>
            </form>
        <?php elseif ($view == 'assessment' && $stage): ?>
            <h1>Input Grades for Stage <?php echo htmlspecialchars($stage); ?></h1>
            <form method="post">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Grade</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $students_query = $config->prepare("SELECT UserID, Name FROM users WHERE Role = ?");
                        $role = 'Stage' . $stage . 'Students';
                        $students_query->bind_param("s", $role);
                        $students_query->execute();
                        $students = $students_query->get_result();
                        while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['Name']); ?></td>
                                <td><input type="text" name="<?php echo $student['UserID']; ?>[<?php echo $assessments[0]; ?>][grade]" placeholder="Enter Grade"></td>
                                <td><input type="text" name="<?php echo $student['UserID']; ?>[<?php echo $assessments[0]; ?>][comment]" placeholder="Enter Comment"></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="submit">Save Grades</button>
            </form>
        <?php else: ?>
            <p>No valid view selected. Please select a valid student or assessment to view.</p>
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
</body>
</html>
