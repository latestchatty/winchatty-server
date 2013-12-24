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
nsc_jsonHeader();
nsc_assertGet();
$filePath = '/mnt/ssd/ChattyIndex/LastID';
#search_data_directory . 'LastID';
$lastId = nsc_getArg('lastId', 'INT?', intval(file_get_contents($filePath)));

while (intval(file_get_contents($filePath)) <= $lastId)
{
   sleep(2);
   # I know, right?  Gets the job done though.  Programming is hard.
}

echo json_encode(array('id' => intval(file_get_contents($filePath))));
