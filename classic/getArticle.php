<?
require_once '../include/Global.php';

$story = 0;

if (isset($_REQUEST['story']))
   $story = $_REQUEST['story'];

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.getArticle/$story/";
chdir('../service/');
include '../service/json.php';