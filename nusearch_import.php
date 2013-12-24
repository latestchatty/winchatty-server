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

$skipPosts = 11190385;

function selectValueOrFalse($pg, $sql, $args) # value or false
{
   $row = selectRowOrFalse($pg, $sql, $args);
   return $row === false ? false : $row[0];
}

function selectValueOrThrow($pg, $sql, $args) # value
{
   $ret = selectValueOrFalse($pg, $sql, $args);
   if ($ret === false)
      throw new Exception('SQL query returned zero rows.');
   else
      return $ret;
}

function selectRowOrFalse($pg, $sql, $args) # dict or false
{
   $rs = pg_query_params($pg, $sql, $args);
   if ($rs === false)
      throw new Exception('selectValue failed.');
   $row = pg_fetch_row($rs);
   if ($row === false)
      return false;
   else
      return $row;
}

function selectRowOrThrow($pg, $sql, $args) # dict
{
   $ret = selectRowOrFalse($pg, $sql, $args);
   if ($ret === false)
      throw new Exception('SQL query returned zero rows.');
   else
      return $ret;
}

function executeOrThrow($pg, $sql, $args) # void
{
   if (pg_query_params($pg, $sql, $args) === false)
      throw new Exception('SQL execute failed.');
}

function executeOrFalse($pg, $sql, $args) # void
{
   return pg_query_params($pg, $sql, $args) !== false;
}

function flagStringToInt($flag)
{
   switch ($flag)
   {
      case "informative": return 6;
      case "interesting": return 6;
      case "nws":         return 2;
      case "stupid":      return 3;
      case "offtopic":    return 5;
      case "tangent":     return 5;
      case "ontopic":     return 1;
      case "political":   return 4;
      case "religious":   return 4;
      default:            return 1;
   }
}

#-----------------------------------------------------------------

if (php_sapi_name() !== 'cli')
   die("Must be run from the command line.\n");

$dumpFilePath = '/mnt/websites/winchatty.com/chatty.dump';

/*
[post]
pid: 12347
thr: 12340
par: 12340
aut: electroly
mod: tangent
dat: 2013-12-23T...
txt: Hello there blah blah blah blah blah blah 
[nuked_post]
pid: 12346
ret: 0
lst: 2013-12-23T...
err: Blah blah blah blah..
*/

$knownThreads = array();
$totalPostsProcessed = 0;
$totalThreadsInserted = 0;
$totalPostsInserted = 0;

$pg = pg_connect('dbname=chatty user=nusearch password=nusearch');
if ($pg === false)
   die("Failed to connect to chatty database.\n");

executeOrThrow($pg, 'BEGIN', array());

$fp = fopen($dumpFilePath, 'r');
if (!$fp)
   die("Can't open dump file.\n");
$line = false;
$startTime = time();
while (($line = fgets($fp)) !== false)
{
   if (time() - $startTime >= 5)
   {
      echo "\rCommitting...";
      fflush(STDOUT);
      executeOrThrow($pg, 'COMMIT', array());
      executeOrThrow($pg, 'BEGIN', array());
      echo "\rProcessed $totalPostsProcessed posts.  Inserted $totalThreadsInserted threads, $totalPostsInserted posts.\n";
      $startTime = time();
   }

   $line = trim($line);
   if ($line == '[post]')
   {
      $totalPostsProcessed++;

      if ($totalPostsProcessed < $skipPosts)
         continue;

      $pid = intval(trim(substr(fgets($fp), 5)));
      $thr = intval(trim(substr(fgets($fp), 5)));
      $par = intval(trim(substr(fgets($fp), 5)));
      $aut = trim(substr(fgets($fp), 5));
      $mod = flagStringToInt(trim(substr(fgets($fp), 5)));
      $dat = date('c', strtotime(trim(substr(fgets($fp), 5))));
      $txt = trim(substr(fgets($fp), 5));

      $authorC = strtolower($aut);
      $bodyC = strtolower(strip_tags($txt));

      try
      {
         # Create the thread if needed.
         if (!isset($knownThreads[strval($thr)]))
         {
            $retId = selectValueOrFalse($pg, 'SELECT id FROM thread WHERE id = $1', array($thr));
            if ($retId != $thr)
            {
               executeOrThrow($pg, 'INSERT INTO thread (id, date, bump_date) VALUES ($1, $2, $3)', array($thr, $dat, $dat));
               $totalThreadsInserted++;
            }
            $knownThreads[strval($thr)] = strtotime($dat);
         }

         $bumpDate = $knownThreads[strval($thr)];
         if (strtotime($dat) > $bumpDate)
         {
            executeOrThrow($pg, 'UPDATE thread SET bump_date = $1 WHERE id = $2', array($dat, $thr));
            $knownThreads[strval($thr)] = strtotime($dat);
         }

         # Create the post.  Ignore errors if the post exists.
         if ($pid != selectValueOrFalse($pg, 'SELECT id FROM post WHERE id = $1', array($pid)))
         {
            executeOrThrow($pg, 'INSERT INTO post (id, thread_id, parent_id, author, category, date, body, author_c, body_c) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
               array($pid, $thr, $par, $aut, $mod, $dat, $txt, $authorC, $bodyC));
            $totalPostsInserted++;
         }

      }
      catch (Exception $e)
      {
         executeOrThrow($pg, 'ROLLBACK', array());
         die($e->getMessage() . "\n");
      }

   }
   else if ($line == '[nuked_post]')
   {
      $pid = intval(trim(substr(fgets($fp), 5)));
      $ret = intval(trim(substr(fgets($fp), 5)));
      $lst = strtotime(trim(substr(fgets($fp), 5)));
      $err = trim(substr(fgets($fp), 5));
   }
}

executeOrThrow($pg, 'COMMIT', array());

fclose($fp);
pg_close($pg);
