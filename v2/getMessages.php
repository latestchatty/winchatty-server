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
$username = nsc_postArg('username', 'STR');
$password = nsc_postArg('password', 'STR');
$folder = nsc_postArg('folder', 'MBX');
$page = nsc_postArg('page', 'INT');

if ($page < 1)
   nsc_die('ERR_ARGUMENT', 'Page number is 1-based.');

try
{
   $m = MessageParser()->getMessages($folder, $username, $password, $page, 0);
}
catch (Exception $e)
{
   nsc_handleException($e);
}

$messages = array();

foreach ($m['messages'] as $msg)
{
   # Correct for a Shacknews bug in reporting message timestamps.
   # Shacknews will report "January 17, 2014, 6:18 am", the current UTC time is "January 17, 2014, 10:18 pm".
   # So we will treat it as UTC, and then offset by +16 hours.
   $time = strtotime($msg['date'] . ' UTC') + (16 * 60 * 60);

   $messages[] = array(
      'id' => intval($msg['id']),
      'from' => strval($msg['from']),
      'to' => strval($msg['to']),
      'subject' => strval($msg['subject']),
      'date' => nsc_date($time),
      'body' => strval($msg['body']),
      'unread' => $msg['unread']
   );
}

echo json_encode(array(
   'page' => intval($m['current_page']),
   'totalPages' => intval($m['last_page']),
   'totalMessages' => intval($m['total']),
   'messages' => $messages
));
