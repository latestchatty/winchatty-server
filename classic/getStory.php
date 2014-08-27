<?
require_once '../include/Global.php';
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$page = 1;
$story = 0;

if (isset($_REQUEST['page']))
   $page = $_REQUEST['page'];

if (isset($_REQUEST['story']))
   $story = $_REQUEST['story'];

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.getStory/$story/$page/";
chdir('../service/');
include '../service/json.php';
