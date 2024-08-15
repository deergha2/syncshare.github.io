<?php
// Contact details
$to = 'deergha2904@gmail.com'; // Replace with your email address
$subject = 'Contact Form Submission';

// Collect form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate form data
if (empty($name) || empty($email) || empty($message)) {
    echo 'All fields are required.';
    exit;
}

// Sanitize email
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'Invalid email address.';
    exit;
}

// Prepare email content
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Create the email body
$body = "Name: $name\n";
$body .= "Email: $email\n\n";
$body .= "Message:\n$message";

// Send the email
if (mail($to, $subject, $body, $headers)) {
    echo 'Thank you for your message. We will get back to you soon.';
} else {
    echo 'Failed to send your message. Please try again later.';
}
?>
