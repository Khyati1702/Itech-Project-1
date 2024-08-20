<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'configure.php';

// Load Composer's autoloader
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\TablePosition;

// Ensure user is logged in
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

// Ensure UserID is set
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
$studentRole = $student['Role'];

// Fetch teacher information (assuming the current logged-in user is the teacher)
$teacherName = $_SESSION['Username'];

// Determine the assessments to display based on the student's role
$assessments = ($studentRole == 'Stage1Students') ? [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Investigation_Task_Part_A", "Investigation_Task_Part_B"
] : [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Oral_Presentation", "Response_Japanese", "Response_English"
];

// Fetch grades and comments from gradings table
$gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ?");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

// Fetch exam scores and comments from exam_scores table
$examScoresQuery = $config->prepare("SELECT * FROM exam_scores WHERE StudentID = ?");
$examScoresQuery->bind_param("i", $UserID);
$examScoresQuery->execute();
$examScoresResult = $examScoresQuery->get_result();

// Create a new Word document
$phpWord = new PhpWord();
$section = $phpWord->addSection();

// Add Student Information
$section->addText('Student Performance Report', ['bold' => true, 'size' => 16, 'align' => 'center']);
$section->addText('Name: ' . $student['Name']);
$section->addText('Course: ' . $student['Course']);
$section->addText('Teacher: ' . $teacherName);  // Added teacher name
$section->addTextBreak(2);

// Add Assessment Grades Table
$section->addText('Assessment Grades', ['bold' => true, 'size' => 14]);

$table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50]);
$table->addRow();
$table->addCell(5000)->addText('Assessment');
$table->addCell(2000)->addText('Grade');
$table->addCell(8000)->addText('Comment');

foreach ($assessments as $assessment) {
    if (!empty($grades[$assessment]) || !empty($grades['Comments_' . $assessment])) {
        $table->addRow();
        $table->addCell(5000)->addText(str_replace('_', ' ', $assessment));
        $table->addCell(2000)->addText($grades[$assessment]);
        $table->addCell(8000)->addText($grades['Comments_' . $assessment]);
    }
}

// Add Exam Scores Table
$section->addTextBreak(2);
$section->addText('Exam Scores', ['bold' => true, 'size' => 14]);

$examTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50]);
$examTable->addRow();
$examTable->addCell(5000)->addText('Exam');
$examTable->addCell(2000)->addText('Score');
$examTable->addCell(8000)->addText('Comment');

while ($exam = $examScoresResult->fetch_assoc()) {
    foreach (['Exam1', 'Exam2'] as $examType) {
        if (!empty($exam[$examType])) {
            $examTable->addRow();
            $examTable->addCell(5000)->addText($examType);
            $examTable->addCell(2000)->addText($exam[$examType]);
            $examTable->addCell(8000)->addText($exam['Comments_' . $examType]);
        }
    }
}

// Save the Word document
$wordFile = 'student_performance_' . $student['Name'] . '.docx';
$phpWord->save($wordFile, 'Word2007');

// Offer the file for download
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . basename($wordFile) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($wordFile));
flush(); // Flush system output buffer
readfile($wordFile);
unlink($wordFile); // Delete the file after download
exit();
?>
