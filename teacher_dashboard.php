<?php
session_start();
require 'configure.php';

// Ensure only authorized users can view the dashboard
if (!isset($_SESSION['Username']) || ($_SESSION['Role'] != 'Teacher' && $_SESSION['Role'] != 'Admin')) {
    header('Location: LoginPage.php');
    exit();
}

// Fetch total number of students
$studentCountQuery = $config->prepare("SELECT COUNT(DISTINCT StudentID) AS total_students FROM gradings");
$studentCountQuery->execute();
$studentCountResult = $studentCountQuery->get_result();
$studentCount = $studentCountResult->fetch_assoc()['total_students'];

// Fetch number of grade inputs
$inputCountQuery = $config->prepare("SELECT COUNT(*) AS total_inputs FROM gradings WHERE Interaction IS NOT NULL OR Text_Analysis IS NOT NULL OR Text_Production IS NOT NULL");
$inputCountQuery->execute();
$inputCountResult = $inputCountQuery->get_result();
$inputCount = $inputCountResult->fetch_assoc()['total_inputs'];

// Fetch average, max, and min grades
$gradesQuery = $config->prepare("
    SELECT 
        AVG(COALESCE(Interaction, 0) + COALESCE(Text_Analysis, 0) + COALESCE(Text_Production, 0) + COALESCE(Investigation_Task_Part_A, 0) + COALESCE(Investigation_Task_Part_B, 0)) AS avg_grade,
        MAX(COALESCE(Interaction, 0) + COALESCE(Text_Analysis, 0) + COALESCE(Text_Production, 0) + COALESCE(Investigation_Task_Part_A, 0) + COALESCE(Investigation_Task_Part_B, 0)) AS max_grade,
        MIN(COALESCE(Interaction, 0) + COALESCE(Text_Analysis, 0) + COALESCE(Text_Production, 0) + COALESCE(Investigation_Task_Part_A, 0) + COALESCE(Investigation_Task_Part_B, 0)) AS min_grade
    FROM gradings");
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$gradesData = $gradesResult->fetch_assoc();
$avgGrade = $gradesData['avg_grade'];
$maxGrade = $gradesData['max_grade'];
$minGrade = $gradesData['min_grade'];

// Fetch students list and their most recent grades
$studentsQuery = $config->prepare("
    SELECT u.Name, u.Course, 
        g.Interaction, g.Text_Analysis, g.Text_Production, 
        g.Investigation_Task_Part_A, g.Investigation_Task_Part_B, g.GradingTimestamp
    FROM gradings g
    JOIN users u ON g.StudentID = u.UserID
    WHERE u.Role IN ('Stage1Students', 'Stage2Students') 
      AND g.GradingTimestamp = (
        SELECT MAX(sub_g.GradingTimestamp) 
        FROM gradings sub_g 
        WHERE sub_g.StudentID = g.StudentID
    )
    ORDER BY u.Name ASC");
$studentsQuery->execute();
$studentsResult = $studentsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Student Grades Statistics</h1>
    
    <section>
        <h2>Overall Statistics</h2>
        <table>
            <tr>
                <th>Total Students</th>
                <td><?php echo $studentCount; ?></td>
            </tr>
            <tr>
                <th>Total Inputs</th>
                <td><?php echo $inputCount; ?></td>
            </tr>
            <tr>
                <th>Average Grade</th>
                <td><?php echo number_format($avgGrade, 2); ?></td>
            </tr>
            <tr>
                <th>Maximum Grade</th>
                <td><?php echo number_format($maxGrade, 2); ?></td>
            </tr>
            <tr>
                <th>Minimum Grade</th>
                <td><?php echo number_format($minGrade, 2); ?></td>
            </tr>
        </table>
    </section>

    <section>
        <h2>Students List with Latest Grades</h2>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Interaction</th>
                    <th>Text Analysis</th>
                    <th>Text Production</th>
                    <th>Investigation Task Part A</th>
                    <th>Investigation Task Part B</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $studentsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Course'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Interaction'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Text_Analysis'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Text_Production'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Investigation_Task_Part_A'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Investigation_Task_Part_B'] ?? 'N/A'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
