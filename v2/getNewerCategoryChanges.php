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
$lastPostEditId = nsc_getArg('lastPostEditId', 'INT');

$rows = nsc_query($pg, 
   'SELECT id, post_id, category, date FROM post_edit WHERE id > $1 ORDER BY id LIMIT 500', 
   array($lastPostEditId));

$edits = array();

foreach ($rows as $row)
{
   $edits[] = array(
      'postEditId' => intval($row[0]),
      'postId' => intval($row[1]),
      'category' => nsc_flagIntToString(intval($row[2])),
      'date' => nsc_date(strtotime($row[3]))
   );
}

echo json_encode(array('postEdits' => $edits));
