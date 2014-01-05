<?
require_once '../include/Global.php';

$page = 1;

if (isset($_GET['page']))
   $page = intval($_GET['page']);

echo json_encode(FrontPageParser()->getStories($page));