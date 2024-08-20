<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Initialize Dompdf with options
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

$UserID = $_GET['UserID'];
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

// Determine the assessments to display based on the student's role
$assessments = ($studentRole == 'Stage1Students') ? [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Investigation_Task_Part_A", "Investigation_Task_Part_B"
] : [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Oral_Presentation", "Response_Japanese", "Response_English"
];

// Fetch grades, comments, and teacher notes from gradings table
$gradesQuery = $config->prepare("
    SELECT * FROM gradings 
    WHERE StudentID = ?");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

// Fetch exam scores and comments from exam_scores table
$examScoresQuery = $config->prepare("
    SELECT * FROM exam_scores 
    WHERE StudentID = ?");
$examScoresQuery->bind_param("i", $UserID);
$examScoresQuery->execute();
$examScoresResult = $examScoresQuery->get_result();

// Start creating the HTML for the PDF
$html = '
<style>
    body {
        font-family: Arial, sans-serif;
        color: #333;
        line-height: 1.6;
    }
    .report-title {
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #2c3e50;
    }
    .report-section-title {
        font-size: 18px;
        font-weight: bold;
        margin-top: 30px;
        margin-bottom: 10px;
        color: #2980b9;
    }
    .student-info {
        margin-bottom: 20px;
    }
    .student-info p {
        margin: 5px 0;
        font-size: 14px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    table, th, td {
        border: 1px solid #bdc3c7;
    }
    th, td {
        padding: 10px;
        text-align: left;
        font-size: 12px;
    }
    th {
        background-color: #ecf0f1;
        color: #2c3e50;
    }
</style>

<div class="report-title">Student Performance Report</div>

<div class="student-info">
    <p><strong>Name:</strong> ' . htmlspecialchars($studentName) . '</p>
    <p><strong>Course:</strong> ' . htmlspecialchars($studentCourse) . '</p>
    <p><strong>Teacher:</strong> ' . htmlspecialchars($teacherName) . '</p>
</div>

<div class="report-section-title">Assessment Grades</div>
<table>
    <thead>
        <tr>
            <th>Assessment</th>
            <th>Grade</th>
        </tr>
    </thead>
    <tbody>';

foreach ($assessments as $assessment) {
    if (!empty($grades[$assessment])) {
        $html .= '<tr>
                    <td>' . htmlspecialchars(str_replace('_', ' ', $assessment)) . '</td>
                    <td>' . htmlspecialchars($grades[$assessment]) . '</td>
                  </tr>';
    }
}

$html .= '</tbody>
</table>

<div class="report-section-title">Exam Scores</div>
<table>
    <thead>
        <tr>
            <th>Exam</th>
            <th>Score</th>
        </tr>
    </thead>
    <tbody>';

while ($exam = $examScoresResult->fetch_assoc()) {
    foreach (['Exam1', 'Exam2'] as $examType) {
        if (!empty($exam[$examType])) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($examType) . '</td>
                        <td>' . htmlspecialchars($exam[$examType]) . '</td>
                      </tr>';
        }
    }
}

$html .= '</tbody>
</table>';

// Load HTML content into Dompdf
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Output the generated PDF (force download)
$dompdf->stream("student_performance_report.pdf", [
    "Attachment" => true
]);

exit();
