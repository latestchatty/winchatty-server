<?
require_once '../include/Global.php';
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$story = 0;

if (isset($_REQUEST['story']))
   $story = $_REQUEST['story'];

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.getArticle/$story/";
chdir('../service/');
include '../service/json.php';