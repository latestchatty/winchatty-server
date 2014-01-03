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

require_once 'Global.php';
$pg = nsc_initJsonGet();
$count = nsc_getArg('count', 'INT?');
$expiration = nsc_getArg('expiration', 'INT?,36');
if (is_null($count))
   $count = 1000;
if (is_null($expiration))
   $expiration = 18;

$rows = nsc_query($pg, 
   "SELECT thread.id FROM thread INNER JOIN post ON thread.id = post.id WHERE thread.date > (NOW() - interval '$expiration hours') ORDER BY thread.bump_date DESC LIMIT \$1",
   array($count));
$threads = array();
foreach ($rows as $row)
{
   $threadId = intval($row[0]);
   $threads[] = nsc_getThread($pg, $threadId);
}

echo json_encode(array('threads' => $threads));
