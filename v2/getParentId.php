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
$parentIds = nsc_getArg('id', 'INT+,50');

$commaSeparatedIdList = implode(',', $parentIds);

$rows = nsc_query($pg, "SELECT id, parent_id FROM post WHERE id IN ($commaSeparatedIdList)", array());
$relationships = array();
foreach ($rows as $row)
   $relationships[] = array('childId' => intval($row[0]), 'parentId' => intval($row[1]));

echo json_encode(array('relationships' => $relationships));
