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
$pg = nsc_initJsonPost();
$username = nsc_postArg('username', 'STR,50');
$postIds = nsc_postArg('postId', 'INT+');
$type = nsc_postarg('type', 'MPT');
$shackerId = nsc_getShackerId($pg, $username);

nsc_execute($pg, 'BEGIN', array());

foreach ($postIds as $postId) {
   # Clear any existing mark.
   nsc_execute($pg, 
      'DELETE FROM shacker_marked_post WHERE shacker_id = $1 AND post_id = $2', 
      array($shackerId, $postId));

   # Make sure the post exists.
   $existingPostId = nsc_selectValueOrFalse($pg, 'SELECT id FROM post WHERE id = $1', array($postId));
   if ($existingPostId === false)
      nsc_die('ERR_POST_DOES_NOT_EXIST', 'The specified post does not exist.');

   # Add a new mark, if necessary.
   if ($type == 'pinned' || $type == 'collapsed')
   {
      nsc_execute($pg,
         'INSERT INTO shacker_marked_post (shacker_id, post_id, mark_type) VALUES ($1, $2, $3)',
         array($shackerId, $postId, nsc_markTypeStringToInt($type)));
   }
}

nsc_execute($pg, 'COMMIT', array());

echo json_encode(array('result' => 'success'));
