<?php
declare(strict_types=1);

function finish_with(string $result)
{
    header('Location: ./?' . $result . '=1', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!empty($_POST['website'] ?? '')) {
    finish_with('sent');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if (
    $name === '' || $message === '' ||
    strlen($name) > 120 || strlen($email) > 254 || strlen($message) > 5000 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    preg_match('/[\r\n]/', $email)
) {
    finish_with('error');
}

$safeName = preg_replace('/[\r\n]+/', ' ', $name) ?: 'Website visitor';
$subject = 'Darwin River Quarries website enquiry';
$body = "A new enquiry was submitted through darwinriverquarries.com.au.\n\n";
$body .= "Name: {$safeName}\n";
$body .= "Email: {$email}\n\n";
$body .= "Message:\n{$message}\n";

$headers = [
    'From: Darwin River Quarries Website <website@darwinriverquarries.com.au>',
    'Reply-To: ' . $safeName . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . PHP_VERSION,
];

$sent = mail('info@drqnt.com.au', $subject, $body, implode("\r\n", $headers));
finish_with($sent ? 'sent' : 'error');
