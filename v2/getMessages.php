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
$pg = nsc_initJsonPost();
$username = nsc_postArg('username', 'STR');
$password = nsc_postArg('password', 'STR');
$folder = nsc_postArg('folder', 'MBX');
$page = nsc_postArg('page', 'INT');

if ($page < 1)
   nsc_die('ERR_ARGUMENT', 'Page number is 1-based.');

try
{
   $m = MessageParser()->getMessages($folder, $username, $password, $page, 0);
}
catch (Exception $e)
{
   nsc_handleException($e);
}

$messages = array();

foreach ($m['messages'] as $msg)
{
   $messages[] = array(
      'id' => intval($msg['id']),
      'from' => strval($msg['from']),
      'to' => strval($msg['to']),
      'subject' => strval($msg['subject']),
      'date' => nsc_date(strtotime($msg['date'])),
      'body' => strval($msg['body']),
      'unread' => $msg['unread']
   );
}

echo json_encode(array(
   'page' => intval($m['current_page']),
   'totalPages' => intval($m['last_page']),
   'totalMessages' => intval($m['total']),
   'messages' => $messages
));
