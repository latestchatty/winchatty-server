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
$username = nsc_postArg('username', 'STR');
$password = nsc_postArg('password', 'STR');
$parentId = nsc_postArg('parentId', 'INT');
$text = nsc_postArg('text', 'STR');

while (strlen($text) < 10)
   $text .= ' ';

try 
{
   $ret = ChattyParser()->post($username, $password, $parentId, 0, $text);

   if (strstr($ret, 'You must be logged in to post') !== false)
      nsc_die('ERR_INVALID_LOGIN', 'Unable to log into user account.');
   else if (strstr($ret, 'Please wait a few minutes before trying to post again.') !== false)
      nsc_die('ERR_POST_RATE_LIMIT', 'Please wait a few minutes before trying to post again.');
   else if (strstr($ret, 'banned') !== false)
      nsc_die('ERR_BANNED', 'You are banned.');
   else if (strstr($ret, 'Trying to post to a nuked thread') !== false)
      nsc_die('ERR_NUKED', 'You cannot reply to a nuked thread or subthread.');
   else if (strstr($ret, 'fixup_postbox_parent_for_remove(') === false)
      nsc_die('ERR_SERVER', 'Unexpected response from server: ' . $ret);
}
catch (Exception $e)
{
   nsc_handleException($e);
}

file_put_contents(V2_DATA_PATH . 'ForceReadNewPosts', '1');

die(json_encode(array('result' => 'success')));
