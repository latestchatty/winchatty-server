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
$token = nsc_postArg('clientSessionToken', 'STR');

$session = nsc_getClientSession($pg, $token);
$username = $session['username'];
$client = $session['client_code'];
$shackerId = nsc_getShackerId($pg, $username);

$data = nsc_selectValueOrFalse($pg, 
   'SELECT data FROM private_client_data WHERE shacker_id = $1 AND client_code = $2',
   array($shackerId, $client));

if ($data === false)
   $data = '';

echo json_encode(array('data' => strval($data)));
