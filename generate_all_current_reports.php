<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['Username']) || !in_array($_SESSION['Role'], ['Teacher', 'Admin'])) {
    header('Location: LoginPage.php');
    exit();
}

require 'configure.php';

// Set time limit and memory limit for PDF generation
set_time_limit(300);
ini_set('memory_limit', '256M');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log'); // Error log

// Fetch teacher information (the teacher generating the reports)
$teacherQuery = $config->prepare("SELECT Name FROM users WHERE UserID = ?");
$teacherQuery->bind_param("i", $_SESSION['UserID']);
$teacherQuery->execute();
$teacherResult = $teacherQuery->get_result();
$teacher = $teacherResult->fetch_assoc();
$teacherName = $teacher['Name'];

// Fetch all students
$studentsQuery = "SELECT UserID, Name, Course, Role FROM users WHERE Role IN ('Stage1Students', 'Stage2Students')";
$studentsResult = $config->query($studentsQuery);

// Create a directory 'uploads/reports' if it doesn't exist
$reportsDir = __DIR__ . '/uploads/reports';
if (!file_exists($reportsDir)) {
    if (!mkdir($reportsDir, 0755, true)) {  // Use 0755 permissions for the new directories
        exit('An error occurred while setting up the reports directory.');
    }
}

// Array to store generated file paths and student names
$generatedFiles = [];
$studentNames = [];

while ($student = $studentsResult->fetch_assoc()) {
    $UserID = $student['UserID'];
    $studentName = $student['Name'];
    $studentRole = $student['Role'];

    // Fetch the latest grades and teacher notes for the student
    $gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? ORDER BY GradingTimestamp DESC LIMIT 1");
    $gradesQuery->bind_param("i", $UserID);
    $gradesQuery->execute();
    $gradesResult = $gradesQuery->get_result();
    $grades = $gradesResult->fetch_assoc();

    if (!$grades) {
        continue; // Skip if no grades are available
    }

    $teacherNote = $grades['TeacherNote'] ?? 'No notes available';

    // Calculate Total Grade for the Student
    $totalGradeQuery = "
        SELECT 
            StudentID,
            (COALESCE(Interaction, 0) + COALESCE(Text_Analysis, 0) + COALESCE(Text_Production, 0) + 
             COALESCE(Investigation_Task_Part_A, 0) + COALESCE(Investigation_Task_Part_B, 0) + 
             COALESCE(Oral_Presentation, 0) + COALESCE(Response_Japanese, 0) + COALESCE(Response_English, 0)) AS TotalGrade
        FROM gradings
        WHERE StudentID = ?"; 

    $totalGradeStmt = $config->prepare($totalGradeQuery);
    $totalGradeStmt->bind_param("i", $UserID);
    $totalGradeStmt->execute();
    $totalGradeResult = $totalGradeStmt->get_result();
    $totalGradeData = $totalGradeResult->fetch_assoc();
    $totalGrade = $totalGradeData['TotalGrade'] ?? 'N/A';

    // Generate HTML content based on student role
    $html = ''; // Initialize HTML variable

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

    // Initialize Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Output the generated PDF to a file
    $pdfFileName = 'student_report_' . $UserID . '.pdf';
    $pdfFilePath = $reportsDir . '/' . $pdfFileName;

    if (file_put_contents($pdfFilePath, $dompdf->output()) !== false) {
        $generatedFiles[] = $pdfFileName;
        $studentNames[] = $studentName;
    }
}

// Close database connections if necessary
$studentsResult->free();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Reports</title>
    <link rel="stylesheet" href="generate_all_current_reports.css">
    <link rel="stylesheet" href="colors.css">
</head>
<?php include 'navbar.php'; ?>
<body>
    <h1>Reports Generated</h1>

    <!-- Button to Download All Reports -->
    <button id="downloadAll" class="download-btn">Download All Reports</button>

    <!-- List of Students for whom reports were generated -->
    <div class="student-list">
        <h2>Generated reports for :</h2>
        <ul>
            <?php foreach ($studentNames as $name): ?>
                <li>
                    <span><?php echo htmlspecialchars($name); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Hidden download links for each report -->
    <div id="downloadLinks" style="display:none;">
        <?php foreach ($generatedFiles as $file): ?>
            <a href="/DraftWebsite/uploads/reports/<?php echo $file; ?>" download="<?php echo $file; ?>"></a>
        <?php endforeach; ?>
    </div>

    <script>
        document.getElementById('downloadAll').addEventListener('click', function() {
            var links = document.querySelectorAll('#downloadLinks a');
            links.forEach(function(link) {
                link.click();  // Programmatically trigger download for each link
            });
        });
    </script>
    
</body>
<?php include 'footer.php'; ?>
</html>

