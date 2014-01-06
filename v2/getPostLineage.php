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
$ids = nsc_getArg('id', 'INT+,50');

$retList = array();
foreach ($ids as $id)
{
   $thread = nsc_getThread($pg, $id, true);
   if ($thread === false)
      continue;
   $posts = array();
   foreach ($thread['posts'] as $post)
      $posts[$post['id']] = $post;

   $lineage = array();
   $currentId = $id;

   while ($currentId != 0)
   {
      $post = $posts[$currentId];
      $currentId = $post['parentId'];
      $lineage[] = $post;
   }

   $retList[] = array(
      'postId' => $id,
      'lineage' => $lineage
   );
}

echo json_encode(array('posts' => $retList));