<?
// WinChatty Server
// Copyright (c) 2013 Brian Luft
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
// documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
// Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
// OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once 'Global.php';

function root_post_result($row)
{
   return array(
      'id' => intval($row[0]),
      'date' => nsc_date(strtotime($row[1])),
      'author' => strval($row[2]),
      'category' => nsc_flagIntToString($row[3]),
      'body' => strval($row[4]),
      'postCount' => intval($row[5]),
      'isParticipant' => intval($row[6]) > 0
   );
}

$pg = nsc_initJsonGet();
$offset = nsc_getArg('offset', 'INT?', 0);
$limit  = nsc_getArg('limit', 'INT?', 40);
$username = nsc_getArg('username', 'STR?', '');
$date = nsc_getArg('date', 'DAT?', 0);
$expiration = 18;

$rootPosts = array();
$totalThreadCount = 0;

if (!empty($date)) 
{
   $rs = nsc_getRootPostsFromDay($pg, $date, $username);
   $allRootPosts = array_map('root_post_result', $rs);
   $totalThreadCount = count($allRootPosts);
   for ($i = $offset; $i < $offset + $limit && $i < count($allRootPosts); $i++)
      $rootPosts[] = $allRootPosts[$i];
}
else
{
   $allThreadIds = nsc_getActiveThreadIds($pg, $expiration);
   $totalThreadCount = count($allThreadIds);
   $threadIds = array();
   for ($i = $offset; $i < $offset + $limit && $i < count($allThreadIds); $i++)
      $threadIds[] = $allThreadIds[$i];

   foreach ($threadIds as $threadId) 
   {
      $row = nsc_selectRow($pg, 
         'SELECT id, date, author, category, body, ' .
         '(SELECT COUNT(*) FROM post AS p2 WHERE p2.thread_id = post.id) AS post_count, ' .
         '(SELECT COUNT(*) FROM post AS p3 WHERE p3.thread_id = post.id AND p3.author_c = $1) AS participant_count ' .
         'FROM post WHERE id = $2',
         array(strtolower($username), $threadId));

      $rootPosts[] = root_post_result($row);
   }
}

echo json_encode(array('totalThreadCount' => $totalThreadCount, 'rootPosts' => $rootPosts));
