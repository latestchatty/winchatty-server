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
$username = nsc_postArg('username', 'STR');
$password = nsc_postArg('password', 'STR');

nsc_checkLogin($username, $password);
$username = strtolower($username);
nfy_checkClientId($pg, $clientId);

try
{
   nfy_detachAccount($pg, $clientId, $username);
}
catch (Exception $ex)
{
   nsc_handleException($ex);
}

echo json_encode(array('result' => 'success'));
