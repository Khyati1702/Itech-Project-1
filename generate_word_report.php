<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'configure.php';
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Shared\Converter;

// Check if the user is logged in
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

// Check if the UserID is provided in the URL
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

// Fetch current teacher name
$teacherQuery = $config->prepare("SELECT Name FROM users WHERE UserID = ?");
$teacherQuery->bind_param("i", $_SESSION['UserID']);
$teacherQuery->execute();
$teacherResult = $teacherQuery->get_result();
$teacher = $teacherResult->fetch_assoc();
$teacherName = $teacher['Name'] ?? 'N/A'; 

// Fetch grades from the gradings table
$gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? ORDER BY GradingTimestamp DESC LIMIT 1");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

if (!$grades) {
    echo "No grades available for this student.";
    exit();
}

$teacherNote = $grades['TeacherNote'] ?? 'No notes available'; 

// Initialize PhpWord object
$phpWord = new PhpWord();
$section = $phpWord->addSection();

// Determine whether the student is in Stage 1 or Stage 2
if ($studentRole == 'Stage1Students') {
    // Stage 1: Add Title and Subtitle
    $section->addText('SACE Stage 1', ['bold' => true, 'size' => 16, 'alignment' => 'center'], ['alignment' => 'center']);
    $section->addText('Student Report', ['bold' => true, 'size' => 20, 'alignment' => 'center'], ['alignment' => 'center']);
    $section->addTextBreak(1);

    // Add Student Information in a table
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 100 * 50, 
    ]);

    $table->addRow();
    $table->addCell(9000)->addText('Student: ' . $studentName, ['bold' => true]);
    $table->addRow();
    $table->addCell(9000)->addText('Course: ' . $studentCourse, ['bold' => true]);
    $table->addRow();
    $table->addCell(9000)->addText('Teacher: ' . $teacherName, ['bold' => true]);

    $section->addTextBreak(1);

    // Add Assessment Table for Stage 1
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 100 * 50, 
    ]);

    // Add Header Row
    $table->addRow();
    $table->addCell(7000)->addText('Summative Assessment Task', ['bold' => true], ['alignment' => 'center']);
    $table->addCell(2000)->addText('Grade', ['bold' => true], ['alignment' => 'center']);

    // Stage 1 Specific Rows
    $table->addRow();
    $table->addCell(7000)->addText('Interaction');
    $table->addCell(2000)->addText($grades['Interaction'] ?? 'N/A');

    $table->addRow();
    $table->addCell(7000)->addText('Text Analysis');
    $table->addCell(2000)->addText($grades['Text_Analysis'] ?? 'N/A');

    $table->addRow();
    $table->addCell(7000)->addText('Text Production');
    $table->addCell(2000)->addText($grades['Text_Production'] ?? 'N/A');

    $table->addRow();
    $table->addCell(7000)->addText('Investigation Task Part A: PPT presentation');
    $table->addCell(2000)->addText($grades['Investigation_Task_Part_A'] ?? 'N/A');

    $table->addRow();
    $table->addCell(7000)->addText('Investigation Task Part B: Reflective Writing in English');
    $table->addCell(2000)->addText($grades['Investigation_Task_Part_B'] ?? 'N/A');

    // Add Total Grade Section
    $section->addTextBreak(3); 
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 50 * 60, 
    ]);

    // Add a row with a single cell for the Total Grade
    $totalGrade = ($grades['Interaction'] ?? 0) + 
                  ($grades['Text_Analysis'] ?? 0) + 
                  ($grades['Text_Production'] ?? 0) + 
                  ($grades['Investigation_Task_Part_A'] ?? 0) + 
                  ($grades['Investigation_Task_Part_B'] ?? 0);

    $row = $table->addRow(Converter::cmToTwip(2.5)); 
    $cell = $row->addCell(Converter::cmToTwip(6)); 
    $cell->addText('Total Grade: ' . number_format($totalGrade, 2), ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $cell->getStyle()->setVAlign('center'); 

    // Add Teacher Notes Section
    $section->addText('TEACHER NOTES:', ['bold' => true]);
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 100 * 50, 
    ]);
    $table->addRow();
    $table->addCell(9000)->addText($teacherNote);

} else if ($studentRole == 'Stage2Students') {
    // Stage 2: Add Title and Subtitle
    $section->addText('SACE Stage 2', ['bold' => true, 'size' => 16, 'alignment' => 'center'], ['alignment' => 'center']);
    $section->addText('Student Report', ['bold' => true, 'size' => 20, 'alignment' => 'center'], ['alignment' => 'center']);
    $section->addTextBreak(1);

    // Add Student Information in a table
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 100 * 50, 
    ]);

    $table->addRow();
    $table->addCell(9000)->addText('Student: ' . $studentName, ['bold' => true]);
    $table->addRow();
    $table->addCell(9000)->addText('Course: ' . $studentCourse, ['bold' => true]);
    $table->addRow();
    $table->addCell(9000)->addText('Teacher: ' . $teacherName, ['bold' => true]);

    $section->addTextBreak(1);

    // Add Assessment Table for Stage 2
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 100 * 50, 
    ]);

    // Add Header Row
    $table->addRow();
    $table->addCell(2000, ['gridSpan' => 2])->addText('Summative Assessment Task', ['bold' => true], ['alignment' => 'center']);
    $table->addCell(2000)->addText('Grade', ['bold' => true], ['alignment' => 'center']);

    // Folio Section
    $table->addRow();
    $table->addCell(2000, ['vMerge' => 'restart'])->addText('Folio', ['bold' => true]);
    $table->addCell(2000)->addText('Interaction');
    $table->addCell(2000)->addText($grades['Interaction'] ?? 'N/A');

    $table->addRow();
    $table->addCell(null, ['vMerge' => 'continue']);
    $table->addCell(2000)->addText('Text Analysis');
    $table->addCell(2000)->addText($grades['Text_Analysis'] ?? 'N/A');

    $table->addRow();
    $table->addCell(null, ['vMerge' => 'continue']);
    $table->addCell(2000)->addText('Text Production');
    $table->addCell(2000)->addText($grades['Text_Production'] ?? 'N/A');

    // In-Depth Study Section
    $table->addRow();
    $table->addCell(2000, ['vMerge' => 'restart'])->addText('In-Depth Study', ['bold' => true]);
    $table->addCell(2000)->addText('Oral Presentation');
    $table->addCell(2000)->addText($grades['Oral_Presentation'] ?? 'N/A');

    $table->addRow();
    $table->addCell(null, ['vMerge' => 'continue']);
    $table->addCell(2000)->addText('Response in Japanese');
    $table->addCell(2000)->addText($grades['Response_Japanese'] ?? 'N/A');

    $table->addRow();
    $table->addCell(null, ['vMerge' => 'continue']);
    $table->addCell(2000)->addText('Response in English');
    $table->addCell(2000)->addText($grades['Response_English'] ?? 'N/A');

    // Add Total Grade Section
    $section->addTextBreak(3); 
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 50 * 60, 
    ]);

    // Add a row with a single cell for the Total Grade
    $totalGrade = ($grades['Interaction'] ?? 0) + 
                  ($grades['Text_Analysis'] ?? 0) + 
                  ($grades['Text_Production'] ?? 0) + 
                  ($grades['Oral_Presentation'] ?? 0) + 
                  ($grades['Response_Japanese'] ?? 0) + 
                  ($grades['Response_English'] ?? 0);

    $row = $table->addRow(Converter::cmToTwip(2.5)); 
    $cell = $row->addCell(Converter::cmToTwip(6)); 
    $cell->addText('Total Grade: ' . number_format($totalGrade, 2), ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $cell->getStyle()->setVAlign('center'); 

    // Add Teacher Notes Section
    $section->addText('TEACHER NOTES:', ['bold' => true]);
    $table = $section->addTable([
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'alignment' => 'center',
        'width' => 100 * 50, 
    ]);
    $table->addRow();
    $table->addCell(9000)->addText($teacherNote);
}

// Save the Word document
$wordFile = 'student_performance_' . $studentName . '.docx';
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
flush();
readfile($wordFile);
unlink($wordFile); 
exit();
?>
