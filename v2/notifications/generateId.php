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

require_once '../../include/Global.php';
$pg = nsc_initJsonGet();

# Tweaked from http://guid.us/GUID/PHP 
# We don't really care about it being a valid GUID.  We're just storing the thing as text anyway.  Whatever.
$charid = strtoupper(md5(uniqid(mt_rand(), true)));
$uuid = 
   substr($charid, 0, 8) . '-'
   . substr($charid, 8, 4) . '-'
   . substr($charid, 12, 4) . '-'
   . substr($charid, 16, 4) . '-'
   . substr($charid, 20, 12);

echo json_encode(array('id' => $uuid));
