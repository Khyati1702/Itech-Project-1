<?php
session_start();
require 'configure.php';


//This page us for the assessment grade input for one assignment all students and all assignments one student.


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Checking the teacher role
if (!isset($_SESSION['Username']) || ($_SESSION['Role'] != 'Teacher')) {
    header('Location: LoginPage.php');
    exit();
}
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$stage = isset($_GET['stage']) ? $_GET['stage'] : null;
$assessment_name = isset($_GET['assessment_name']) ? $_GET['assessment_name'] : null;
$view = isset($_GET['view']) ? $_GET['view'] : null;
// Cheking teacher id here
if (!isset($_SESSION['UserID'])) {
    die('TeacherID is not set. Please log in again.');
}
$teacher_id = $_SESSION['UserID'];

// Assignments according ot stage 
$assessments = [];
if ($stage == 1) {
    $assessments = [
        "Interaction" => "Interaction",
        "Text Analysis" => "Text_Analysis",
        "Text Production" => "Text_Production",
        "Investigation Task Part A" => "Investigation_Task_Part_A",
        "Investigation Task Part B" => "Investigation_Task_Part_B"
    ];
    //Now the assignment for stage 2
} elseif ($stage == 2) {
    $assessments = [
        "Interaction" => "Interaction",
        "Text Analysis" => "Text_Analysis",
        "Text Production" => "Text_Production",
        "Oral Presentation" => "Oral_Presentation",
        "Response in Japanese" => "Response_Japanese",
        "Response in English" => "Response_English"
    ];
} else {
    die('Invalid stage provided.');
}

// students according ot stage 
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
        // Inserting grades
        $query_base = "INSERT INTO gradings (StudentID, TeacherID, GradingTimestamp";
        $query_values = " VALUES (?, ?, NOW()";
        $query_update = " ON DUPLICATE KEY UPDATE GradingTimestamp = NOW()";
        $params = [$student_id, $teacher_id];
        $types = "ii";

        foreach ($assessments as $displayName => $columnName) {
            $gradeKey = $columnName;
            $commentKey = $columnName . '_comment';

            $grade = isset($_POST[$gradeKey]) ? $_POST[$gradeKey] : null;
            $comment = isset($_POST[$commentKey]) ? $_POST[$commentKey] : null;

            // Confirming the input
            if ($grade !== null && $grade !== '') {
                $query_base .= ", `$columnName`";
                $query_values .= ", ?";
                $query_update .= ", `$columnName` = VALUES(`$columnName`)";
                $params[] = $grade;
                $types .= "d";
            }
            if ($comment !== null && $comment !== '') {
                $commentColumn = 'Comments_' . $columnName;
                $query_base .= ", `$commentColumn`";
                $query_values .= ", ?";
                $query_update .= ", `$commentColumn` = VALUES(`$commentColumn`)";
                $params[] = $comment;
                $types .= "s";
            }
        }

        // Adding teacher note 
        if (!empty($_POST['teacher_notes'])) {
            $query_base .= ", `TeacherNote`";
            $query_values .= ", ?";
            $query_update .= ", `TeacherNote` = VALUES(`TeacherNote`)";
            $params[] = $_POST['teacher_notes'];
            $types .= "s";
        }

        $query = $query_base . ")" . $query_values . ")" . $query_update;

        $stmt = $config->prepare($query);
        if (!$stmt) {
            die("Query preparation failed: " . $config->error);
        }

        // checking and providing confirmation.
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo "Grades and comments saved successfully!";
        } else {
            echo "Error saving grades and comments: " . $stmt->error;
        }

    } elseif ($view == 'assessment' && $assessment_name) {
        if (isset($assessments[$assessment_name])) {
            $assessment_column = $assessments[$assessment_name];
        } else {
            die('Invalid assessment name provided.');
        }

        // Saving grades for all students for one particular assessment.
        foreach ($_POST as $student_id => $data) {
            if (is_numeric($student_id)) {
                $grade = $data['grade'] ?? null;
                $comment = $data['comment'] ?? null;

       
                if ($grade === '') $grade = null;
                if ($comment === '') $comment = null;

                // inserting the grades and comments.
                if ($grade !== null || $comment !== null) {
                    $update_query = $config->prepare("
                        INSERT INTO gradings (StudentID, TeacherID, `$assessment_column`, `Comments_$assessment_column`, GradingTimestamp) 
                        VALUES (?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                            `$assessment_column` = VALUES(`$assessment_column`), 
                            `Comments_$assessment_column` = VALUES(`Comments_$assessment_column`), 
                            GradingTimestamp = NOW()");
                    $update_query->bind_param('iiss', $student_id, $teacher_id, $grade, $comment);
                    if (!$update_query->execute()) {
                        echo "Error saving grades for student ID $student_id: " . $update_query->error;
                    }
                }
            }
        }
        echo "Grades and comments saved successfully!";
    }
}

// Getting the studnet name to show on form. 
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
                <?php foreach ($assessments as $displayName => $columnName): ?>
                    <div class="assessment-input">
                        <label for="<?php echo $columnName; ?>" 
                               class="<?php echo ($stage == 1) ? 'stage1-label' : ''; ?>">
                            <?php echo htmlspecialchars($displayName); ?>
                        </label>
                        <div class="grade-comment">
                            <input type="number" 
                                   id="<?php echo $columnName; ?>" 
                                   name="<?php echo $columnName; ?>" 
                                   placeholder="Grade" 
                                   min="1" max="100">
                            <textarea id="<?php echo $columnName . '_comment'; ?>" 
                                      name="<?php echo $columnName . '_comment'; ?>" 
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
            <h1>Input Grades for <?php echo htmlspecialchars($assessment_name); ?></h1>
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
