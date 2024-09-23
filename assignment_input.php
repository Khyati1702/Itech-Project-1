<?php
session_start();
require 'configure.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and is a Teacher or Admin
if (!isset($_SESSION['Username']) || ($_SESSION['Role'] != 'Teacher' && $_SESSION['Role'] != 'Admin')) {
    header('Location: LoginPage.php');
    exit();
}

// Get URL parameters
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$stage = isset($_GET['stage']) ? $_GET['stage'] : null;
$assessment_name = isset($_GET['assessment_name']) ? $_GET['assessment_name'] : null;
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

// Fetch students based on the stage
$students_query = $config->prepare("SELECT UserID, Name FROM users WHERE Role = ?");
$role = 'Stage' . $stage . 'Students';
$students_query->bind_param("s", $role);
$students_query->execute();
$students = $students_query->get_result();

if (!$students) {
    die('No students found for this stage.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($view == 'student' && $student_id) {
        // Base query to insert grades
        $query_base = "INSERT INTO gradings (StudentID, TeacherID, GradingTimestamp";
        $query_values = " VALUES (?, ?, NOW()";
        $query_update = " ON DUPLICATE KEY UPDATE GradingTimestamp = NOW()";
        $params = [$student_id, $teacher_id];
        $types = "ii"; // 'i' for integers (StudentID, TeacherID)

        // Loop through each assessment and process the input
        foreach ($assessments as $assessment) {
            $grade = isset($_POST[str_replace(' ', '_', $assessment)]) ? $_POST[str_replace(' ', '_', $assessment)] : null;
            $comment = isset($_POST[str_replace(' ', '_', $assessment) . '_comment']) ? $_POST[str_replace(' ', '_', $assessment) . '_comment'] : null;

            // Only process if the grade or comment is provided
            if ($grade !== null && $grade !== '') { // Ensure we don't insert empty values
                $query_base .= ", `$assessment`";
                $query_values .= ", ?";
                $query_update .= ", `$assessment` = VALUES(`$assessment`)";
                $params[] = $grade;
                $types .= "d"; // 'd' for decimal (grade)
            }

            if ($comment !== null && $comment !== '') {
                $query_base .= ", `Comments_$assessment`";
                $query_values .= ", ?";
                $query_update .= ", `Comments_$assessment` = VALUES(`Comments_$assessment`)";
                $params[] = $comment;
                $types .= "s"; // 's' for string (comment)
            }
        }

        // Add Teacher Notes if provided
        if (!empty($_POST['teacher_notes'])) {
            $query_base .= ", `TeacherNote`";
            $query_values .= ", ?";
            $query_update .= ", `TeacherNote` = VALUES(`TeacherNote`)";
            $params[] = $_POST['teacher_notes'];
            $types .= "s"; // 's' for string (teacher note)
        }

        // Combine the base query, values, and update clause
        $query = $query_base . ")" . $query_values . ")" . $query_update;

        // Prepare and execute the statement
        $stmt = $config->prepare($query);
        if (!$stmt) {
            die("Query preparation failed: " . $config->error);
        }

        // Bind the parameters and execute the query
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo "Grades and comments saved successfully!";
        } else {
            echo "Error saving grades and comments: " . $stmt->error;
        }

    } elseif ($view == 'assessment' && $assessment_name) {
        // Handle saving for assessment view (all students for one assessment)
        foreach ($_POST as $student_id => $data) {
            if (is_numeric($student_id)) {
                $grade = $data['grade'] ?? null;
                $comment = $data['comment'] ?? null;

                // Ensure that empty values are not sent to the database
                if ($grade === '') $grade = null;
                if ($comment === '') $comment = null;

                // Insert or update the grade and comment
                if ($grade !== null || $comment !== null) { // Only process if grade or comment is provided
                    $update_query = $config->prepare("
                        INSERT INTO gradings (StudentID, TeacherID, `$assessment_name`, `Comments_$assessment_name`, GradingTimestamp) 
                        VALUES (?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                            `$assessment_name` = VALUES(`$assessment_name`), 
                            `Comments_$assessment_name` = VALUES(`Comments_$assessment_name`), 
                            GradingTimestamp = NOW()");
                    $update_query->bind_param('iiss', $student_id, $teacher_id, $grade, $comment);
                    $update_query->execute();
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
<?php include 'navbar.php'; ?>
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
                <div class="assessment-input">
                    <label for="<?php echo str_replace(' ', '_', $assessment); ?>" 
                           class="<?php echo ($stage == 1) ? 'stage1-label' : ''; ?>">
                        <?php echo str_replace('_', ' ', htmlspecialchars($assessment)); ?>
                    </label>
                    <div class="grade-comment">
                        <input type="number" 
                               id="<?php echo str_replace(' ', '_', $assessment); ?>" 
                               name="<?php echo str_replace(' ', '_', $assessment); ?>" 
                               placeholder="Grade" 
                               min="1" max="100">
                        <textarea id="<?php echo str_replace(' ', '_', $assessment) . '_comment'; ?>" 
                                  name="<?php echo str_replace(' ', '_', $assessment) . '_comment'; ?>" 
                                  placeholder="Enter Comment"></textarea>
                    </div>
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
            <button type="submit" class="SaveGrade-button">Save Grades</button>
        </form>
        <?php elseif ($view == 'assessment' && $assessment_name): ?>
    <h1>Input Grades for <?php echo str_replace('_', ' ', htmlspecialchars($assessment_name)); ?></h1>
    <form method="post">
        <div class="assessment-view">
            <?php while ($student = $students->fetch_assoc()): ?>
                <div class="student-assessment">
                    <div class="student-name">
                        <?php echo htmlspecialchars($student['Name']); ?>
                    </div>
                    <div class="grade-comment">
                        <input type="number" name="<?php echo $student['UserID']; ?>[grade]" placeholder="Grade">
                        <textarea name="<?php echo $student['UserID']; ?>[comment]" placeholder="Enter Comment"></textarea>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <button type="submit" class="SaveGrade-button">Save Grades</button>
    </form>
<?php endif; ?>

</main>

<?php include 'footer.php'; ?>

</body>
</html>
