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
$terms = nsc_getArg('terms', 'STR?', '');
$author = nsc_getArg('author', 'STR?', '');
$parentAuthor = nsc_getArg('parentAuthor', 'STR?', '');
$category = nsc_getArg('category', 'MOD?', '');
$offset = nsc_getArg('offset', 'INT?', 0);
$limit = nsc_getArg('limit', 'INT?,500', 35);
$oldestFirst = nsc_getArg('oldestFirst', 'BIT?', false);

if (empty($terms) && empty($author) && empty($parentAuthor))
   nsc_die('ERR_SERVER', '(Temporary) A search term, author, or parent author query is required.');

#$posts = nsc_search($pg, $terms, $author, $parentAuthor, $category, $offset, $limit, $oldestFirst);

$perPage = 15;
$startingPage = floor((float)$offset / (float)$perPage);
$startingPageFirstIndex = $offset - ($startingPage * $perPage);
$endingPage = floor((float)($offset + $limit - 1) / (float)$perPage);
$endingPageLastIndex = ($offset + $limit - 1) - ($endingPage * $perPage);

$startingPage++;
$endingPage++;

$postIds = array();
for ($page = $startingPage; $page <= $endingPage; $page++)
{
   try
   {
      $pageResults = SearchParser()->search($terms, $author, $parentAuthor, $category, $page, $oldestFirst);
   }
   catch (Exception $e)
   {
      nsc_handleException($e);
   }

   if (empty($pageResults))
      break;
   
   foreach ($pageResults as $i => $result)
   {
      if ($page == $startingPage && $i < $startingPageFirstIndex)
      {
         # Nothing
      }
      else if ($page == $endingPage && $i > $endingPageLastIndex)
      {
         break;
      }
      else
      {
         $postIds[] = intval($result['id']);
      }
   }
}

if (empty($postIds))
   die(json_encode(array('posts' => array())));

$posts = nsc_getPosts($pg, $postIds);
$dict = array();
foreach ($posts as $post)
   $dict[$post['id']] = $post;

$searchResults = array();
foreach ($postIds as $postId)
{
   if (isset($dict[$postId]))
      $searchResults[] = $dict[$postId];
   else
      $searchResults[] = array(
         'id' => $postId,
         'threadId' => 0,
         'parentId' => 0,
         'author' => 'Duke Nuked',
         'category' => 'ontopic',
         'date' => nsc_date(strtotime('1980-01-01 Midnight UTC')),
         'body' => '<span class="jt_red"><b>* N U K E D *</b></span>'
      );
}

echo json_encode(array('posts' => $searchResults));

