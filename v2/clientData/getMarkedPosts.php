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
$username = nsc_getArg('username', 'STR,50');
$shackerId = nsc_getShackerId($pg, $username);

$rows = nsc_query($pg, 
   'SELECT post_id, mark_type FROM shacker_marked_post WHERE shacker_id = $1', 
   array($shackerId));

$markedPosts = array();
foreach ($rows as $row)
{
   $markedPosts[] = array(
      'id' => intval($row[0]),
      'type' => nsc_markTypeIntToString(intval($row[1]))
   );
}

echo json_encode(array('markedPosts' => $markedPosts));
