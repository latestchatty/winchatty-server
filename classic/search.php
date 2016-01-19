<?
require_once '../include/Global.php';
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$author = isset($_REQUEST['author']) ? $_REQUEST['author'] : '';
$parent = isset($_REQUEST['parent_author']) ? $_REQUEST['parent_author'] : '';
$terms  = isset($_REQUEST['terms']) ? $_REQUEST['terms'] : '';
$page   = isset($_REQUEST['page']) ? $_REQUEST['page'] : '1';
die(json_encode(ClassicAdapter::nusearch($terms, $author, $parent, $page)));
