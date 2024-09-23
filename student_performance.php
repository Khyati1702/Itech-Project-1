<?php
session_start();
require 'configure.php';
require 'vendor/autoload.php'; // This autoloads the required libraries

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// The rest of your existing code continues here...


// Redirect if the user is not logged in
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

// Redirect if UserID is not provided in the URL
if (!isset($_GET['UserID'])) {
    header('Location: Profile.php');
    exit();
}

$UserID = $_GET['UserID'];
$loggedInUserRole = $_SESSION['Role'];

// Fetch student information
$query = $config->prepare("SELECT Name, Course, Role, GoogleEmail FROM users WHERE UserID = ?");
$query->bind_param("i", $UserID);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$studentRole = $student['Role'];
$studentName = $student['Name'];
$studentEmail = $student['GoogleEmail'];

// Define Stage 1 and Stage 2 assessments separately
$stage1Assessments = [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Investigation_Task_Part_A", "Investigation_Task_Part_B"
];

$stage2Assessments = [
    "Interaction", "Text_Analysis", "Text_Production", 
    "Oral_Presentation", "Response_Japanese", "Response_English"
];

// Determine which assessments to show based on the student's role
$assessments = ($studentRole == 'Stage1Students') ? $stage1Assessments : $stage2Assessments;

// Fetch current grades from the `gradings` table for the student
$gradesQuery = $config->prepare("
    SELECT * 
    FROM gradings 
    WHERE StudentID = ? 
    ORDER BY GradingTimestamp DESC 
    LIMIT 1;");
$gradesQuery->bind_param("i", $UserID);
$gradesQuery->execute();
$gradesResult = $gradesQuery->get_result();
$grades = $gradesResult->fetch_assoc();

// Fetch archived grades for Stage 2 students who were previously in Stage 1 from `stage1_grades_archive`
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

// Handle grade updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_grade']) && ($loggedInUserRole == 'Teacher' || $loggedInUserRole == 'Admin')) {
    $assessment = $_POST['assessment'];
    $newGrade = $_POST['new_grade'];
    $newComment = $_POST['new_comment'];

    // Ensure that existing grades are retained and only updated fields are changed
    $updateQuery = $config->prepare("
        UPDATE gradings 
        SET $assessment = IFNULL(?, $assessment), 
            Comments_$assessment = IFNULL(?, Comments_$assessment), 
            GradingTimestamp = NOW()
        WHERE StudentID = ? AND TeacherID = ?");

    // Bind new values
    $updateQuery->bind_param("ssii", 
        $newGrade, 
        $newComment, 
        $UserID,
        $_SESSION['UserID'] // To identify the teacher making the update
    );

    if ($updateQuery->execute()) {
        echo "<p>Grade updated successfully.</p>";
    } else {
        echo "<p>Failed to update grade.</p>";
    }

    header("Location: student_performance.php?UserID=" . $UserID);
    exit();
}

// Handle email sharing functionality
if (isset($_POST['share_report'])) {
    require 'vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.your-email-server.com'; // Set your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@example.com';     // SMTP username
        $mail->Password   = 'your-email-password';        // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('your-email@example.com', 'Your School Name');
        $mail->addAddress($studentEmail, $studentName); // Add student email

        // Determine which report to attach
        $selectedReport = $_POST['report_type'];

        switch ($selectedReport) {
            case 'pdf':
                $reportFilePath = 'path_to_generated_report.pdf'; // Path to PDF report
                break;
            case 'word':
                $reportFilePath = 'path_to_generated_report.docx'; // Path to Word report
                break;
            case 'final_pdf':
                $reportFilePath = 'path_to_final_report.pdf'; // Path to Final PDF report
                break;
            case 'final_word':
                $reportFilePath = 'path_to_final_report.docx'; // Path to Final Word report
                break;
            default:
                echo "Invalid report type selected.";
                exit();
        }

        $mail->addAttachment($reportFilePath);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your Performance Report';
        $mail->Body    = 'Dear ' . $studentName . ',<br><br>Please find attached your performance report.<br><br>Best regards,<br>Your Teacher';

        $mail->send();
        echo 'Report shared successfully via email!';
    } catch (Exception $e) {
        echo "Report could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Performance</title>
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="student_performance.css">
    <style>
        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-body {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<main>
    <h2><?php echo htmlspecialchars($student['Name'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($student['Course'] ?? 'N/A'); ?></h2>
    
    <?php if ($loggedInUserRole == 'Teacher' || $loggedInUserRole == 'Admin'): ?>
    <!-- Button sections grouped in flex layout -->
    <section style="display: flex; gap: 20px;">
        <!-- Download PDF Report Button -->
        <a href="generate_report.php?UserID=<?php echo $UserID; ?>" class="btn btn-primary">Download PDF Report</a>
        
        <?php if ($studentRole == 'Stage2Students'): ?>
            <!-- Download Final PDF Report Button (displayed inline) -->
            <a href="generate_stage2_pdf_report.php?UserID=<?php echo $UserID; ?>" class="btn btn-primary">Download Final PDF Report</a>
        <?php endif; ?>
        
        <!-- Share Report via Email Form -->
        <form method="POST">
            <label for="report_type">Select report type to share:</label>
            <select name="report_type" id="report_type" required>
                <option value="pdf">PDF Report</option>
                <option value="word">Word Report</option>
                <option value="final_pdf">Final PDF Report</option>
                <option value="final_word">Final Word Report</option>
            </select>
            <button type="submit" name="share_report" class="btn btn-primary">Share Report via Email</button>
        </form>
    </section>

    <section style="display: flex; gap: 20px; margin-top: 20px;">
        <!-- Download Word Report Button -->
        <a href="generate_word_report.php?UserID=<?php echo $UserID; ?>" class="btn btn-primary">Download Word Report</a>
        
        <?php if ($studentRole == 'Stage2Students'): ?>
            <!-- Download Final Word Report Button (displayed inline) -->
            <a href="generate_Final_Word_report.php?UserID=<?php echo $UserID; ?>" class="btn btn-primary">Download Final Word Report</a>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    
    <!-- Assessment Grades Table -->
    <section>
        <h3>Grades and Comments</h3>
        <table>
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Grade</th>
                    <th>Comment</th>
                    <?php if ($loggedInUserRole == 'Teacher' || $loggedInUserRole == 'Admin'): ?>
                    <th>Timestamp</th> <!-- Only visible for teachers and admin -->
                    <th>Update Grade and Comments</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assessments as $assessment): ?>
                    <?php if (isset($grades[$assessment]) || isset($grades['Comments_' . $assessment])): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(str_replace('_', ' ', $assessment)); ?></td>
                            <td><?php echo htmlspecialchars($grades[$assessment] ?? 'N/A'); ?></td>
                            <!-- Comment button that opens the modal -->
                            <td>
                                <button class="btn_comment_asses" onclick="openModal('<?php echo htmlspecialchars($grades['Comments_' . $assessment] ?? 'N/A'); ?>')">View Comment</button>
                            </td>
                            <?php if ($loggedInUserRole == 'Teacher' || $loggedInUserRole == 'Admin'): ?>
                            <td><?php echo htmlspecialchars($grades['GradingTimestamp'] ?? 'N/A'); ?></td>
                            <td>
                                <form method="POST" class="update-grade-form">
                                    <input type="hidden" name="assessment" value="<?php echo $assessment; ?>">
                                    <input type="number" name="new_grade" value="<?php echo $grades[$assessment] ?? ''; ?>" required>
                                    <input type="text" name="new_comment" value="<?php echo $grades['Comments_' . $assessment] ?? ''; ?>">
                                    <button type="submit" name="update_grade">Update</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Modal for displaying comment -->
    <div id="commentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Comment</h3>
            <div class="modal-body" id="modalCommentContent"></div>
        </div>
    </div>

    <!-- Show Stage 1 Archived Grades for Stage 2 Students -->
    <?php if ($studentRole == 'Stage2Students' && $archivedGrades): ?>
    <section>
        <h3>Stage 1 Grades</h3>
        <table>
            <thead>
                <tr>
                    <th>Assessment</th>
                    <th>Grade</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stage1Assessments as $assessment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $assessment)); ?></td>
                        <td><?php echo htmlspecialchars($archivedGrades[$assessment] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($archivedGrades['Comments_' . $assessment] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- Teacher Notes Section -->
    <?php if ($loggedInUserRole == 'Teacher' || $loggedInUserRole == 'Admin'): ?>
        <section class="teacher-notes-section">
            <div class="teacher-notes-header">
                Teacher Notes
            </div>
            <div class="teacher-notes-content">
                <!-- Check if TeacherNote exists and display -->
                <p><?php echo htmlspecialchars($grades['TeacherNote'] ?? 'No teacher notes available'); ?></p>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

<script>
    function openModal(comment) {
        const modal = document.getElementById('commentModal');
        const modalContent = document.getElementById('modalCommentContent');
        modalContent.textContent = comment;
        modal.style.display = 'block';
    }

    const closeBtn = document.querySelector('.modal .close');
    closeBtn.onclick = function () {
        document.getElementById('commentModal').style.display = 'none';
    };

    window.onclick = function (event) {
        if (event.target == document.getElementById('commentModal')) {
            document.getElementById('commentModal').style.display = 'none';
        }
    };
</script>
</body>
</html>
