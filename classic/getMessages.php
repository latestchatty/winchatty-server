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
   $username = urlencode($_SERVER['PHP_AUTH_USER']);
   $password = urlencode($_SERVER['PHP_AUTH_PW']);
}

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.getMessages/$username/$password/";
chdir('../service/');
include '../service/json.php';