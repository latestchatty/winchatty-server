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
$pg = nsc_initJsonGet();
$username = nsc_getArg('username', 'STR,50');
$shackerId = nsc_getShackerId($pg, $username);

$row = nsc_selectRow($pg, 
   'SELECT filter_nws, filter_stupid, filter_political, filter_tangent, filter_informative FROM shacker WHERE id = $1', 
   array($shackerId));

echo json_encode(array(
   'filters' => array(
      'nws' => $row[0] == 't',
      'stupid' => $row[1] == 't',
      'political' => $row[2] == 't',
      'tangent' => $row[3] == 't',
      'informative' => $row[4] == 't'
   )
));
