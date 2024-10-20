<?php



//This page is the PRofile page , where it contains list of all the students and search bar and filtering for maximum, minimum filters. 

session_start();
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

$username = $_SESSION['Username'];
$role = $_SESSION['Role'];

// Checking the course id for the user
$courseQuery = $config->prepare("SELECT CourseID FROM users WHERE Username = ?");
$courseQuery->bind_param("s", $username);
$courseQuery->execute();
$courseResult = $courseQuery->get_result();
$courseData = $courseResult->fetch_assoc();
$CourseID = $courseData['CourseID'] ?? null;

if (!$CourseID) {
    die('Course ID is missing!');
}

// Setting the defalt user filter
$searchQuery = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$assignmentField = isset($_GET['assignment_field']) ? $_GET['assignment_field'] : null;
$statType = isset($_GET['stat_type']) ? $_GET['stat_type'] : 'max';
$threshold = isset($_GET['threshold']) ? $_GET['threshold'] : null;

// The assignments for the filters
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

// Build the query to fetch students based on the teacher's course and optional search query
if ($role == 'Teacher') {
    $query = "
        SELECT u.UserID, u.Name, u.Course 
        FROM users u
        WHERE u.Role IN ('Stage1Students', 'Stage2Students') 
        AND u.CourseID = ? 
    ";

    // Append search conditions if a search query is provided
    if (!empty($searchQuery)) {
        $query .= "AND (u.Name LIKE ? OR u.Course LIKE ?) ";
    }

    $stmt = $config->prepare($query);

    // Bind parameters based on whether a search query is provided
    if (!empty($searchQuery)) {
        $searchQueryWildcard = "%$searchQuery%";
        $stmt->bind_param("iss", $CourseID, $searchQueryWildcard, $searchQueryWildcard);
    } else {
        $stmt->bind_param("i", $CourseID);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch statistics based on the selected assignment field and statistic type
    if ($assignmentField && array_key_exists($assignmentField, $assignmentFields)) {
        if ($statType == 'max') {
            // Get the student with the highest mark in the selected assignment
            $statQuery = $config->prepare("
                SELECT u.Name, g.$assignmentField AS Grade
                FROM gradings g
                JOIN users u ON u.UserID = g.StudentID
                WHERE g.$assignmentField IS NOT NULL
                ORDER BY g.$assignmentField DESC
                LIMIT 1
            ");
        } elseif ($statType == 'min') {
            // Get the student with the lowest mark in the selected assignment
            $statQuery = $config->prepare("
                SELECT u.Name, g.$assignmentField AS Grade
                FROM gradings g
                JOIN users u ON u.UserID = g.StudentID
                WHERE g.$assignmentField IS NOT NULL
                ORDER BY g.$assignmentField ASC
                LIMIT 1
            ");
        } elseif ($statType == 'avg') {
            // Calculate the average mark for the selected assignment
            $statQuery = $config->prepare("
                SELECT AVG(g.$assignmentField) AS Grade
                FROM gradings g
                WHERE g.$assignmentField IS NOT NULL
            ");
        } elseif ($statType == 'threshold' && $threshold !== null) {
            // Get students who scored above or equal to the threshold
            $statQuery = $config->prepare("
                SELECT u.Name, g.$assignmentField AS Grade
                FROM gradings g
                JOIN users u ON u.UserID = g.StudentID
                WHERE g.$assignmentField IS NOT NULL
                AND g.$assignmentField >= ?
            ");
            $statQuery->bind_param("d", $threshold);
        }

        // Execute the statistics query if it's set
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

    <?php if ($role == 'Teacher'): ?>
        <div class="report-buttons">
            <form action="generate_all_current_reports.php" method="POST">
                <button type="submit" class="download_report_btn">Generate All Current Reports</button>
            </form>
            <form action="generate_all_stage2_reports.php" method="POST">
                <button type="submit" class="download_report_btn">Generate All Final Reports</button>
            </form>
        </div>

       
        <div class="assignment-stats-container">
          
            <div class="form-container">
                <form method="GET" action="">
                    <label for="assignment_field" class="Lable">Assignment Field:</label>
                    <select name="assignment_field" id="assignment_field">
                        <option value=""> Select Assignment Field </option>
                        <?php foreach ($assignmentFields as $field => $label): ?>
                            <option value="<?php echo $field; ?>" <?php if ($field == $assignmentField) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="stat_type" class="Lable">Select Statistic:</label>
                    <select name="stat_type" id="stat_type">
                        <option value="max" <?php if ($statType == 'max') echo 'selected'; ?>>Maximum</option>
                        <option value="min" <?php if ($statType == 'min') echo 'selected'; ?>>Minimum</option>
                        <option value="avg" <?php if ($statType == 'avg') echo 'selected'; ?>>Average</option>
                        <option value="threshold" <?php if ($statType == 'threshold') echo 'selected'; ?>>Threshold Filter</option>
                    </select>

                    
                    <?php if ($statType == 'threshold'): ?>
                        <label for="threshold" class="Lable">Enter Threshold:</label>
                        <input type="number" step="0.01" name="threshold" value="<?php echo htmlspecialchars($threshold); ?>">
                    <?php endif; ?>

                    <button type="submit" class="btn-primary_search">Search</button>
                </form>
            </div>

        
            <?php if ($assignmentField && isset($statData)): ?>
                <div class="stats-results">
                    <h3><?php echo $assignmentFields[$assignmentField]; ?> Statistics</h3>

            
                    <?php if ($statType == 'max' || $statType == 'min' || $statType == 'avg'): ?>
                        <p><strong><?php echo ucfirst($statType); ?>:</strong> 
                        <?php echo $statData[0]['Grade'] ?? 'N/A'; ?>
                        </p>
                        <?php if ($statType == 'max' || $statType == 'min'): ?>
                            <p><strong>Student:</strong> <?php echo $statData[0]['Name'] ?? 'N/A'; ?></p>
                        <?php endif; ?>
                    <?php endif; ?>

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


    <?php if ($role == 'Teacher' || $role == 'Admin'): ?>
    <div class="form-container">
        <form method="GET" action="">
            <label for="search_query" class="Lable">Search by Name or Course:</label>
            <input type="text" id="search_name" name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Enter student name or course">
            <button type="submit" class="btn-primary_search">Search</button>
        </form>
    </div>
    <?php endif; ?>

 
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
                    <td><?php echo htmlspecialchars($row['Name'] ?? 'Unnamed User'); ?></td>
                    <td><?php echo htmlspecialchars($row['Course']); ?></td>
                    <td><a href="StudentProfile.php?UserID=<?php echo $row['UserID']; ?>">View</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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
