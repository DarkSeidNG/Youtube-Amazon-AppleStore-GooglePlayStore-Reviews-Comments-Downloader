<?php
require '/includes/class.phpmailer.php';
$mail = new PHPMailer;

$mail->setFrom('chrisifwax101@gmail.com', 'Your Name');
$mail->addAddress('ifwaxtel@gmail.com', 'My Friend');
$mail->Subject  = 'First PHPMailer Message';
$mail->Body     = 'Hi! This is my first e-mail sent through PHPMailer.';

if(!$mail->send()) {
  echo 'Message was not sent.';
  echo 'Mailer error: ' . $mail->ErrorInfo;
} else {
  echo 'Message has been sent.';
}