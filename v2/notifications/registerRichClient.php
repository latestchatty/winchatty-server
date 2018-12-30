<?
// WinChatty Server
// Copyright (c) 2013 Brian Luft
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
// documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
// Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
// OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once '../../include/Global.php';
$pg = nsc_initJsonPost();
$clientId = nsc_postArg('id', 'STR');
$clientName = nsc_postArg('name', 'STR');
$username = nsc_postArg('username', 'STR');
$password = nsc_postArg('password', 'STR');
$appleDeviceToken = nsc_postArg('appleDeviceToken', 'STR?');

if (!empty($appleDeviceToken))
   nsc_die('ERR_SERVER', 'Not implemented yet.');

nsc_checkLogin($username, $password);
$username = strtolower($username);
nfy_registerClientId($pg, $clientId, $clientName);
nfy_attachAccount($pg, $username, $clientId);

echo json_encode(array('result' => 'success'));
