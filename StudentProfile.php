<?php
session_start();
if (!isset($_SESSION['Username'])) {
    header('Location: experiment2.php');
    exit();
}

include 'configure.php';

if (isset($_GET['UserID'])) {
    $UserID = $_GET['UserID'];
    $query = "SELECT * FROM users WHERE UserID = ?";
    $stmt = $config->prepare($query);
    $stmt->bind_param("i", $UserID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} else {
    header('Location: Profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="StudentProfile.css">
    <link rel="stylesheet" href="colors.css">
</head>
<body>
    <header class="main-header">
        <div class="logo-container">
            <img class="header-title" src="Eximages/REAL_SACE.png" alt="SACE Portal Logo">
            <span class="header-title">SACE Portal</span>
        </div>
        <div class="nav-container">
            <span class="menu-toggle" onclick="toggleMenu()">☰</span>
            <nav class="main-nav">
            <a href="Mainpage.php">Home</a>
                <a href="assignment.php">Grading</a>
                <a href="Profile.php">Students</a>
                <a href="#">Contact</a>
                <a href="#">Help</a>
            </nav>
            <div class="search-container">
                <input type="search" placeholder="Search">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-button">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main>
        <h1>Student Profile</h1>
        <div class="profile-container">
            <div class="user-details">
                <h2>User details</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['Name']); ?></p>
                <p><strong>Email address:</strong> <?php echo htmlspecialchars($user['Username']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['Contact']); ?></p>
            </div>
            <div class="course-details">
                <h2>Course details</h2>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($user['Role']); ?></p>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($user['Course']); ?></p>
            </div>
            <div class="reports">
                <h2>Reports</h2>
                <ul>
                    <li><a href="student_performance.php?UserID=<?php echo $UserID; ?>">Performance</a></li>
                </ul>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <div class="quick-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Services</a></li>
                    <li><a href="#">Student Info</a></li>
                    <li><a href="#">Contacts</a></li>
                    <li><a href="#">Help</a></li>
                </ul>
            </div>
            <div class="contact-us">
                <h3>Contact Us</h3>
                <ul>
                    <li><a href="#">Instagram</a></li>
                    <li><a href="#">Facebook</a></li>
                    <li><a href="#">YouTube</a></li>
                </ul>
            </div>
            <div class="address">
                <h3>Address</h3>
                <p>Level 5/118 King William St<br>Adelaide, SA<br>Phone: (08) 5555 5555</p>
            </div>
        </div>
        <div class="footer-bottom">
            <img src="Eximages/REAL_SACE.png" alt="SACE Portal Logo">
            <p>&copy; SACE Student Portal</p>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const nav = document.querySelector('.main-nav');
            nav.classList.toggle('active');
            console.log('Menu toggled.');
        }
    </script>
</body>
</html>
