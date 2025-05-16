<?php

require_once 'utils.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


function sendEmail($to, $subject, $body, $plainText = '', $attachments = []) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Your SMTP server not Gmail necessarily
        $mail->SMTPAuth   = true;
        $mail->Username   = ''; // Your SMTP username
        $mail->Password   = ''; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('', 'FSO'); // Your email address

        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;


        if (!empty($plainText)) {
            $mail->AltBody = $plainText;
        }

        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }

        $mail->send();

        return [
            'success' => true,
            'message' => 'Email has been sent successfully'
        ];

    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);

        return [
            'success' => false,
            'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"
        ];
    }
}

?>