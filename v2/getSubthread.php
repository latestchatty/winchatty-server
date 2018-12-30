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
