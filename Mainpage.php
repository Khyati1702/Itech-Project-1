<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Page</title>
    <link rel="stylesheet" href="Mainpage.css">
    <link rel="stylesheet" href="colors.css">
</head>
<body>
    <header class="main-header">
        <div class="logo-container">
            <img class="header-title" src="Images/REAL_SACE.png" alt="SACE Portal Logo"> <!-- Changed "Eximages" to "Images" -->
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
    <h1 class="animated-text">いらっしゃいませ <?php echo htmlspecialchars($_SESSION['Username']); ?> <span class="emoji">&#128075;</span></h1>
        
        <section class="courses-grid">
            <!-- Course Card 1 -->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Background)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 1</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
            <!-- Course Card 2-->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Background)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 2</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
            <!-- Course Card 3 -->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Continuers)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 1</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
            <!-- Course Card 4 -->
            <article class="course-card">
                <header class="course-header background-blue">
                    <h2>SACE Japanese (Continuers)</h2>
                    <button>...</button>
                </header>
                <div class="course-body">
                    <p>Stage 2</p>
                    <div class="progress-bar" data-completed="25"></div>
                </div>
            </article>
        </section>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <div class="quick-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="Mainpage.php">Home</a></li>
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
            <img src="Images/REAL_SACE.png" alt="SACE Portal Logo"> <!-- Changed "Eximages" to "Images" -->
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
