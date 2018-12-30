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

if (php_sapi_name() !== 'cli')
   die('Must be run from the command line.');

require_once 'include/Global.php';

runNotifyServer();

function runNotifyServer()
{
   $pg = nsc_connectToDatabase();
   $eventId = nsc_selectValue($pg, 'SELECT MAX(id) FROM event', array());
   $notifyTriggers = false; # Will be set on first iteration of loop.
   $notifyTriggersTimestamp = false;
   $startTime = time();
   
   while (time() < $startTime + 300)
   {
      sleep(1);
      
      if ($notifyTriggers === false || time() > $notifyTriggersTimestamp + 60)
      {
         $notifyTriggers = getNotifyTriggers($pg);
         $notifyTriggersTimestamp = time();
         deleteExpiredNotifications($pg);
      }
   
      $url = 'http://winchatty.com/v2/waitForEvent?includeParentAuthor=true&lastEventId=' . $eventId;
      $response = json_decode(file_get_contents($url), true);
      
      if (isset($response['error']))
         die('Error: ' . $response['code'] . ' - ' . $response['message']);
      
      $eventId = $response['lastEventId'];
      foreach ($response['events'] as $event)
      {
         if ($event['eventType'] != 'newPost')
            continue;
         
         $post = $event['eventData']['post'];
         $parentAuthor = strval($event['eventData']['parentAuthor']);
         $author = strval($post['author']);
         $body = strval($post['body']);
         $body = ThreadParser::removeSpoilers($body);
         $postId = intval($post['id']);
         $canonBody = canonicalize($body);
         $truncatedBody = truncateBody(html_entity_decode(stripTagsSpecial($body)));
         
         $users = array();
         
         if (isset($notifyTriggers['replyUsernames'][strtolower($parentAuthor)]))
            $users[strtolower($parentAuthor)] = true;
         
         foreach ($notifyTriggers['keywordUsernamesMap'] as $notifyKeyword => $notifyUsernames)
            if (strpos($canonBody, $notifyKeyword) !== false)
               foreach ($notifyUsernames as $notifyUsername)
                  $users[strtolower($notifyUsername)] = true;
         
         foreach ($users as $username => $unused)
            sendNotification($pg, $username, $author, $truncatedBody, $postId);
      }
   }
}

function stripTagsSpecial($str)
{
   $str = str_replace('<br/>', ' ', $str);
   $str = str_replace('<br />', ' ', $str);
   return strip_tags($str);
}

function truncateBody($str)
{
   if (strlen($str) <= 100)
      return $str;
   else
      return substr($str, 0, 97) . '...';
}

function deleteExpiredNotifications($pg)
{
   nsc_execute($pg, 'DELETE FROM notify_client_queue WHERE expiration <= NOW()', array());
}

function sendNotification($pg, $username, $subject, $body, $postId)
{
   $count = nfy_sendNotification($pg, $username, $subject, $body, $postId);
   debugLog("Sent notification to \"$username\" ($count clients):\n$subject\n$body\nPost #$postId");
}

function getNotifyTriggers($pg)
{
   $replyUsernames = array();
   $keywordUsernamesMap = array();

   $rs = nsc_query($pg, 'SELECT username, match_replies, match_mentions FROM notify_user', array());
   foreach ($rs as $row)
   {
      $username = strtolower(strval($row[0]));
      $matchReplies = intval($row[1]);
      $matchMentions = intval($row[2]);
      
      if ($matchReplies == 't')
         $replyUsernames[$username] = true;
      
      if ($matchMentions == 't')
      {
         $keyword = canonicalize($username);
         if (isset($keywordUsernamesMap[$keyword]))
         {
            $usernames = $keywordUsernamesMap[$keyword];
            $usernames[] = $username;
            $keywordUsernamesMap[$keyword] = $usernames;
         }
         else
         {
            $keywordUsernamesMap[$keyword] = array($username);
         }
      }
   }
   
   $rs = nsc_query($pg, 'SELECT username, keyword FROM notify_user_keyword', array());
   foreach ($rs as $row)
   {
      $username = strval($row[0]);
      $keyword = canonicalize($row[1]);
      
      if (isset($keywordUsernamesMap[$keyword]))
      {
         $usernames = $keywordUsernamesMap[$keyword];
         $usernames[] = $username;
         $keywordUsernamesMap[$keyword] = $usernames;
      }
      else
      {
         $keywordUsernamesMap[$keyword] = array($username);
      }
   }
   
   return array(
      'replyUsernames' => $replyUsernames,
      'keywordUsernamesMap'=> $keywordUsernamesMap);
}

function canonicalize($keyword)
{
   $keyword = strtolower(trim(html_entity_decode(strip_tags(strval($keyword)))));
   $charsToRemove = "~!@#$%^&*()_+`-=[]\\{}|;':\",./<>?\t\n\r";
   $keyword = str_replace('.', ' ', $keyword);
   
   foreach (str_split($charsToRemove) as $ch)
      $keyword = str_replace($ch, ' ', $keyword);
   
   while (strpos($keyword, '  ') !== false)
      $keyword = str_replace('  ', ' ', $keyword);
   
   return ' ' . trim($keyword) . ' ';
}

function debugLog($message)
{
   $timestamp = date('r');
   printf("[%s] %s\n\n", $timestamp, $message);
}
