<?
require_once '../include/Global.php';
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$id = false;

if (isset($_REQUEST['id']))
   $id = intval($_REQUEST['id']);
else
   die('id required');

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.getThread/$id/";
chdir('../service/');
include '../service/json.php';