<?php
/**
 * Simple script to test mail configuration.
 * Run: php test_mail.php your-email@example.com
 */

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($argc < 2) {
    echo "Usage: php test_mail.php your-email@example.com\n";
    exit(1);
}

$to_email = $argv[1];

$from = $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@interntrack.online';
$from_name = $_ENV['MAIL_FROM_NAME'] ?? 'InternTrack API';

$subject = 'Test Email from InternTrack';
$message = "This is a test email to verify your mail configuration.\n\n";
$message .= "Mail settings:\n";
$message .= "MAIL_MAILER: " . ($_ENV['MAIL_MAILER'] ?? 'not set') . "\n";
$message .= "MAIL_HOST: " . ($_ENV['MAIL_HOST'] ?? 'not set') . "\n";
$message .= "MAIL_PORT: " . ($_ENV['MAIL_PORT'] ?? 'not set') . "\n";
$message .= "MAIL_USERNAME: " . ($_ENV['MAIL_USERNAME'] ?? 'not set') . "\n";
$message .= "MAIL_ENCRYPTION: " . ($_ENV['MAIL_ENCRYPTION'] ?? 'not set') . "\n";

$headers = "From: $from_name <$from>\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

echo "Attempting to send email to: $to_email\n";
echo "From: $from_name <$from>\n";
echo "\n";

if (mail($to_email, $subject, $message, $headers)) {
    echo "✓ Email sent successfully!\n";
    echo "Check your inbox (and spam folder) at: $to_email\n";
} else {
    echo "✗ Failed to send email.\n";
    echo "\nPossible issues:\n";
    echo "1. SendGrid API key might be invalid or expired\n";
    echo "2. Mail server settings might be incorrect\n";
    echo "3. PHP mail() function might not be configured\n";
    echo "\nCheck your .env file and SendGrid dashboard.\n";
}
