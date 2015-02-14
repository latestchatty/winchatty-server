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

require_once 'Config.php';
require_once 'ConfigUserPass.php';  # Defines WINCHATTY_USERNAME and WINCHATTY_PASSWORD constants.
require_once 'Bookmarks.php';

require_once 'Parser.php';
require_once 'ChattyParser.php';
require_once 'ThreadParser.php';
require_once 'SearchParser.php';
require_once 'StoriesFeedParser.php';
require_once 'FrontPageParser.php';
require_once 'ArticleParser.php';
require_once 'MessageParser.php';
require_once 'ProfileParser.php';

require_once 'ClassicAdapter.php';

require_once 'NuSearchClient.php';

error_reporting(E_ALL);

function check_set($array, $key)
{
   if (isset($array[$key]))
      return $array[$key];
   else
      die("Missing parameter $key.");
}

function array_top(&$array)
{
   return $array[count($array) - 1];
}

function collapse_whitespace($str)
{
	$str = str_replace("\n", ' ', $str);
	$str = str_replace("\t", ' ', $str);
	$str = str_replace("\r", ' ', $str);
	
	while (strpos($str, '  ') !== false)
		$str = str_replace('  ', ' ', $str);
	
	return $str;
}

