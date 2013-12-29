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
$data = nsc_postArg('data', 'STR');

$session = nsc_getClientSession($pg, $token);
$username = $session['username'];
$client = $session['client_code'];
$shackerId = nsc_getShackerId($pg, $username);

nsc_execute($pg, 'BEGIN', array());

nsc_execute($pg,
   'DELETE FROM private_client_data WHERE shacker_id = $1 AND client_code = $2',
   array($shackerId, $client));

nsc_execute($pg,
   'INSERT INTO private_client_data (shacker_id, client_code, data) VALUES ($1, $2, $3)',
   array($shackerId, $client, $data));

nsc_execute($pg, 'COMMIT', array());

echo json_encode(array('result' => 'success'));
