<?php
session_start();
if (!isset($_SESSION['Username']) || $_SESSION['Role'] != 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}


// This page is seeing the submisisons made by the students. 

include 'configure.php';

$teacherID = $_SESSION['UserID'];
$stmt = $config->prepare("SELECT * FROM assignments WHERE TeacherID = ?");
$stmt->bind_param("i", $teacherID);
$stmt->execute();
$assignments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions</title>
    <link rel="stylesheet" href="submissions.css"> 
</head>
<body>
    <?php include 'navbar.php'; ?>

    <h1>Student Submissions</h1>

    <?php while ($assignment = $assignments->fetch_assoc()): ?>
        <div class="assignment-block">
            <h2><?php echo htmlspecialchars($assignment['Title']); ?></h2>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($assignment['Description']); ?></p>

          
            <?php
            $assignmentID = $assignment['AssignmentID'];
            $submissionsStmt = $config->prepare("
                SELECT s.*, u.Name, u.GoogleEmail
                FROM AssignmentSubmissions s
                JOIN users u ON s.StudentID = u.UserID
                WHERE s.AssignmentID = ?
                AND s.SubmissionDate = (
                    SELECT MAX(SubmissionDate)
                    FROM AssignmentSubmissions
                    WHERE AssignmentID = ? AND StudentID = s.StudentID
                )
                GROUP BY s.StudentID");
            $submissionsStmt->bind_param("ii", $assignmentID, $assignmentID);
            $submissionsStmt->execute();
            $submissions = $submissionsStmt->get_result();
            ?>

            <?php if ($submissions->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student Email</th>
                            <th>Submission Date</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($submission = $submissions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($submission['Name']); ?></td>
                                <td><?php echo htmlspecialchars($submission['GoogleEmail']); ?></td>
                                <td><?php echo htmlspecialchars($submission['SubmissionDate']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($submission['FilePath']); ?>" download>Download File</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No submissions yet for this assignment.</p>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>

    <?php include 'footer.php'; ?>
</body>
</html>
