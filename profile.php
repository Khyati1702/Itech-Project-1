<?php
session_start();
if (!isset($_SESSION['Username'])) {
    header('Location: LoginPage.php');
    exit();
}

include 'configure.php';

$username = $_SESSION['Username'];
$role = $_SESSION['Role'];

if ($role == 'Teacher') {
    $query = "SELECT UserID, Username, Role FROM users WHERE Role IN ('Stage1Students', 'Stage2Students')";
} else {
    $query = "SELECT UserID, Username, Role FROM users WHERE Username='$username'";
}

$result = mysqli_query($config, $query);
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
<header class="main-header">
    <div class="logo-container">
        <img class="header-title" src="Images/REAL_SACE.png" alt="SACE Portal Logo">
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
        <h1>Students Enrolled</h1>
        <table>
            <thead>
                <tr>
                    <th>Names</th>
                    <th>Roles</th>
                    <th>Profiles</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Username']); ?></td>
                        <td><?php echo htmlspecialchars($row['Role']); ?></td>
                        <td><a href="StudentProfile.php?UserID=<?php echo $row['UserID']; ?>">View</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
        <img src="Images/REAL_SACE.png" alt="SACE Portal Logo">
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
