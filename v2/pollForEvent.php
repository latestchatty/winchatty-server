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
nsc_jsonHeader();
nsc_assertGet();
$filePath = V2_DATA_PATH . 'LastEventID';
$eventsFilePath = V2_DATA_PATH . 'LastEvents';
$lastId = nsc_getArg('lastEventId', 'INT');
$includeParentAuthor = nsc_getArg('includeParentAuthor', 'BIT?', false);

$pg = nsc_connectToDatabase();

$rows = nsc_query($pg, 'SELECT id, date, type, data FROM event WHERE id > $1 ORDER BY id', array($lastId));
if (count($rows) > 0 && intval($rows[0][0]) != $lastId + 1)
   nsc_die('ERR_TOO_MANY_EVENTS', 'Too many events have occurred since the specified last event ID.');

$returnEvents = array();
foreach ($rows as $row)
{
   $eventData = json_decode(strval($row[3]), true);

   if (!$includeParentAuthor && isset($eventData['parentAuthor']))
      unset($eventData['parentAuthor']);

   $returnEvents[] = array(
      'eventId' => intval($row[0]),
      'eventDate' => nsc_date(strtotime($row[1])),
      'eventType' => strval($row[2]),
      'eventData' => $eventData,
   );
}

if (count($returnEvents) > 0)
   $lastId = $returnEvents[count($returnEvents) - 1]['eventId'];

echo json_encode(array('lastEventId' => $lastId, 'events' => $returnEvents));        
