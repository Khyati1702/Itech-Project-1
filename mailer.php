<?php
require 'vendor/autoload.php'; 
require 'configure.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

//This page contains the SMTP details for the emailing form. 


$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function sendEmail($toEmail, $toName, $subject, $body, $attachment = null, $attachmentName = 'attachment.pdf') {
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';   
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_EMAIL'];   
        $mail->Password   = $_ENV['SMTP_PASSWORD']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587; 

  
        $mail->setFrom($_ENV['SMTP_EMAIL'], $_ENV['FROM_NAME']); 
        $mail->addAddress($toEmail, $toName); 

        // Adding the attachment
        if ($attachment) {
            $mail->addStringAttachment($attachment, $attachmentName);
        }

        // Adding content such as body and subject
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        //Senfing the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        
        error_log("Email failed to send. PHPMailer Error: {$mail->ErrorInfo}");
        return $mail->ErrorInfo;
    }
}
?>
