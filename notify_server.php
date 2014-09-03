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
      if ($notifyTriggers === false || time() > $notifyTriggersTimestamp + 60)
      {
         $notifyTriggers = getNotifyTriggers($pg);
         $notifyTriggersTimestamp = time();
         deleteExpiredNotifications($pg);
      }
   
      $url = 'https://winchatty.com/v2/waitForEvent?includeParentAuthor=true&lastEventId=' . $eventId;
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
   $fp = fopen('/tmp/notify.log', 'a');
   fprintf($fp, "[%s] %s\n\n", $timestamp, $message);
   fclose($fp);
   echo "$message\n\n";
}
