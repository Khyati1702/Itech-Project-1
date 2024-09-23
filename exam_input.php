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
    $exam = $_POST['exam']; 
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
<?php include 'navbar.php'; ?>


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

    <?php include 'footer.php'; ?>

</body>
</html>
