<?
require_once '../include/Global.php';
header('Content-type: application/json');

$id = false;
if (isset($_GET['id']))
   $id = intval($_GET['id']);
else
   die('id required');

$pg = nsc_connectToDatabase();

$threadId = nsc_selectValue($pg, 'SELECT thread_id FROM post WHERE id = $1', array($id));
$rs = nsc_query($pg, 'SELECT id, parent_id, author, category, date, body FROM post WHERE thread_id = $1', array($id));

$posts = array();
$participants = array();
$replyCount = 0;
foreach ($rs as $row)
{
   $author = strval($row[2]);

   $posts[] = array(
      'id' => intval($row[0]),
      'parent_id' => intval($row[1]),
      'author' => $author,
      'category' => intval($row[3]),
      'date' => strval($row[4]),
      'body' => strval($row[5])
   );

   $replyCount++;
   if (isset($participants[$author]))
      $participants[$author]['post_count']++;
   else
      $participants[$author] = array('username' => $author, 'post_count' => 1);
}

$rootPost = buildComment($threadId, $posts);
$rootPost['last_reply_id'] = 0;
$rootPost['reply_count'] = $replyCount;
$rootPost['participants'] = $participants;

$response = array(
   'story_id' => 0,
   'last_page' => 1,
   'page' => 1,
   'story_name' => 'WinChatty',
   'comments' => array($rootPost)
);

echo json_encode($response);

nsc_disconnectFromDatabase($pg);


#--------------------------------------------------------------------------------------------------


function buildComment($id, $posts)
{
   $post = false;

   foreach ($posts as $x)
      if ($x['id'] == $id)
         $post = $x;
   if ($post === false)
      die(json_encode(array('error' => "Post not found.")));

   $category = nsc_flagIntToString($post['category']);

   $preview = substr(strip_tags(nsc_previewFromBody($post['body'])), 0, 100);
   $date = date('M d, Y g:ia T', strtotime($post['date']));  #"Dec 01, 2013 5:43pm PST"

   $comments = array();
   foreach (getReplies($id, $posts) as $reply)
      $comments[] = buildComment($reply['id'], $posts);

   return array(
      'category' => $category,
      'id' => $id,
      'author' => $post['author'],
      'preview' => $preview,
      'date' => $date,
      'body' => $post['body'],
      'comments' => $comments
   );
}

function getReplies($parentId, $posts)
{
   $replies = array();
   foreach ($posts as $post)
      if ($post['parent_id'] == $parentId)
         $replies[intval($post['id'])] = $post;
   ksort($replies);
   return $replies;   
}
