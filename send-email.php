<?php
require "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// ✅ Custom validation function
function validatex($data) {
    $data = trim($data);              // Remove whitespace
    $data = stripslashes($data);      // Remove backslashes
    $data = htmlspecialchars($data);  // Escape HTML special characters
    return $data;
}

// ✅ Safely collect POST data
$name    = validatex($_POST['name'] ?? '');
$email   = validatex($_POST['email'] ?? '');
$subject = validatex($_POST['subject'] ?? '');
$message = validatex($_POST['message'] ?? '');

// ✅ Basic validation checks
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    die("Error: All fields are required.");
}

// ✅ Email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format.");
}

$mail = new PHPMailer(true);

try {
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for debugging

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Use your app password (not your regular Gmail password)
    $mail->Username = "smartrashbin.system@gmail.com";
    $mail->Password = "svqjvkmdkdedjbia";

    // ✅ Additional security: sanitize email headers
    $safe_name = preg_replace('/[\r\n]+/', ' ', $name);
    $safe_email = filter_var($email, FILTER_SANITIZE_EMAIL);

    $mail->setFrom($safe_email, $safe_name);
    $mail->addAddress("smartrashbin.system@gmail.com", "Smart Trashbin");

    // Prevent email header injection
    $safe_subject = preg_replace("/[\r\n]+/", " ", $subject);
    $mail->Subject = $safe_subject;
    $mail->Body = nl2br($message);
    $mail->AltBody = strip_tags($message);

    // Send email
    $mail->send();

    // Redirect to confirmation page
    header("Location: sent.php");
    exit;

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
