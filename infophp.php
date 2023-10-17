<?PHP
ini_set('display_errors', true);
error_reporting(E_ALL);

ini_set('smtp_port', 465);
 $message = "Line 1\r\nLine 2\r\nLine 3";

 // In case any of our lines are larger than 70 characters, we should use wordwrap()
 $message = wordwrap($message, 70, "\r\n");

 // Send
 mail('william@makecard.com.br', 'My Subject', $message);
 phpinfo();
