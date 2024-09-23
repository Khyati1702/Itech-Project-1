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

// Fetch the CourseID for the logged-in user (either a teacher or a student)
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

// Build the query for students
if ($role == 'Teacher') {
    $query = "
        SELECT u.UserID, u.Name, u.Course 
        FROM users u
        WHERE u.Role IN ('Stage1Students', 'Stage2Students') 
        AND u.CourseID = ? ";

    // Modify the query to search by name or course with the same search input
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

    // Fetch groups for the teacher
    $groupQuery = $config->prepare("
        SELECT groups.GroupID, groups.GroupName, COUNT(group_members.StudentID) AS StudentCount 
        FROM groups
        LEFT JOIN group_members ON groups.GroupID = group_members.GroupID
        WHERE groups.TeacherID = ?
        GROUP BY groups.GroupID
    ");
    $groupQuery->bind_param("i", $_SESSION['UserID']);
    $groupQuery->execute();
    $groupResult = $groupQuery->get_result();
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
</head>
<body>
<?php include 'navbar.php'; ?>

<main>
    <h1>Students Enrolled</h1>

    <!-- Search Form (Visible only to Teachers and Admins) -->
    <?php if ($role == 'Teacher' || $role == 'Admin'): ?>
    <form method="GET" action="">
        <label class="Search_name" for="search_query">Search by Name or Course:</label>
        <input type="text" id="search_name" name="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Enter student name or course">
        <button type="submit" class="btn-primary_search">Search</button>
    </form>
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

    <?php if ($role == 'Teacher' || $role == 'Admin'): ?>
        <div class="group-management">
            <h2>Manage Student Groups</h2>
            <div class="group-input-container">
                <form action="create_group.php" method="POST">
                    <input type="text" name="GroupName" placeholder="Enter Group Name" required>
                    <button type="submit" class="btn-primary">Create Group</button>
                </form>
            </div>

            <h3>Your Groups</h3>
            <table>
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Number of Students</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($group = $groupResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group['GroupName']); ?></td>
                            <td><?php echo htmlspecialchars($group['StudentCount']); ?></td>
                            <td><a href="manage_group.php?GroupID=<?php echo $group['GroupID']; ?>">Manage</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
