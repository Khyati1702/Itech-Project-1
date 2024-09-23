<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

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

// Fetch student information
$query = $config->prepare("SELECT Name, Course FROM users WHERE UserID = ?");
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$studentName = $student['Name'];
$studentCourse = $student['Course'];

// Fetch current grades for Stage 2
$gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? ORDER BY GradingTimestamp DESC LIMIT 1");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

// Fetch archived Stage 1 grades for this Stage 2 student
$archivedQuery = $config->prepare("
    SELECT * 
    FROM stage1_grades_archive 
    WHERE StudentID = ? 
    AND Stage = 'Old_stage1Student'
    ORDER BY GradingTimestamp DESC 
    LIMIT 1;");
$archivedQuery->bind_param("i", $UserID);
$archivedQuery->execute();
$archivedResult = $archivedQuery->get_result();
$archivedGrades = $archivedResult->fetch_assoc();

// Calculate totals for Stage 1 and Stage 2
$stage1Total = (
    ($archivedGrades['Interaction'] ?? 0) +
    ($archivedGrades['Text_Analysis'] ?? 0) +
    ($archivedGrades['Text_Production'] ?? 0) +
    ($archivedGrades['Investigation_Task_Part_A'] ?? 0) +
    ($archivedGrades['Investigation_Task_Part_B'] ?? 0)
);

$stage2Total = (
    ($grades['Interaction'] ?? 0) +
    ($grades['Text_Analysis'] ?? 0) +
    ($grades['Text_Production'] ?? 0) +
    ($grades['Oral_Presentation'] ?? 0) +
    ($grades['Response_Japanese'] ?? 0) +
    ($grades['Response_English'] ?? 0)
);

// HTML content for PDF report
$html = '
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        h1, h2 { text-align: center; }
        .comment-box { border: 1px solid black; padding: 10px; }
        .total-grade { font-weight: bold; }
    </style>
    <h1>School Report</h1>
    <h2>Stage 1: Grade A-E (5 grade rating)</h2>
    <p><strong>Student Name:</strong> ' . htmlspecialchars($studentName) . '</p>
    <table>
        <thead>
            <tr>
                <th>Task</th>
                <th>Inter</th>
                <th>Text Ana</th>
                <th>Text Pro</th>
                <th>Inv A</th>
                <th>Inv B</th>
                <th>Total</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Grade</td>
                <td>' . htmlspecialchars($archivedGrades['Interaction'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($archivedGrades['Text_Analysis'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($archivedGrades['Text_Production'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($archivedGrades['Investigation_Task_Part_A'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($archivedGrades['Investigation_Task_Part_B'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($stage1Total) . '</td>
                <td>' . htmlspecialchars($archivedGrades['Old_teacherNote'] ?? 'N/A') . '</td>
            </tr>
        </tbody>
    </table>
    
    <h2>Stage 2: Grade A+~E- (15 grade rating)</h2>
    <p><strong>Student Name:</strong> ' . htmlspecialchars($studentName) . '</p>
    <table>
        <thead>
            <tr>
                <th rowspan="2">Tasks</th>
                <th colspan="3">Folio</th>
                <th colspan="3">In-Depth Study</th>
                <th rowspan="2">Total</th>
                <th rowspan="2">Comment</th>
            </tr>
            <tr>
                <th>Inter</th>
                <th>Text Ana</th>
                <th>Text Pro1</th>
                <th>Oral Pre</th>
                <th>Res Jap</th>
                <th>Res Eng</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Grade</td>
                <td>' . htmlspecialchars($grades['Interaction'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($grades['Text_Analysis'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($grades['Text_Production'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($grades['Oral_Presentation'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($grades['Response_Japanese'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($grades['Response_English'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($stage2Total) . '</td>
                <td>' . htmlspecialchars($grades['TeacherNote'] ?? 'N/A') . '</td>
            </tr>
        </tbody>
    </table>

    <h3>Abbreviation</h3>
    <p>*Inter= Interaction<br>
       * Text Ana= Text Analysis<br>
       * Text Pro= Text Production<br>
       * Inv A= Investigation Task Part A (Response in Japanese)<br>
       * Inv B= Investigation Task Part B (Response in English)<br>
       * Oral Pre= Oral Presentation (Stage 2)<br>
       * Res Jap= Response in Japanese (Stage 2)<br>
       * Res Eng= Response in English (Stage 2)</p>
';

// Load the HTML into Dompdf
$dompdf->loadHtml($html);

// Set paper size to A4
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Stream the PDF to the browser for download
$dompdf->stream("stage2_student_report.pdf", ["Attachment" => true]);

exit();
