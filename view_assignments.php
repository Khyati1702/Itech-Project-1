<?php
session_start();
if (!isset($_SESSION['Username']) || ($_SESSION['Role'] != 'Stage1Students' && $_SESSION['Role'] != 'Stage2Students')) {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

// Determine the student's stage based on their role
$role = $_SESSION['Role'];
$stage = ($role == 'Stage1Students') ? 'Stage1' : 'Stage2';

// Fetch assignments matching the student's stage
$stmt = $config->prepare("SELECT * FROM assignments WHERE Stage = ?");
$stmt->bind_param("s", $stage);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Assignments</title>
    <link rel="stylesheet" href="view_assignments.css"> <!-- Link to your CSS file -->
</head>
<body>
    <?php include 'navbar.php'; ?>

    <h1>Assignment (<?php echo $stage; ?>)</h1>

    <div class="assignments-container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="assignment-block">
                <div class="assignment-header">
                    <h2><?php echo htmlspecialchars($row['Title']); ?></h2>
                    <p class="time-remaining">
                        <?php
                        // Calculate time remaining
                        $dueDate = new DateTime($row['DueDate']);
                        $currentDate = new DateTime();
                        if ($currentDate < $dueDate) {
                            $interval = $currentDate->diff($dueDate);
                            echo $interval->format('Time remaining: %d days %h hours');
                        } else {
                            echo "Assignment overdue";
                        }
                        ?>
                    </p>
                </div>

                <div class="assignment-details">
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($row['Description']); ?></p>
                    <p><strong>Start Date:</strong> <?php echo htmlspecialchars($row['StartDate']); ?></p>
                    <p><strong>Due Date:</strong> <?php echo !empty($row['DueDate']) ? htmlspecialchars($row['DueDate']) : 'No Due Date'; ?></p>
                </div>

                <div class="assignment-actions">
                    <?php if (!empty($row['FilePath'])): ?>
                        <a href="<?php echo htmlspecialchars($row['FilePath']); ?>" class="download-btn">Download File</a>
                    <?php else: ?>
                        <p>No file uploaded</p>
                    <?php endif; ?>
                    <!-- Submit Assignment Button triggers modal or submission portal -->
                    <button class="submit-assignment-btn" onclick="openSubmissionPortal(<?php echo $row['AssignmentID']; ?>)">Submit Assignment</button>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Submission Portal (hidden by default) -->
    <div id="submission-portal" class="submission-modal" style="display:none;">
        <div class="modal-content">
            <span class="close-btn" onclick="closeSubmissionPortal()">&times;</span>
            <h2 class="Submit-h2">Submit Your Assignment</h2>
            <form action="submit_assignment.php" method="post" enctype="multipart/form-data">
                <input type="file" name="assignment_file" class="File_Selection" required>
                <input type="hidden" id="assignment_id" name="assignment_id">
                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function openSubmissionPortal(assignmentID) {
            document.getElementById('submission-portal').style.display = 'block';
            document.getElementById('assignment_id').value = assignmentID;
        }

        function closeSubmissionPortal() {
            document.getElementById('submission-portal').style.display = 'none';
        }
    </script>
</body>
</html>
