<?php
session_start();
require 'configure.php';

// Check if the user is logged in and is a Teacher or Admin
if (!isset($_SESSION['Username']) || ($_SESSION['Role'] != 'Teacher' && $_SESSION['Role'] != 'Admin')) {
    header('Location: LoginPage.php');
    exit();
}

// Fetch students and assessments based on the selected stage
$stage1Students = $config->query("SELECT UserID, Name FROM users WHERE Role='Stage1Students'");
$stage2Students = $config->query("SELECT UserID, Name FROM users WHERE Role='Stage2Students'");

$stage1Assessments = [
    "Interaction",
    "Text Analysis",
    "Text Production",
    "Investigation Task Part A",
    "Investigation Task Part B"
];

$stage2Assessments = [
    "Interaction",
    "Text Analysis",
    "Text Production",
    "Oral Presentation",
    "Response in Japanese",
    "Response in English"
];

/*
$stage1Exams = [
    "Midterm Exam",
    "Final Exam"
];

$stage2Exams = [
    "Midterm Exam",
    "Final Exam"
];
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Student Scores</title>
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="assignmentnew.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<main>
    <h1>Input Student Scores</h1>
    <div class="toggle-buttons">
        <button id="stage1Button" class="toggle-button active" onclick="showStage1()">Stage 1</button>
        <button id="stage2Button" class="toggle-button" onclick="showStage2()">Stage 2</button>
    </div>

    <!-- Stage 1 Container -->
    <div id="stage1Container" class="stage-container">
        <div class="toggle-buttons">
            <button id="stage1StudentsButton" class="toggle-button active" onclick="showStage1Students()">Stage 1 Students</button>
            <button id="stage1AssessmentsButton" class="toggle-button" onclick="showStage1Assessments()">Stage 1 Assessments</button>
            <!-- <button id="stage1ExamsButton" class="toggle-button" onclick="showStage1Exams()">Stage 1 Exams</button> -->
        </div>

        <!-- Stage 1 Students List -->
        <div id="stage1StudentsList" class="table-container">
            <h2>Stage 1: Students</h2>
            <table>
                <thead>
                    <tr>
                        <th>Students</th>
                        <th>Input Grades</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $stage1Students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['Name']); ?></td>
                            <td><a href="assignment_input.php?student_id=<?php echo $student['UserID']; ?>&stage=1&view=student">Input Grades</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Stage 1 Assessments List -->
        <div id="stage1AssessmentsList" class="table-container" style="display:none;">
            <h2>Stage 1: Assessments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Assessment Name</th>
                        <th>Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stage1Assessments as $assessment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assessment); ?></td>
                            <td>Assessment</td>
                            <td><a href="assignment_input.php?assessment_name=<?php echo urlencode($assessment); ?>&stage=1&view=assessment">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Stage 1 Exams List (Commented Out) -->
        <!--
        <div id="stage1ExamsList" class="table-container" style="display:none;">
            <h2>Stage 1: Exams</h2>
            <table>
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stage1Exams as $exam): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam); ?></td>
                            <td>Exam</td>
                            <td><a href="exam_input.php?exam_name=<?php echo urlencode($exam); ?>&stage=1&view=exam">Enter Scores</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        -->
    </div>

    <!-- Stage 2 Container -->
    <div id="stage2Container" class="stage-container" style="display:none;">
        <div class="toggle-buttons">
            <button id="stage2StudentsButton" class="toggle-button active" onclick="showStage2Students()">Stage 2 Students</button>
            <button id="stage2AssessmentsButton" class="toggle-button" onclick="showStage2Assessments()">Stage 2 Assessments</button>
            <!-- <button id="stage2ExamsButton" class="toggle-button" onclick="showStage2Exams()">Stage 2 Exams</button> -->
        </div>

        <!-- Stage 2 Students List -->
        <div id="stage2StudentsList" class="table-container">
            <h2>Stage 2: Students</h2>
            <table>
                <thead>
                    <tr>
                        <th>Students</th>
                        <th>Input Grades</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $stage2Students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['Name']); ?></td>
                            <td><a href="assignment_input.php?student_id=<?php echo $student['UserID']; ?>&stage=2&view=student">Input Grades</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Stage 2 Assessments List -->
        <div id="stage2AssessmentsList" class="table-container" style="display:none;">
            <h2>Stage 2: Assessments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Assessment Name</th>
                        <th>Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stage2Assessments as $assessment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assessment); ?></td>
                            <td>Assessment</td>
                            <td><a href="assignment_input.php?assessment_name=<?php echo urlencode($assessment); ?>&stage=2&view=assessment">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Stage 2 Exams List (Commented Out) -->
        <!--
        <div id="stage2ExamsList" class="table-container" style="display:none;">
            <h2>Stage 2: Exams</h2>
            <table>
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stage2Exams as $exam): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam); ?></td>
                            <td>Exam</td>
                            <td><a href="exam_input.php?exam_name=<?php echo urlencode($exam); ?>&stage=2&view=exam">Enter Scores</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        -->
    </div>
</main>

<?php include 'footer.php'; ?>


<script>
    function showStage1() {
        document.getElementById('stage1Container').style.display = 'block';
        document.getElementById('stage2Container').style.display = 'none';
        document.getElementById('stage1Button').classList.add('active');
        document.getElementById('stage2Button').classList.remove('active');
    }

    function showStage2() {
        document.getElementById('stage1Container').style.display = 'none';
        document.getElementById('stage2Container').style.display = 'block';
        document.getElementById('stage1Button').classList.remove('active');
        document.getElementById('stage2Button').classList.add('active');
    }

    function showStage1Students() {
        document.getElementById('stage1StudentsList').style.display = 'block';
        document.getElementById('stage1AssessmentsList').style.display = 'none';
        /* document.getElementById('stage1ExamsList').style.display = 'none'; */
        document.getElementById('stage1StudentsButton').classList.add('active');
        document.getElementById('stage1AssessmentsButton').classList.remove('active');
        /* document.getElementById('stage1ExamsButton').classList.remove('active'); */
    }

    function showStage1Assessments() {
        document.getElementById('stage1StudentsList').style.display = 'none';
        document.getElementById('stage1AssessmentsList').style.display = 'block';
        /* document.getElementById('stage1ExamsList').style.display = 'none'; */
        document.getElementById('stage1StudentsButton').classList.remove('active');
        document.getElementById('stage1AssessmentsButton').classList.add('active');
        /* document.getElementById('stage1ExamsButton').classList.remove('active'); */
    }

    /*
    function showStage1Exams() {
        document.getElementById('stage1StudentsList').style.display = 'none';
        document.getElementById('stage1AssessmentsList').style.display = 'none';
        document.getElementById('stage1ExamsList').style.display = 'block';
        document.getElementById('stage1StudentsButton').classList.remove('active');
        document.getElementById('stage1AssessmentsButton').classList.remove('active');
        document.getElementById('stage1ExamsButton').classList.add('active');
    }
    */

    function showStage2Students() {
        document.getElementById('stage2StudentsList').style.display = 'block';
        document.getElementById('stage2AssessmentsList').style.display = 'none';
        /* document.getElementById('stage2ExamsList').style.display = 'none'; */
        document.getElementById('stage2StudentsButton').classList.add('active');
        document.getElementById('stage2AssessmentsButton').classList.remove('active');
        /* document.getElementById('stage2ExamsButton').classList.remove('active'); */
    }

    function showStage2Assessments() {
        document.getElementById('stage2StudentsList').style.display = 'none';
        document.getElementById('stage2AssessmentsList').style.display = 'block';
        /* document.getElementById('stage2ExamsList').style.display = 'none'; */
        document.getElementById('stage2StudentsButton').classList.remove('active');
        document.getElementById('stage2AssessmentsButton').classList.add('active');
        /* document.getElementById('stage2ExamsButton').classList.remove('active'); */
    }

    /*
    function showStage2Exams() {
        document.getElementById('stage2StudentsList').style.display = 'none';
        document.getElementById('stage2AssessmentsList').style.display = 'none';
        document.getElementById('stage2ExamsList').style.display = 'block';
        document.getElementById('stage2StudentsButton').classList.remove('active');
        document.getElementById('stage2AssessmentsButton').classList.remove('active');
        document.getElementById('stage2ExamsButton').classList.add('active');
    }
    */

    // Initialize the page with Stage 1 Students visible
    showStage1();
</script>
</body>
</html>
