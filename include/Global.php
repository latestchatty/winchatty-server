<?
// WinChatty Server
// Copyright (c) 2013 Brian Luft
//
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
// documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
// Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
// OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

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
require_once 'DuctTapeSearch.php';

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

