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
$pg = nsc_initJsonGet();

# Tweaked from http://guid.us/GUID/PHP 
# We don't really care about it being a valid GUID.  We're just storing the thing as text anyway.  Whatever.
$charid = strtoupper(md5(uniqid(mt_rand(), true)));
$uuid = 
   substr($charid, 0, 8) . '-'
   . substr($charid, 8, 4) . '-'
   . substr($charid, 12, 4) . '-'
   . substr($charid, 16, 4) . '-'
   . substr($charid, 20, 12);

echo json_encode(array('id' => $uuid));
