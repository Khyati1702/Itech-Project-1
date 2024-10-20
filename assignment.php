<?php
session_start();
require 'configure.php';


//THis page is the Grading page, where toggle is shown for selecting between the stage 1 and stage 2 students.


if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}
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

    <!-- Stage 1 button for students-->
    <div id="stage1Container" class="stage-container">
        <div class="toggle-buttons">
            <button id="stage1StudentsButton" class="toggle-button active" onclick="showStage1Students()">Stage 1 Students</button>
            <button id="stage1AssessmentsButton" class="toggle-button" onclick="showStage1Assessments()">Stage 1 Assessments</button>
        </div>

        <!-- Stage 1 table -->
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

        <!-- Stage 1 assessment table  -->
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
                            <td><a href="assignment_input.php?assessment_name=<?php echo urlencode($assessment); ?>&stage=1&view=assessment">Input Grades</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stage 2 button for students -->
    <div id="stage2Container" class="stage-container" style="display:none;">
        <div class="toggle-buttons">
            <button id="stage2StudentsButton" class="toggle-button active" onclick="showStage2Students()">Stage 2 Students</button>
            <button id="stage2AssessmentsButton" class="toggle-button" onclick="showStage2Assessments()">Stage 2 Assessments</button>
        </div>

        <!-- Stage 2  student table -->
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

        <!-- Stage 2 assesment table -->
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
        document.getElementById('stage1StudentsButton').classList.add('active');
        document.getElementById('stage1AssessmentsButton').classList.remove('active');
    }

    function showStage1Assessments() {
        document.getElementById('stage1StudentsList').style.display = 'none';
        document.getElementById('stage1AssessmentsList').style.display = 'block';
        document.getElementById('stage1StudentsButton').classList.remove('active');
        document.getElementById('stage1AssessmentsButton').classList.add('active');
    }

    function showStage2Students() {
        document.getElementById('stage2StudentsList').style.display = 'block';
        document.getElementById('stage2AssessmentsList').style.display = 'none';
        document.getElementById('stage2StudentsButton').classList.add('active');
        document.getElementById('stage2AssessmentsButton').classList.remove('active');
    }

    function showStage2Assessments() {
        document.getElementById('stage2StudentsList').style.display = 'none';
        document.getElementById('stage2AssessmentsList').style.display = 'block';
        document.getElementById('stage2StudentsButton').classList.remove('active');
        document.getElementById('stage2AssessmentsButton').classList.add('active');
    }

    // Starting with stage 1 button clicked 
    showStage1();
</script>
</body>
</html>
