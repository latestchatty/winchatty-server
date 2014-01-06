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
$pg = nsc_initJsonGet();
$offset = nsc_getArg('offset', 'INT?', 0);
$limit  = nsc_getArg('limit', 'INT?', 40);
$username = nsc_getArg('username', 'STR?', '');
$expiration = 18;

$threadIds = nsc_getActiveThreadIds($pg, $expiration, $limit, $offset);

$rootPosts = array();
foreach ($threadIds as $threadId)
{
   $row = nsc_selectRow($pg, 
      'SELECT id, date, author, category, body, ' .
      '(SELECT COUNT(*) FROM post AS p2 WHERE p2.thread_id = post.id) AS post_count, ' .
      '(SELECT COUNT(*) FROM post AS p3 WHERE p3.thread_id = post.id AND p3.author_c = $1) AS participant_count ' .
      'FROM post WHERE id = $2',
      array(strtolower($username), $threadId));

   $rootPosts[] = array(
      'id' => intval($row[0]),
      'date' => nsc_date(strtotime($row[1])),
      'author' => strval($row[2]),
      'category' => nsc_flagIntToString($row[3]),
      'body' => strval($row[4]),
      'postCount' => intval($row[5]),
      'isParticipant' => intval($row[6]) > 0
   );
}

echo json_encode(array('rootPosts' => $rootPosts));
