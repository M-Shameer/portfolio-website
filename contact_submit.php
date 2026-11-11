
<?php

session_start();

require_once __DIR__ . '/config/database.php';

// Redirect back to the contact section with a message

function contactRedirect($type, $message) {

    $_SESSION['contact_flash'] = [
        'type' => $type,
        'message' => $message
    ];

    header("Location: index.php#contact");
    exit;
}

// Accept only POST requests

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php#contact");
    exit;
}

// Verify the security token

$submittedToken = $_POST['contact_token'] ?? '';
$sessionToken = $_SESSION['contact_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {

    contactRedirect(
        'error',
        'Your request could not be verified. Please refresh the page and try again.'
    );

}

// Spam protection: bots often fill hidden fields

$website = $_POST['website'] ?? '';

if (is_string($website) && trim($website) !== '') {

    contactRedirect(
        'success',
        'Thank you! Your message has been submitted.'
    );

}

// Get submitted information safely

$name = isset($_POST['sender_name']) && is_string($_POST['sender_name'])
    ? trim($_POST['sender_name'])
    : '';

$email = isset($_POST['sender_email']) && is_string($_POST['sender_email'])
    ? trim($_POST['sender_email'])
    : '';

$subject = isset($_POST['subject']) && is_string($_POST['subject'])
    ? trim($_POST['subject'])
    : '';

$message = isset($_POST['message']) && is_string($_POST['message'])
    ? trim($_POST['message'])
    : '';

// Validate required fields

if ($name === '' || $email === '' || $message === '') {

    contactRedirect(
        'error',
        'Please complete your name, email address and message.'
    );

}

// Validate field lengths

if (strlen($name) > 150) {

    contactRedirect(
        'error',
        'Your name must not exceed 150 characters.'
    );

}

if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

    contactRedirect(
        'error',
        'Please enter a valid email address.'
    );

}

if (strlen($subject) > 200) {

    contactRedirect(
        'error',
        'The subject must not exceed 200 characters.'
    );

}

if (strlen($message) > 5000) {

    contactRedirect(
        'error',
        'Your message must not exceed 5000 characters.'
    );

}

// Store message in MySQL

try {

    $stmt = $pdo->prepare(
        "INSERT INTO contact_messages
        (sender_name, sender_email, subject, message, status)
        VALUES
        (:sender_name, :sender_email, :subject, :message, 'new')"
    );

    $stmt->execute([

        ':sender_name' => $name,
        ':sender_email' => $email,
        ':subject' => $subject !== '' ? $subject : null,
        ':message' => $message

    ]);

    contactRedirect(
        'success',
        'Thank you! Your message has been sent successfully.'
    );

} catch (PDOException $e) {

    error_log("Contact message error: " . $e->getMessage());

    contactRedirect(
        'error',
        'Sorry, your message could not be submitted. Please try again.'
    );

}

?>
