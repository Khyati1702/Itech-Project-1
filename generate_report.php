<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Set options for Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

session_start();
require 'configure.php';

if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

if (!isset($_GET['UserID'])) {
    header('Location: Profile.php');
    exit();
}

$UserID = $_GET['UserID']; // Fetch the UserID from GET request
$loggedInUserRole = $_SESSION['Role'];

// Fetch student information
$query = $config->prepare("SELECT Name, Course, Role FROM users WHERE UserID = ?");
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$studentName = $student['Name'];
$studentCourse = $student['Course'];
$studentRole = $student['Role'];

// Fetch teacher name
$teacherQuery = $config->prepare("SELECT Name FROM users WHERE UserID = ?");
$teacherQuery->bind_param("i", $_SESSION['UserID']);
$teacherQuery->execute();
$teacherResult = $teacherQuery->get_result();
$teacher = $teacherResult->fetch_assoc();
$teacherName = $teacher['Name'];

// Fetch grades, comments, and teacher notes from gradings table
$gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? ORDER BY GradingTimestamp DESC LIMIT 1");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

if (!$grades) {
    echo "No grades available for this student.";
    exit();
}

$teacherNote = $grades['TeacherNote'] ?? 'No notes available'; // Handle null values for TeacherNote

// Calculate Total Grade for the Student
$totalGradeQuery = "
    SELECT 
        StudentID,
        (COALESCE(Interaction, 0) + COALESCE(Text_Analysis, 0) + COALESCE(Text_Production, 0) + 
         COALESCE(Investigation_Task_Part_A, 0) + COALESCE(Investigation_Task_Part_B, 0) + 
         COALESCE(Oral_Presentation, 0) + COALESCE(Response_Japanese, 0) + COALESCE(Response_English, 0)) AS TotalGrade
    FROM gradings
    WHERE StudentID = ?"; // Using prepared statement for safety

$totalGradeStmt = $config->prepare($totalGradeQuery);
$totalGradeStmt->bind_param("i", $UserID);
$totalGradeStmt->execute();
$totalGradeResult = $totalGradeStmt->get_result();
$totalGradeData = $totalGradeResult->fetch_assoc();
$totalGrade = $totalGradeData['TotalGrade'] ?? 'N/A'; // Ensure we handle null TotalGrade

// HTML formatting for Stage 1 or Stage 2 students
if ($studentRole == 'Stage1Students') {
    // For Stage 1 report
    $html = '
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        table, th, td { border: 2px solid black; border-collapse: collapse; padding: 10px; }
        th, td { text-align: center; }
        h1, h2 { text-align: center; }
        .header { font-size: 12px; text-align: left; }
        .report-title { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .grade-box { border: 2px solid black; width: 50%; margin: 20px auto; padding: 20px; text-align: center; font-size: 18px; }
        .teacher-comment-box { padding-left: 10px; width: 100%; height: 100px; border: 1px solid black; }
    </style>
    <h2>SACE Stage 1</h2>
    <h1>Student Report</h1>
    <table style="width: 100%;">
        <tr><td class="header"><strong>Student:</strong> ' . htmlspecialchars($studentName) . '</td></tr>
        <tr><td class="header"><strong>Course:</strong> ' . htmlspecialchars($studentCourse) . '</td></tr>
        <tr><td class="header"><strong>Teacher:</strong> ' . htmlspecialchars($teacherName) . '</td></tr>
    </table>

    <table style="width: 100%;">
        <thead>
            <tr>
                <th>Summative Assessment Task</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Interaction</td><td>' . htmlspecialchars($grades['Interaction'] ?? 'N/A') . '</td></tr>
            <tr><td>Text Analysis</td><td>' . htmlspecialchars($grades['Text_Analysis'] ?? 'N/A') . '</td></tr>
            <tr><td>Text Production</td><td>' . htmlspecialchars($grades['Text_Production'] ?? 'N/A') . '</td></tr>
            <tr><td>Investigation Task Part A: PPT presentation</td><td>' . htmlspecialchars($grades['Investigation_Task_Part_A'] ?? 'N/A') . '</td></tr>
            <tr><td>Investigation Task Part B: Reflective Writing in English</td><td>' . htmlspecialchars($grades['Investigation_Task_Part_B'] ?? 'N/A') . '</td></tr>
        </tbody>
    </table>

    <div class="grade-box"><strong>Total Grade:</strong> ' . htmlspecialchars($totalGrade) . '</div>
    <h3>TEACHER NOTES:</h3>
    <div class="teacher-comment-box">' . htmlspecialchars($teacherNote) . '</div>';
    
} else if ($studentRole == 'Stage2Students') {
    // For Stage 2 report
    $html = '
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        table, th, td { border: 2px solid black; border-collapse: collapse; padding: 10px; }
        th, td { text-align: center; }
        h1, h2 { text-align: center; }
        .header { font-size: 12px; text-align: left; }
        .report-title { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .grade-box { border: 2px solid black; width: 50%; margin: 20px auto; padding: 20px; text-align: center; font-size: 18px; }
        .teacher-comment-box { padding-left: 10px; width: 100%; height: 100px; border: 1px solid black; }
    </style>
    <h2>SACE Stage 2</h2>
    <h1>Student Report</h1>
    <table style="width: 100%;">
        <tr>    <td class="header"><strong>Student:</strong> ' . htmlspecialchars($studentName) . '</td></tr>
           <tr> <td class="header"><strong>Course:</strong> ' . htmlspecialchars($studentCourse) . '</td></tr>
         <tr>   <td class="header"><strong>Teacher:</strong> ' . htmlspecialchars($teacherName) . '</td></tr>
    </table>

    <table style="width: 100%;">
        <thead>
            <tr>
                <th></th>
                <th>Summative Assessment Task</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <tr><td rowspan="3">Folio</td><td>Interaction</td><td>' . htmlspecialchars($grades['Interaction'] ?? 'N/A') . '</td></tr>
            <tr><td>Text Analysis</td><td>' . htmlspecialchars($grades['Text_Analysis'] ?? 'N/A') . '</td></tr>
            <tr><td>Text Production</td><td>' . htmlspecialchars($grades['Text_Production'] ?? 'N/A') . '</td></tr>
        </tbody>
        <tbody>
            <tr><td rowspan="3">In-Depth Study</td><td>Oral Presentation</td><td>' . htmlspecialchars($grades['Oral_Presentation'] ?? 'N/A') . '</td></tr>
            <tr><td>Response in Japanese</td><td>' . htmlspecialchars($grades['Response_Japanese'] ?? 'N/A') . '</td></tr>
            <tr><td>Response in English</td><td>' . htmlspecialchars($grades['Response_English'] ?? 'N/A') . '</td></tr>
        </tbody>
    </table>

    <div class="grade-box"><strong>Total Grade:</strong> ' . htmlspecialchars($totalGrade) . '</div>
    <h3>TEACHER NOTES:</h3>
    <div class="teacher-comment-box">' . htmlspecialchars($teacherNote) . '</div>';
}

// Load HTML into Dompdf
$dompdf->loadHtml($html);

// Set paper size to A4
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Stream the PDF to browser for download
$dompdf->stream("student_performance_report.pdf", ["Attachment" => true]);

exit();
