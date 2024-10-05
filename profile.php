<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

$username = $_SESSION['Username'];
$role = $_SESSION['Role'];

// Fetch the CourseID for the logged-in user
$courseQuery = $config->prepare("SELECT CourseID FROM users WHERE Username = ?");
$courseQuery->bind_param("s", $username);
$courseQuery->execute();
$courseResult = $courseQuery->get_result();
$courseData = $courseResult->fetch_assoc();

$CourseID = $courseData['CourseID'] ?? null;

if (!$CourseID) {
    die('Course ID is missing!');
}

// Set default filters
$searchQuery = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$assignmentField = isset($_GET['assignment_field']) ? $_GET['assignment_field'] : null;
$statType = isset($_GET['stat_type']) ? $_GET['stat_type'] : 'max'; 
$threshold = isset($_GET['threshold']) ? $_GET['threshold'] : null; 

// Define the available assignment fields
$assignmentFields = [
    'Interaction' => 'Interaction',
    'Text_Analysis' => 'Text Analysis',
    'Text_Production' => 'Text Production',
    'Investigation_Task_Part_A' => 'Investigation Task Part A',
    'Investigation_Task_Part_B' => 'Investigation Task Part B',
    'Oral_Presentation' => 'Oral Presentation',
    'Response_Japanese' => 'Response Japanese',
    'Response_English' => 'Response English',
];

// Build the query for students
if ($role == 'Teacher') {
    $query = "
        SELECT u.UserID, u.Name, u.Course 
        FROM users u
        WHERE u.Role IN ('Stage1Students', 'Stage2Students') 
        AND u.CourseID = ? ";

    
    if (!empty($searchQuery)) {
        $query .= "AND (u.Name LIKE ? OR u.Course LIKE ?) ";
    }

    $stmt = $config->prepare($query);

    // Bind parameters conditionally
    if (!empty($searchQuery)) {
        $stmt->bind_param("iss", $CourseID, $searchQueryWildcard, $searchQueryWildcard);
        $searchQueryWildcard = "%$searchQuery%";
    } else {
        $stmt->bind_param("i", $CourseID);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the selected statistic (max, min, avg, student count, input count, or threshold)
    if ($assignmentField && array_key_exists($assignmentField, $assignmentFields)) {
        if ($statType == 'max') {
            // Query to get the student with the highest marks
            $statQuery = $config->prepare("
                SELECT u.Name, g.$assignmentField AS Grade
                FROM gradings g
                JOIN users u ON u.UserID = g.StudentID
                WHERE g.$assignmentField IS NOT NULL
                ORDER BY g.$assignmentField DESC
                LIMIT 1
            ");
        } elseif ($statType == 'min') {
            // Query to get the student with the lowest marks
            $statQuery = $config->prepare("
                SELECT u.Name, g.$assignmentField AS Grade
                FROM gradings g
                JOIN users u ON u.UserID = g.StudentID
                WHERE g.$assignmentField IS NOT NULL
                ORDER BY g.$assignmentField ASC
                LIMIT 1
            ");
        } elseif ($statType == 'avg') {
            // Query to get the average marks for the assignment field
            $statQuery = $config->prepare("
                SELECT AVG(g.$assignmentField) AS Grade
                FROM gradings g
                WHERE g.$assignmentField IS NOT NULL
            ");
        } elseif ($statType == 'student_count') {
            // Query to get the number of students
            $statQuery = $config->prepare("
                SELECT COUNT(DISTINCT g.StudentID) AS StudentCount
                FROM gradings g
                WHERE g.$assignmentField IS NOT NULL
            ");
        } elseif ($statType == 'input_count') {
            // Query to get the number of inputs (non-null values for the field)
            $statQuery = $config->prepare("
                SELECT COUNT(g.$assignmentField) AS InputCount
                FROM gradings g
                WHERE g.$assignmentField IS NOT NULL
            ");
        } elseif ($statType == 'threshold' && $threshold !== null) {
            // Query to get all students above or equal to the threshold
            $statQuery = $config->prepare("
                SELECT u.Name, g.$assignmentField AS Grade
                FROM gradings g
                JOIN users u ON u.UserID = g.StudentID
                WHERE g.$assignmentField IS NOT NULL
                AND g.$assignmentField >= ?
            ");
            $statQuery->bind_param("d", $threshold);
        }

        if (isset($statQuery)) {
            $statQuery->execute();
            $statResult = $statQuery->get_result();
            $statData = [];
            while ($row = $statResult->fetch_assoc()) {
                $statData[] = $row;  
            }
        }
    }

} else {
    // Fetch only the current user's details, including their Name
    $query = "SELECT UserID, Name, Course FROM users WHERE Username = ?";
    $stmt = $config->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
}

if (!$result) {
    die('Error in query: ' . mysqli_error($config));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profiles</title>
    <link rel="stylesheet" href="Profile.css">
    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="statistics.css"> 
</head>
<body>
<?php include 'navbar.php'; ?>

<main>
    <h1>Students Enrolled</h1>

    <!-- Search Form (Visible only to Teachers and Admins) -->
    <?php if ($role == 'Teacher' || $role == 'Admin'): ?>
    <div class="form-container">
        <form method="GET" action="">
            <label for="search_query">Search by Name or Course:</label>
            <input type="text" id="search_name" name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Enter student name or course">
            <button type="submit" class="btn-primary_search">Search</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Student Table -->
    <table>
        <thead>
            <tr>
                <th>Names</th>
                <th>Course</th>
                <th>Profiles</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <!-- Display the Name of the student -->
                    <td><?php echo htmlspecialchars($row['Name'] ?? 'Unnamed User'); ?></td>
                    <td><?php echo htmlspecialchars($row['Course']); ?></td>
                    <td><a href="StudentProfile.php?UserID=<?php echo $row['UserID']; ?>">View</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Assignment Statistics Section (Only visible to teachers) -->
    <?php if ($role == 'Teacher'): ?>
    <div class="assignment-stats-container">
        <h2>Assignment Statistics</h2>
        <div class="form-container">
            <form method="GET" action="">
                <label for="assignment_field">Assignment Field:</label>
                <select name="assignment_field" id="assignment_field">
                    <option value=""> Select Assignment Field </option>
                    <?php foreach ($assignmentFields as $field => $label): ?>
                        <option value="<?php echo $field; ?>" <?php if ($field == $assignmentField) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="stat_type">Select Statistic:</label>
                <select name="stat_type" id="stat_type">
                    <option value="max" <?php if ($statType == 'max') echo 'selected'; ?>>Maximum</option>
                    <option value="min" <?php if ($statType == 'min') echo 'selected'; ?>>Minimum</option>
                    <option value="avg" <?php if ($statType == 'avg') echo 'selected'; ?>>Average</option>
                    <option value="student_count" <?php if ($statType == 'student_count') echo 'selected'; ?>>Number of Students</option>
                    <option value="input_count" <?php if ($statType == 'input_count') echo 'selected'; ?>>Number of Inputs</option>
                    <option value="threshold" <?php if ($statType == 'threshold') echo 'selected'; ?>>Threshold Filter</option>
                </select>

                <!-- Show input for threshold if 'threshold' is selected -->
                <?php if ($statType == 'threshold'): ?>
                    <label for="threshold">Enter Threshold:</label>
                    <input type="number" step="0.01" name="threshold" value="<?php echo htmlspecialchars($threshold); ?>">
                <?php endif; ?>

                <button type="submit" class="btn-primary_search">Search</button>
            </form>
        </div>

        <!-- Display the selected statistics -->
        <?php if ($assignmentField && isset($statData)): ?>
            <div class="stats-results">
                <h3><?php echo $assignmentFields[$assignmentField]; ?> Statistics</h3>

                <!-- Display for max/min/average -->
                <?php if ($statType == 'max' || $statType == 'min' || $statType == 'avg'): ?>
                    <p><strong><?php echo ucfirst($statType); ?>:</strong> 
                    <?php echo $statData[0]['Grade'] ?? 'N/A'; ?>
                    </p>
                    <?php if ($statType == 'max' || $statType == 'min'): ?>
                        <p><strong>Student:</strong> <?php echo $statData[0]['Name'] ?? 'N/A'; ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Display for threshold: show all students who meet the threshold -->
                <?php if ($statType == 'threshold'): ?>
                    <h4>Students scoring above or equal to <?php echo $threshold; ?>:</h4>
                    <ul>
                        <?php foreach ($statData as $student): ?>
                            <li><?php echo $student['Name']; ?> - <?php echo $student['Grade']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<?php include 'footer.php'; ?>

<script>
    function toggleMenu() {
        const nav = document.querySelector('.main-nav');
        nav.classList.toggle('active');
    }
</script>
</body>
</html>
