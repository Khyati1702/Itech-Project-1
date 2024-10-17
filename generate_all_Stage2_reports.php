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

// Fetch all Stage 2 students
$studentsQuery = "SELECT UserID, Name FROM users WHERE Role = 'Stage2Students'";
$studentsResult = $config->query($studentsQuery);

// Create a directory 'uploads/reports' if it doesn't exist
$reportsDir = __DIR__ . '/uploads/reports';
if (!file_exists($reportsDir)) {
    if (!mkdir($reportsDir, 0755, true)) {
        exit('An error occurred while setting up the reports directory.');
    }
}

// Array to store generated file paths and student names
$generatedFiles = [];
$studentNames = [];

while ($student = $studentsResult->fetch_assoc()) {
    $UserID = $student['UserID'];
    $studentName = $student['Name'];

    // Fetch current Stage 2 grades for the student
    $gradesQuery = $config->prepare("SELECT * FROM gradings WHERE StudentID = ? ORDER BY GradingTimestamp DESC LIMIT 1");
    $gradesQuery->bind_param("i", $UserID);
    $gradesQuery->execute();
    $gradesResult = $gradesQuery->get_result();
    $grades = $gradesResult->fetch_assoc();

    if (!$grades) {
        continue; // Skip if no Stage 2 grades are available
    }

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

    // Generate HTML content for the report
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

    // Initialize Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Output the generated PDF to a file
    $pdfFileName = 'student_report_stage2_' . $UserID . '.pdf';
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
