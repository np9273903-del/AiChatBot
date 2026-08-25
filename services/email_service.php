<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Sends a real email via PHP's built-in mail().
 * NOTE: mail() requires the server to have a working MTA (sendmail/postfix) configured.
 * For local dev without a mail server, swap this out for PHPMailer + SMTP -
 * every call site in this app just calls send_email(), so only this function needs to change.
 */
function send_email($toEmail, $subject, $bodyHtml) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";

    // mail() returns true if the message was accepted for delivery, not that it was delivered.
    return @mail($toEmail, $subject, $bodyHtml, $headers);
}

function send_welcome_email($toEmail, $username) {
    $subject = 'Welcome to ' . APP_NAME;
    $body = "<p>Hi {$username},</p><p>Your " . APP_NAME . " account has been created with this email address.</p><p>Happy building!</p>";
    return send_email($toEmail, $subject, $body);
}

function send_added_to_project_email($toEmail, $projectName, $addedByEmail) {
    $subject = "You were added to project \"{$projectName}\" on " . APP_NAME;
    $body = "<p>{$addedByEmail} added you to the project <strong>{$projectName}</strong> on " . APP_NAME . ".</p>"
          . "<p>Log in to start chatting: <a href=\"" . APP_URL . "/home.php\">" . APP_URL . "/home.php</a></p>";
    return send_email($toEmail, $subject, $body);
}

/**
 * Real-time mention notification: fired the moment a chat message @mentions
 * a project member by their email/username, in case they're not online right now.
 */
function send_mention_email($toEmail, $projectName, $fromEmail, $messageText) {
    $subject = "{$fromEmail} mentioned you in \"{$projectName}\" on " . APP_NAME;
    $safeMsg = htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8');
    $body = "<p><strong>{$fromEmail}</strong> mentioned you in project <strong>{$projectName}</strong>:</p>"
          . "<blockquote style=\"margin:8px 0;padding:8px 12px;border-left:3px solid #6c5ce7;background:#f5f5f8;\">{$safeMsg}</blockquote>"
          . "<p><a href=\"" . APP_URL . "/home.php\">Open " . APP_NAME . "</a></p>";
    return send_email($toEmail, $subject, $body);
}
