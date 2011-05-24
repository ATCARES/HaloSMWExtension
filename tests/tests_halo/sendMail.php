<?php

/* Send mail to someone
 *
 * Usage:
 *
 *  php sendMail.php -t <recipient email address>
 *                   [ -s <subject> ]
 *                   [ -m <mail text> | -f <file with mail text> ]
 *                   [ -F <from adress> ]
 *                   [ -S <smtp server with port> ]
 *                   [ -U <smtp user> ]
 *                   [ -P <smtp password> ]
 *
 */

// get command line parameters
$args = $_SERVER['argv'];
while ($arg = array_shift($args)) {
    if ($arg == '-t')
        $to = array_shift($args) or die ("Error: missing value for -t\n");
    else if ($arg == '-s')
        $subject = array_shift($args) or die ("Error: missing value for -s\n");
    else if ($arg == '-m')
        $message = array_shift($args) or die ("Error: missing value for -m\n");
    else if ($arg == '-f') {
        $filename = array_shift($args) or die ("Error: missing value for -f\n");
        if (!file_exists($filename)) die ("Error: file doesn't exist\n");
        $message = file_get_contents($filename);
    }
    else if ($arg == '-F')
        $from = array_shift($args) or die ("Error: missing value for -F\n");
    else if ($arg == '-S') {
        $smtp= array_shift($args) or die ("Error: missing value for -S\n");
        if (preg_match('/:(\d+)$/', $smtp, $matches)) {
           $host = str_replace(':'.$matches[1], '', $smtp);
           $port = $matches[1];
        }
        else {
            $host = $smtp;
            $port = "465";
        }
    }
    else if ($arg == '-U') {
        $username= array_shift($args) or die ("Error: missing value for -U\n");
    }
    else if ($arg == '-P') {
        $password= array_shift($args) or die ("Error: missing value for -P\n");
    }
}
if (!isset($to)) die ("Error: no recipient given whom to send mail to\n");
if (!isset($subject)) $subject = "";
if (!isset($message)) $message = "";
if (strlen($message) == 0 && strlen($subject) == 0)
    die ("Error: empty subject and mail body\n");

// set from address to some default
if (! isset($from)) $from = "Hudson Buildserver <robotta@ontoprise.de>";

// send mail using the php mail command when we do not have smtp settings defined
if (! isset($smtp)) {
    $headers = 'From: '.trim($from). "\r\n";
    mail($to, $subject, $message, $headers);
    exit(0);
}

// smtp server config set, then use the Pear Mail package
require_once "Mail.php";

$headers = array ('From' => $from,
   'To' => $to,
   'Subject' => $subject);
$smtp = Mail::factory('smtp',
   array ('host' => $host,
     'port' => $port,
     'auth' => true,
     'username' => $username,
     'password' => $password));

$mail = $smtp->send($to, $headers, $message);

if (PEAR::isError($mail))
   echo("\n" . $mail->getMessage() );
else
   echo("\nMessage successfully sent!\n");
