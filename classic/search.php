<?
require_once '../include/Global.php';

$author = isset($_REQUEST['author']) ? $_REQUEST['author'] : '';
$parent = isset($_REQUEST['parent_author']) ? $_REQUEST['parent_author'] : '';
$terms  = isset($_REQUEST['terms']) ? $_REQUEST['terms'] : '';
$page   = isset($_REQUEST['page']) ? $_REQUEST['page'] : '1';
die(json_encode(ClassicAdapter::search($terms, $author, $parent, $page)));
