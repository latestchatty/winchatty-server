<?
require_once '../include/Global.php';
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$page = 1;

if (isset($_GET['page']))
   $page = intval($_GET['page']);

echo json_encode(FrontPageParser()->getStories($page));