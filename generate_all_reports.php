<?php
require 'vendor/autoload.php';
require 'configure.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use ZipArchive;

session_start();

// Ensure the user is logged in and is a teacher
if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}

// Check if CourseID is provided
if (!isset($_GET['CourseID'])) {
    header('Location: Profile.php');
    exit();
}

$CourseID = $_GET['CourseID'];

// Fetch all students in the course
$studentsQuery = $config->prepare("
    SELECT UserID, Name, Course FROM users WHERE CourseID = ?
");
$studentsQuery->bind_param("i", $CourseID);
$studentsQuery->execute();
$studentsResult = $studentsQuery->get_result();

// Initialize Dompdf with options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

// Create a directory to store individual PDF files temporarily
$tempDir = 'temp_reports';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Initialize the ZIP archive
$zip = new ZipArchive();
$zipFileName = "course_reports.zip";
$zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Loop through each student and generate their report
while ($student = $studentsResult->fetch_assoc()) {
    // Fetch grades, comments, and exam scores
    $gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ?");
    $gradesQuery->bind_param("i", $student['UserID']);
    $gradesQuery->execute();
    $grades = $gradesQuery->get_result()->fetch_assoc();

    $examScoresQuery = $config->prepare("SELECT * FROM exam_scores WHERE StudentID = ?");
    $examScoresQuery->bind_param("i", $student['UserID']);
    $examScoresQuery->execute();
    $examScores = $examScoresQuery->get_result();

    // Generate the HTML for this student's report
    $html = "
    <h1 style='text-align:center;'>Student Performance Report</h1>
    <p><strong>Name:</strong> {$student['Name']}</p>
    <p><strong>Course:</strong> {$student['Course']}</p>
    <p><strong>Teacher:</strong> {$_SESSION['Username']}</p>
    <h2>Assessment Grades</h2>
    <table border='1' width='100%' cellpadding='5' cellspacing='0'>
        <tr>
            <th>Assessment</th>
            <th>Grade</th>
            <th>Comment</th>
        </tr>";

    foreach ($grades as $key => $value) {
        if (strpos($key, 'Comments_') === false && !empty($value)) {
            $commentKey = 'Comments_' . $key;
            $html .= "<tr>
                <td>" . str_replace('_', ' ', $key) . "</td>
                <td>{$value}</td>
                <td>{$grades[$commentKey]}</td>
            </tr>";
        }
    }

    $html .= "</table>";

    // Exam Scores
    $html .= "<h2>Exam Scores</h2>
    <table border='1' width='100%' cellpadding='5' cellspacing='0'>
        <tr>
            <th>Exam</th>
            <th>Score</th>
            <th>Comment</th>
        </tr>";

    while ($exam = $examScores->fetch_assoc()) {
        foreach (['Exam1', 'Exam2'] as $examType) {
            if (!empty($exam[$examType])) {
                $html .= "<tr>
                    <td>{$examType}</td>
                    <td>{$exam[$examType]}</td>
                    <td>{$exam['Comments_' . $examType]}</td>
                </tr>";
            }
        }
    }

    $html .= "</table>";

    // Load the HTML content into Dompdf
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the PDF
    $dompdf->render();

    // Save the PDF to a file
    $pdfFilePath = $tempDir . '/' . 'report_' . $student['UserID'] . '.pdf';
    file_put_contents($pdfFilePath, $dompdf->output());

    // Add the PDF to the ZIP archive
    $zip->addFile($pdfFilePath, 'report_' . $student['UserID'] . '.pdf');
}

// Close the ZIP archive
$zip->close();

// Remove the temporary directory and its contents
array_map('unlink', glob("$tempDir/*.*"));
rmdir($tempDir);

// Offer the ZIP file for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipFileName) . '"');
header('Content-Length: ' . filesize($zipFileName));
readfile($zipFileName);

// Delete the ZIP file after download
unlink($zipFileName);

exit();
?>
