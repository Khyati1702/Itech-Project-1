<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// THis code makes a Final word report for the stage 2 students , which contains both stage 1 an dstage 2 grades data. (Button for this present on respective students performance page).

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

// Fetchings student information
$query = $config->prepare("SELECT Name, Course, Role FROM users WHERE UserID = ?");
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$studentName = $student['Name'];
$studentCourse = $student['Course'];
$studentRole = $student['Role'];

// Fetching the  grades for Stage 2
$gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? ORDER BY GradingTimestamp DESC LIMIT 1");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

// Fetching the  archived Stage 1 grades f
$archivedGrades = null;
if ($studentRole == 'Stage2Students') {
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
}


$phpWord = new PhpWord();
$section = $phpWord->addSection();

// Adding Title 
$section->addText('School Report', ['bold' => true, 'size' => 20, 'alignment' => 'center'], ['alignment' => 'center']);
$section->addTextBreak(1);

// Adding the Stage 1 Grades
if ($archivedGrades) {
    $section->addText('Stage 1: Grade A-E (5 grade rating)', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $section->addText('Student Name: ' . htmlspecialchars($studentName), ['bold' => true]);
    
    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
    $table->addRow();
    $table->addCell(1500)->addText('Task', ['bold' => true]);
    $table->addCell(1000)->addText('Inter', ['bold' => true]);
    $table->addCell(1000)->addText('Text Ana', ['bold' => true]);
    $table->addCell(1000)->addText('Text Pro', ['bold' => true]);
    $table->addCell(1000)->addText('Inv A', ['bold' => true]);
    $table->addCell(1000)->addText('Inv B', ['bold' => true]);
    $table->addCell(1000)->addText('Total', ['bold' => true]);
    $table->addCell(2000)->addText('Comment', ['bold' => true]);

    $table->addRow();
    $table->addCell(1500)->addText('Grade');
    $table->addCell(1000)->addText($archivedGrades['Interaction'] ?? 'N/A');
    $table->addCell(1000)->addText($archivedGrades['Text_Analysis'] ?? 'N/A');
    $table->addCell(1000)->addText($archivedGrades['Text_Production'] ?? 'N/A');
    $table->addCell(1000)->addText($archivedGrades['Investigation_Task_Part_A'] ?? 'N/A');
    $table->addCell(1000)->addText($archivedGrades['Investigation_Task_Part_B'] ?? 'N/A');
    $table->addCell(1000)->addText(
        ($archivedGrades['Interaction'] ?? 0) +
        ($archivedGrades['Text_Analysis'] ?? 0) +
        ($archivedGrades['Text_Production'] ?? 0) +
        ($archivedGrades['Investigation_Task_Part_A'] ?? 0) +
        ($archivedGrades['Investigation_Task_Part_B'] ?? 0)
    );
    $table->addCell(2000)->addText($archivedGrades['Old_teacherNote'] ?? 'N/A');
    
    $section->addTextBreak(2);
}

// Adding the  Stage 2 Grade
if ($grades) {
    $section->addText('Stage 2: Grade A+~E- (15 grade rating)', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $section->addText('Student Name: ' . htmlspecialchars($studentName), ['bold' => true]);

    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
    $table->addRow();
    $table->addCell(1500)->addText('Tasks', ['bold' => true]);
    $table->addCell(1000)->addText('Inter', ['bold' => true]);
    $table->addCell(1000)->addText('Text Ana', ['bold' => true]);
    $table->addCell(1000)->addText('Text Pro1', ['bold' => true]);
    $table->addCell(1000)->addText('Oral Pre', ['bold' => true]);
    $table->addCell(1000)->addText('Res Jap', ['bold' => true]);
    $table->addCell(1000)->addText('Res Eng', ['bold' => true]);
    $table->addCell(1000)->addText('Total', ['bold' => true]);
    $table->addCell(2000)->addText('Comment', ['bold' => true]);

    $table->addRow();
    $table->addCell(1500)->addText('Grade');
    $table->addCell(1000)->addText($grades['Interaction'] ?? 'N/A');
    $table->addCell(1000)->addText($grades['Text_Analysis'] ?? 'N/A');
    $table->addCell(1000)->addText($grades['Text_Production'] ?? 'N/A');
    $table->addCell(1000)->addText($grades['Oral_Presentation'] ?? 'N/A');
    $table->addCell(1000)->addText($grades['Response_Japanese'] ?? 'N/A');
    $table->addCell(1000)->addText($grades['Response_English'] ?? 'N/A');
    $table->addCell(1000)->addText(
        ($grades['Interaction'] ?? 0) +
        ($grades['Text_Analysis'] ?? 0) +
        ($grades['Text_Production'] ?? 0) +
        ($grades['Oral_Presentation'] ?? 0) +
        ($grades['Response_Japanese'] ?? 0) +
        ($grades['Response_English'] ?? 0)
    );
    $table->addCell(2000)->addText($grades['TeacherNote'] ?? 'N/A');
}


$section->addTextBreak(2);
$section->addText('Abbreviation', ['bold' => true, 'size' => 12], ['alignment' => 'left']);
$section->addText('* Inter = Interaction');
$section->addText('* Text Ana = Text Analysis');
$section->addText('* Text Pro = Text Production');
$section->addText('* Inv A = Investigation Task Part A (Response in Japanese)');
$section->addText('* Inv B = Investigation Task Part B (Response in English)');
$section->addText('* Oral Pre = Oral Presentation (Stage 2)');
$section->addText('* Res Jap = Response in Japanese (Stage 2)');
$section->addText('* Res Eng = Response in English (Stage 2)');

// Saveing the Word document
$wordFile = 'stage2_student_report_' . htmlspecialchars($studentName) . '.docx';
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($wordFile);


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
