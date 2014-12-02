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

$row = nsc_selectRow($pg, 
   'SELECT filter_nws, filter_stupid, filter_political, filter_tangent, filter_informative FROM shacker WHERE id = $1', 
   array($shackerId));

echo json_encode(array(
   'filters' => array(
      'nws' => $row[0] == 't',
      'stupid' => $row[1] == 't',
      'political' => $row[2] == 't',
      'tangent' => $row[3] == 't',
      'informative' => $row[4] == 't'
   )
));
