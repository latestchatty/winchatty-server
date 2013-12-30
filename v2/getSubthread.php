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
$idList = nsc_getArg('id', 'INT+,50');

$subthreads = array();
foreach ($idList as $subthreadId)
{
   $thread = nsc_getThread($pg, $subthreadId, true);
   if ($thread !== false)
   {
      $childrenOf = array();
      $posts = array();
      foreach ($thread['posts'] as $post)
      {
         $childrenOf[$post['id']] = array();
         $posts[$post['id']] = $post;
      }
      foreach ($thread['posts'] as $post)
         $childrenOf[$post['parentId']][] = $post['id'];

      $subthreadPosts = array();
      $stack = array();
      array_push($stack, $subthreadId);

      while (!empty($stack))
      {
         $id = array_pop($stack);

         $subthreadPosts[] = $posts[$id];

         foreach ($childrenOf[$id] as $childId)
            array_push($stack, $childId);
      }

      $subthreads[] = array('subthreadId' => $subthreadId, 'posts' => $subthreadPosts);
   }
}

echo json_encode(array('subthreads' => $subthreads));
