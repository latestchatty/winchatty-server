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

require_once 'include/Global.php';

if (php_sapi_name() !== 'cli')
   die('Must be run from the command line.');

function listPush(&$list, $name, $id)
{
   global $cf;
   if (isset($list[$name]))
   {
      $itemPtr = $list[$name];
      $newPtr = cf_cons($cf, $id, $itemPtr);
      $list[$name] = $newPtr;
   }
   else
   {
      $itemPtr = cf_cons($cf, $id, 0);
      $list[$name] = $itemPtr;
   }
}

function writeFiles()
{
   global $stems;
   file_put_contents('/mnt/ssd/ChattyIndex/Stems', serialize($stems));
}

$pg = nsc_connectToDatabase();
$startTime = 0;
$lastId = 0;
$totalStems = 0;

$cf = cf_create('/mnt/ssd/ChattyIndex/LinkedLists');

$uniqueStems = 0;
$posts = array();
$stems = array();  
$id = 0;
$nextThreshold = 0;
$thresholdLength = 100000;
$totalCells = 0;
$totalPosts = 0;
$chunkStart = time();
$processStart = time();
$postsIndex = 0;

while (true)
{
   if ((time() - $startTime) >= 1)
   {
      $date = date('H:i:s A');
      $totalCellsStr = number_format($totalCells, 0);
      $totalSizeStr = number_format(($totalCells * 10) / 1024 / 1024, 1);
      echo " [$date] $totalPosts posts. $totalCellsStr nodes ($totalSizeStr MB). Post #$id.\r";
      $startTime = time();
   }

   if ($totalPosts >= $nextThreshold)
   {
      $nextThreshold += $thresholdLength;
      writeFiles();
      $chunkTotalSeconds = time() - $chunkStart;
      $chunkMinutes = (int)($chunkTotalSeconds / 60);
      $chunkSeconds = $chunkTotalSeconds % 60;

      $totalTotalSeconds = time() - $processStart;
      $totalHoursStr = number_format($totalTotalSeconds / 60 / 60, 2);

      $date = date('H:i:s A');
      $totalCellsStr = number_format($totalCells, 0);
      $totalSizeStr = number_format(($totalCells * 10) / 1024 / 1024, 1);
      echo " [$date] $totalPosts posts. $totalCellsStr nodes ($totalSizeStr MB). Post #$id.\n";
      echo "    Chunk time: $chunkMinutes minutes, $chunkSeconds seconds\n";
      echo "    Total time: $totalHoursStr hours\n";
      $chunkStart = time();
   }

   /*if ((time() - $startTime) >= 10)
   {
      pg_query($pg, 'ROLLBACK');
      pg_query($pg, 'BEGIN');
      $startTime = time();
   }*/

   if ($postsIndex >= count($posts))
   {
      echo "#";
      fflush(STDOUT);
      $postsIndex = 0;
      $posts = nsc_query($pg, 'SELECT post.id, post.body FROM post WHERE post.id > $1 ORDER BY post.id LIMIT 5000', array($lastId));
      if (empty($posts))
         break;
      echo "\r \r";
   }

   $post = $posts[$postsIndex++];
   $totalPosts++;

   $id = intval($post[0]);
   $body = strval($post[1]);
   $bodyStems = nsc_convertTextToStems($body);
   
   $lastId = $id;
   
   foreach ($bodyStems as $bodyStem)
   {
      if (!empty($bodyStem))
      {
         listPush($stems, $bodyStem, $id);
         $totalCells++;
      }
   }
}

writeFiles();

$date = date('H:i:s A');
$totalCellsStr = number_format($totalCells, 0);
$totalSizeStr = number_format(($totalCells * 10) / 1024 / 1024, 1);
echo " [$date] $totalPosts posts. $totalCellsStr nodes ($totalSizeStr MB). Post #$id.\r";
cf_close($cf);

echo "\n";