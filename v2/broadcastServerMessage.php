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
$message = nsc_postArg('message', 'STR');

if ($username != 'electroly')
   nsc_die('ERR_INVALID_LOGIN', 'Enter an administrator username and password.');

try
{
   MessageParser()->getUserID($username, $password);
}
catch (Exception $e)
{
   $message = $e->getMessage();

   if (trim(strtolower($message)) == 'unable to log into user account.')
      nsc_die('ERR_INVALID_LOGIN', 'Enter an administrator username and password.');

   nsc_die('ERR_SERVER', $message);
}

# [E_SMSG]
$smsg = array('message' => $message);

nsc_logEvent($pg, 'serverMessage', $smsg);

echo json_encode(array('result' => 'success'));
