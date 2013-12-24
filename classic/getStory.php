<?
require_once '../include/Global.php';

$page = 1;
$story = 0;

if (isset($_REQUEST['page']))
   $page = $_REQUEST['page'];

if (isset($_REQUEST['story']))
   $story = $_REQUEST['story'];

$_SERVER['PHP_SELF'] = "/service/json.php/ClassicService.getStory/$story/$page/";
chdir('../service/');
include '../service/json.php';

/*$pg = nsc_initJsonGet();
$page = nsc_postArg('page', 'INT?', 1);

$threadsPerPage = 40;
$offset = $threadsPerPage * ($page - 1);
$limit = $threadsPerPage;

$comments = array();
$lastPage = 1;
$expiration = 18;  # Matches Shacknews

$rows = nsc_query($pg, 
   "SELECT id FROM thread WHERE date > (NOW() - interval '$expiration hours') ORDER BY bump_date DESC LIMIT \$1",
   array($limit));
$threads = array();
foreach ($rows as $row)
   $threads[] = array('threadId' => intval($row[0]), 'date' => nsc_date(strtotime($row[1])));




echo json_encdoe(array(
   'page' => intval($page),
   'comments' => $comments,
   'story_name' => 'WinChatty API',
   'story_id' => '17',
   'last_page' => $lastPage
));*/