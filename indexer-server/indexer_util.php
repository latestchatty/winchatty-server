<?
# WinChatty Server
# Copyright (C) 2015 Brian Luft
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

require_once '../include/Global.php';

define('TOTAL_TIME_SEC',        300);
define('LOL_INTERVAL_SEC',      60);

function checkInternet() # void
{
   $curl = curl_init();
   curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($curl, CURLOPT_HEADER, true);
   curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
   curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($curl, CURLOPT_USERAGENT, 'WinChatty NuSearch');
   curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With: libcurl'));
   curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
   curl_setopt($curl, CURLOPT_URL, "http://www.shacknews.com/robots.txt");
   curl_setopt($curl, CURLOPT_POST, false);
   $robots = curl_exec($curl);
   curl_close($curl);

   if ($robots === false)
      throw new Exception('Internet access problem.');
   if (strpos($robots, 'Disallow:') === false)
      throw new Exception('robots.txt data was not expected.');
}

function logNewPost($pg, $id)
{
   $posts = nsc_getPosts($pg, array($id));

   $post = $posts[0];
   $parentId = $post['parentId'];
   $parentAuthor = '';
   if ($parentId > 0)
   {
      $parentPosts = nsc_getPosts($pg, array($parentId));
      $parentPost = $parentPosts[0];
      $parentAuthor = $parentPost['author'];
   }

   # [E_NEWP]
   $newp = array(
      'postId' => intval($id),
      'post' => $post,
      'parentAuthor' => $parentAuthor
   );
   nsc_logEvent($pg, 'newPost', $newp);
}
