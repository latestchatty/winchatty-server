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

# NOTE: Before running this script, you should delete the search files in /home/chatty/search-data, then restart
# the winchatty-search service.  This will clear the search index.

require_once 'include/Global.php';

if (php_sapi_name() !== 'cli')
   die('Must be run from the command line.');

$pg = nsc_connectToDatabase();
$chunkStart = 0;
$chunkLen = 100;
$any = true;
$lastId = 0;
$endId = 10000;

if (file_exists('/tmp/dts-start'))
   $startId = intval(file_get_contents('/tmp/dts-start'));
if (file_exists('/tmp/dts-end'))
   $endId = intval(file_get_contents('/tmp/dts-end'));

$chunkStart = $startId;

while ($any && $chunkStart >= $startId && $chunkStart <= $endId)
{
   $rs = nsc_query($pg, 
      "SELECT p.id, p.body, p.author, COALESCE(p2.author, ''), p.category " .
      'FROM post p ' .
      'LEFT JOIN post p2 ON p.parent_id = p2.id ' .
      'WHERE p.id >= $1 AND p.id <= $3' .
      'ORDER BY p.id ' .
      'LIMIT $2',
      array($chunkStart, $chunkLen, $endId));
   $any = false;
   $firstId = 0;
   foreach ($rs as $row) 
   {
      $id = intval($row[0]);
      if ($id == 0)
         continue;
      $body = strval($row[1]);
      $author = strval($row[2]);
      $parentAuthor = strval($row[3]);
      $category = intval($row[4]);

      try
      {
         dts_index($id, $body, $author, $parentAuthor, $category);
      }
      catch (Exception $e) 
      {
         echo "Skipped id $id due to error: " . $e->getMessage() . "\n";
      }

      $any = true;
      if ($firstId == 0)
         $firstId = $id;
      $lastId = $id;
   }
   if (!$any)
      break;
   echo "Indexed $firstId ... $lastId\n";
   $chunkStart = $lastId + 1;
}
