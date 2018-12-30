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
