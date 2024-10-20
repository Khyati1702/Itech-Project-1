<?php
session_start();
require 'configure.php'; 
require 'mailer.php';   


//This page is a form for sending email with the attachment and it uses the SMTP email to send the emails, which is available in the .env file. 

// Checking user role
if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'Teacher') {
    header('Location: LoginPage.php');
    exit();
}



// Handling the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail = $_POST['student_email'];
    $toName = $_POST['student_name']; 
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    
    // Handling the file attachment
    $attachment = null;
    $attachmentName = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $attachment = file_get_contents($_FILES['attachment']['tmp_name']);
        $attachmentName = $_FILES['attachment']['name'];
    }

    // Sending the email
    $emailError = sendEmail($toEmail, $toName, $subject, $body, $attachment, $attachmentName);

    if ($emailError === true) {
        echo "Email sent successfully!";
    } else {
        echo "Failed to send email. Error: " . $emailError; 
    }
}


$students = [];
$query = "SELECT UserID, Name, GoogleEmail FROM users WHERE Role IN ('Stage1Students', 'Stage2Students')";
$result = mysqli_query($config, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = [
            'email' => $row['GoogleEmail'], 
            'name' => $row['Name']
        ];
    }
} else {
    die("Database query failed: " . mysqli_error($config)); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email</title>

    <link rel="stylesheet" href="colors.css">
    <link rel="stylesheet" href="teacher_email.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <h1>Send Email to Students</h1>


    <form method="POST" enctype="multipart/form-data">
   
        <label for="student_email">Select Student:</label>
        <select name="student_email" id="student_email" onchange="document.getElementById('student_name').value=this.options[this.selectedIndex].text;">
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['email']; ?>"><?php echo $student['name']; ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">No students available</option>
            <?php endif; ?>
        </select>

 
        <input type="hidden" name="student_name" id="student_name">


        <label for="subject">Email Subject:</label>
        <input type="text" name="subject" id="subject" required>

        <label for="body">Email Body:</label>
        <textarea name="body" id="body" required></textarea>


        <label for="attachment">Attachment (optional):</label>
        <input type="file" name="attachment" id="attachment">

        <button type="submit">Send Email</button>
    </form>


    <?php include 'footer.php'; ?>
</body>
</html>
