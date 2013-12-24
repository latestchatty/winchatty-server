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

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$lastID = false;
if (isset($_GET['last_id']))
   $lastID = $_GET['last_id'];

$filePath = '/mnt/websites/winchatty.com/data/Search/LastID';

$initial = $lastID === false ? file_get_contents($filePath) : $lastID;

while (intval(file_get_contents($filePath)) == intval($initial))
{
   sleep(2);
   echo ' ';
   flush();
}

echo file_get_contents($filePath);