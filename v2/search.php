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
         'body' => '<span class="jt_red"><b>* N U K E D *</b></span>',
         'lols' => array()
      );
}

echo json_encode(array('posts' => $searchResults));

