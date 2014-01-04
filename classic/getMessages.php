<?
require_once '../include/Global.php';

$username = false;
$password = false;

if (!isset($_SERVER['PHP_AUTH_USER'])) 
{
   header('WWW-Authenticate: Basic realm="WinChatty Server"');
   header('HTTP/1.0 401 Unauthorized');
   die('Unable to log in.');
}
else
{
   $username = $_SERVER['PHP_AUTH_USER'];
   $password = $_SERVER['PHP_AUTH_PW'];
}

$adapter = new ClassicAdapter();
try
{
   $result = $adapter->getMessages($username, $password);
}
catch (Exception $e)
{
   die('error_get_failed');
}

echo json_encode($result);