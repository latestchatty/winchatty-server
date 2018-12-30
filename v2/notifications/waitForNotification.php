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

require_once '../../include/Global.php';
$pg = nsc_initJsonPost();
$clientId = nsc_postArg('clientId', 'STR');

nfy_checkClientId($pg, $clientId, true);
pg_close($pg);

$startTime = time();
$endTime = $startTime + 600; # 10 minutes
$messages = false;

while (time() < $endTime)
{
   $pg = nsc_connectToDatabase();
   $messages = nsc_query($pg, 
      'SELECT subject, body, post_id, thread_id FROM notify_client_queue WHERE client_id = $1 ORDER BY id',
      array($clientId));
   pg_close($pg);
      
   if (empty($messages))
      sleep(2);
   else
      break;
}

$messageObjs = array();
foreach ($messages as $message)
{
   $messageObjs[] = array(
      'subject' => strval($message[0]),
      'body' => strval($message[1]),
      'postId' => intval($message[2]),
      'threadId' => intval($message[3]));
}

$pg = nsc_connectToDatabase();
nsc_execute($pg, 'DELETE FROM notify_client_queue WHERE client_id = $1', array($clientId));

echo json_encode(array('messages' => $messageObjs));
