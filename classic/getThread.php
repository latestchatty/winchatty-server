<?
require_once '../include/Global.php';

$id = false;

if (isset($_REQUEST['id']))
   $id = intval($_REQUEST['id']);
else
   die('id required');

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.getThread/$id/";
chdir('../service/');
include '../service/json.php';