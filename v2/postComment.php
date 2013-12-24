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
   else if (strstr($ret, 'fixup_postbox_parent_for_remove(') === false)
      nsc_die('ERR_SERVER', 'Unexpected response from server: ' . $ret);
}
catch (Exception $e)
{
   $message = $e->getMessage();

   if (trim(strtolower($message)) == 'unable to log into user account.')
      nsc_die('ERR_INVALID_LOGIN', $message);

   nsc_die('ERR_SERVER', $message);
}

file_put_contents('/mnt/ssd/ChattyIndex/ForceReadNewPosts', '1');

die(json_encode(array('result' => 'success')));
