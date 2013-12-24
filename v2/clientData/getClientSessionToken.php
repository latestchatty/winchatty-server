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
$client = nsc_postArg('client', 'STR');
$version = nsc_postArg('version', 'STR');

$expiration = time() + 3600; # 1 hour

# Is there an existing session for this user/client combo?
$existingToken = nsc_selectValueOrFalse($pg,
   'SELECT token FROM client_session WHERE username = $1 AND client_code = $2',
   array($username, $client));
if ($existingToken !== false)
{
   # Refresh the expiration date and version
   nsc_execute($pg,
      'UPDATE client_session SET expire_date = $1, client_version = $2 WHERE token = $3',
      array(date('c', $expiration), $version, $existingToken));

   die(json_encode(array(
      'clientSessionToken' => $existingToken,
      'expiration' => nsc_date($expiration)
   )));
}

$token = uniqid(sha1($username . $client));

# Validate the username and password.
try
{
   MessageParser()->getUserID($username, $password);
}
catch (Exception $e)
{
   nsc_die('ERR_INVALID_LOGIN', 'Invalid login.');
}

# Create the client_session record.
nsc_execute($pg, 
   'INSERT INTO client_session (token, username, client_code, client_version, expire_date) VALUES ($1, $2, $3, $4, $5)',
   array($token, $username, $client, $version, date('c', $expiration)));

echo json_encode(array(
   'clientSessionToken' => $token,
   'expiration' => nsc_date($expiration)
));
