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

require_once 'Global.php';
$pg = nsc_initJsonPost();
$username = nsc_postArg('username', 'STR,50');
$client = nsc_postArg('client', 'STR,50');
$data = nsc_postArg('data', 'STR,100000');
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
