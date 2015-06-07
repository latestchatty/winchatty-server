<?
# WinChatty Server
# Copyright (C) 2013 Brian Luft
# 
# This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public 
# License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later 
# version.
# 
# This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
# warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
# details.
# 
# You should have received a copy of the GNU General Public License along with this program; if not, write to the Free 
# Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

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
