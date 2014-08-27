<?
require_once '../include/Global.php';
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

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

$parentID = $_REQUEST['parent_id'];
$storyID = 0;
$body = $_REQUEST['body'];

try
{
   if ($parentID == 0)
      $parentID = '';

   $ret = ChattyParser()->post($username, $password, $parentID, $storyID, $body);

   if (strstr($ret, 'You must be logged in to post') !== false)
      die('error_login_failed');
   else if (strstr($ret, 'Please wait a few minutes before trying to post again.') !== false)
      die('error_post_rate_limit');
   else if (strstr($ret, 'fixup_postbox_parent_for_remove(') !== false)
      sleep(5);
   else
      throw new Exception($ret);
}
catch (Exception $e)
{
   die('error_post_failed');
}

#$_SERVER['PHP_SELF'] = "/service/json.php/ChattyService.post/$username/$password/$parentID/$storyID/$body";
#chdir('../service/');
#include '../service/json.php';
