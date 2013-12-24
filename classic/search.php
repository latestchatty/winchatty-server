<?
require_once '../include/Global.php';

$author = urlencode(isset($_REQUEST['author']) ? $_REQUEST['author'] : '');
$parent = urlencode(isset($_REQUEST['parent_author']) ? $_REQUEST['parent_author'] : '');
$terms  = urlencode(isset($_REQUEST['terms']) ? $_REQUEST['terms'] : '');
$page   = urlencode(isset($_REQUEST['page']) ? $_REQUEST['page'] : '1');

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.search/$terms/$author/$parent/$page/";
chdir('../service/');
include '../service/json.php';