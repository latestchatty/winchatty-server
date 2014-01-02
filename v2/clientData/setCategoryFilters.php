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
$nws = nsc_postArg('nws', 'BIT');
$stupid = nsc_postArg('stupid', 'BIT');
$political = nsc_postArg('political', 'BIT');
$tangent = nsc_postArg('tangent', 'BIT');
$informative = nsc_postArg('informative', 'BIT');

$session = nsc_getClientSession($pg, $token);
$username = $session['username'];
$shackerId = nsc_getShackerId($pg, $username);

nsc_execute($pg,
   'UPDATE shacker SET filter_nws = $1, filter_stupid = $2, filter_political = $3, filter_tangent = $4, filter_informative = $5 WHERE id = $6',
   array($nws, $stupid, $political, $tangent, $informative, $shackerId));

echo json_encode(array('result' => 'success'));
