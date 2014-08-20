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

require_once '../../include/Global.php';
$pg = nsc_initJsonPost();
$clientId = nsc_postArg('clientId', 'STR');

$clients = nsc_query($pg, 'SELECT id, username FROM notify_client WHERE id = $1', array($clientId));
if (empty($clients))
   nsc_die('ERR_UNKNOWN_CLIENT_ID', 'Client ID was not found in the database.');
$client = $clients[0];
if (is_null($client[1]) || empty($client[1]))
   nsc_die('ERR_CLIENT_NOT_ASSOCIATED', 'Client is not associated with a Shacknews account.');

$startTime = time();
$endTime = $startTime + 600; # 10 minutes
$messages = false;

while (time() < $endTime)
{
   $messages = nsc_query($pg, 
      'SELECT subject, body, post_id, thread_id FROM notify_client_queue WHERE client_id = $1 ORDER BY id',
      array($clientId));
      
   if (empty($messages))
      sleep(5);
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

nsc_execute($pg, 'DELETE FROM notify_client_queue WHERE client_id = $1', array($clientId));

echo json_encode(array('messages' => $messageObjs));
