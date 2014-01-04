<?
require_once '../include/Global.php';

$username = false;
$password = false;

if (!isset($_SERVER['PHP_AUTH_USER'])) 
{
   header('WWW-Authenticate: Basic realm="WinChatty Server"');
   header('HTTP/1.0 401 Unauthorized');
   echo 'Cancelled.';
   exit;
} 
else 
{
   $username = $_SERVER['PHP_AUTH_USER'];
   $password = $_SERVER['PHP_AUTH_PW'];
}

$to = $_POST['to'];
$subject = $_POST['subject'];
$body = $_POST['body'];

try
{
   MessageParser()->sendMessage($username, $password, $to, $subject, $body);
}
catch (Exception $e)
{
   die('error_send_failed');
}

echo "OK";
