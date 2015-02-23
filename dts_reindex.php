<?
# WinChatty Server
# Copyright (C) 2015 Brian Luft
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
      $body = strval($row[1]);
      $author = strval($row[2]);
      $parentAuthor = strval($row[3]);
      $category = intval($row[4]);

      dts_index($id, $body, $author, $parentAuthor, $category);

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
