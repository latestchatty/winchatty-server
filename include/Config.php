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

define('data_directory',             '/mnt/websites/winchatty.com/data/');
define('bookmarks_data_directory',   '/mnt/websites/winchatty.com/data/Bookmarks/');
define('locatecache_data_directory', '/mnt/websites/winchatty.com/data/LocateCache/');
define('search_data_directory',      '/mnt/websites/winchatty.com/data/Search/');

# For WinChatty v2 API
define('V2_CONNECTION_STRING', 'dbname=chatty user=nusearch password=nusearch');
define('V2_DATA_PATH', '/mnt/ssd/ChattyIndex/'); # must have trailing slash. must already exist.
