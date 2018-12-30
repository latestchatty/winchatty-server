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
$nws = nsc_postArg('nws', 'BIT');
$stupid = nsc_postArg('stupid', 'BIT');
$political = nsc_postArg('political', 'BIT');
$tangent = nsc_postArg('tangent', 'BIT');
$informative = nsc_postArg('informative', 'BIT');
$shackerId = nsc_getShackerId($pg, $username);

nsc_execute($pg,
   'UPDATE shacker SET filter_nws = $1, filter_stupid = $2, filter_political = $3, filter_tangent = $4, filter_informative = $5 WHERE id = $6',
   array($nws, $stupid, $political, $tangent, $informative, $shackerId));

echo json_encode(array('result' => 'success'));
